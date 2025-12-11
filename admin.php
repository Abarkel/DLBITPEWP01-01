<?php
require_once 'db.php';
// Sitzung starten, falls noch nicht geschehen
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prüfen, ob jemand eingeloggt ist
if (!isset($_SESSION['benutzer_id'])) {
    header('Location: login.php');
    exit;
}

// Prüfen, ob Rolle = admin
if ($_SESSION['rolle'] !== 'Admin') {
    header('Location: index.php');
    exit;
}
// Meldung initialisieren
$meldung = '';

// Prüfen, ob eine Admin-Aktion angefordert wurde (z.B. Thema löschen oder Beitrag löschen)
if (isset($_GET['aktion'], $_GET['id'])) {
    $aktion = $_GET['aktion'];
    $id     = (int)$_GET['id'];

    // Das Kategorien löschen (optional, falls benötigt)        
    if ($aktion === 'kategorie_loeschen' && $id > 0) {
        $verbindung->begin_transaction();
        $fehler = false;

        // Alle Themen-IDs dieser Kategorie sammeln
        $sqlThemenIds = "SELECT thema_id FROM themen WHERE kategorie_id = ?";
        $stmt = $verbindung->prepare($sqlThemenIds);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $themaIds = [];
        while ($row = $res->fetch_assoc()) {
            $themaIds[] = (int)$row['thema_id'];
        }
        $stmt->close();

        // Beiträge zu den Themen löschen (falls vorhanden)
        if (!empty($themaIds) && !$fehler) {
            $sqlBeitrDel = "DELETE FROM beitraege WHERE thema_id = ?";
            $stmtB = $verbindung->prepare($sqlBeitrDel);
            foreach ($themaIds as $tid) {
                $stmtB->bind_param('i', $tid);
                if (!$stmtB->execute()) { $fehler = true; break; }
            }
            $stmtB->close();
        }

        // Themen in dieser Kategorie löschen
        if (!$fehler) {
            $sqlThemaDel = "DELETE FROM themen WHERE kategorie_id = ?";
            $stmtT = $verbindung->prepare($sqlThemaDel);
            $stmtT->bind_param('i', $id);
            if (!$stmtT->execute()) { $fehler = true; }
            $stmtT->close();
        }

        // Kategorie selbst löschen
        if (!$fehler) {
            $sqlKatDel = "DELETE FROM kategorien WHERE kategorie_id = ?";
            $stmtK = $verbindung->prepare($sqlKatDel);
            $stmtK->bind_param('i', $id);
            if ($stmtK->execute()) {
                $verbindung->commit();
                $meldung = 'Kategorie sowie alle zugehörigen Themen und Beiträge wurden gelöscht.';
            } else {
                $verbindung->rollback();
                $meldung = 'Fehler beim Löschen der Kategorie.';
            }
            $stmtK->close();
        } else {
            $verbindung->rollback();
            $meldung = 'Fehler beim Löschen: Vorgang abgebrochen.';
        }
    }


    //das Thema löschen
    if ($aktion === 'thema_loeschen' && $id > 0) {
        // ZUERST alle Beiträge zu diesem Thema löschen
        $sqlBeitraegeLoeschen = "DELETE FROM beitraege WHERE thema_id = ?";
        $stmtB = $verbindung->prepare($sqlBeitraegeLoeschen);
        $stmtB->bind_param('i', $id);
        $stmtB->execute();
        $stmtB->close();

        // Dann das Thema selbst löschen
        $sqlThemaLoeschen = "DELETE FROM themen WHERE thema_id = ?";
        $stmtT = $verbindung->prepare($sqlThemaLoeschen);
        $stmtT->bind_param('i', $id);

        if ($stmtT->execute()) {
            $meldung = 'Das Thema und alle zugehörigen Beiträge wurden gelöscht.';
        } else {
            $meldung = 'Fehler beim Löschen des Themas.';
        }
        $stmtT->close();
    }

    //das Beitrag löschen
    if ($aktion === 'beitrag_loeschen' && $id > 0) {
        // Einzelnen Beitrag löschen
        $sqlBeitragLoeschen = "DELETE FROM beitraege WHERE beitrag_id = ?";
        $stmtB2 = $verbindung->prepare($sqlBeitragLoeschen);
        $stmtB2->bind_param('i', $id);

        if ($stmtB2->execute()) {
            $meldung = 'Der Beitrag wurde gelöscht.';
        } else {
            $meldung = 'Fehler beim Löschen des Beitrags.';
        }
        $stmtB2->close();
    }

}

// Prüfen, ob eine neue Kategorie angelegt werden soll (per POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kategorie_anlegen'])) {
    $katName = trim($_POST['kategorie_name'] ?? '');
    $katBeschreibung = trim($_POST['kategorie_beschreibung'] ?? '');

    if ($katName === '') {
        $meldung = 'Der Name der Kategorie darf nicht leer sein.';
    } else {
        $sqlNeueKat = "INSERT INTO kategorien (name, beschreibung) VALUES (?, ?)";
        $stmtNeueKat = $verbindung->prepare($sqlNeueKat);
        $stmtNeueKat->bind_param('ss', $katName, $katBeschreibung);

        if ($stmtNeueKat->execute()) {
            $meldung = 'Die neue Kategorie wurde erfolgreich angelegt.';
        } else {
            $meldung = 'Fehler beim Anlegen der Kategorie.';
        }

        $stmtNeueKat->close();
    }
}

// Alle Kategorien laden (für Admin-Übersicht)
$sqlKategorien = "SELECT 
                    k.kategorie_id,
                    k.name,
                    k.beschreibung,
                    COUNT(t.thema_id) AS anzahl_themen
                  FROM kategorien k
                  LEFT JOIN themen t ON k.kategorie_id = t.kategorie_id
                  GROUP BY k.kategorie_id, k.name, k.beschreibung
                  ORDER BY k.name ASC";

$ergKategorien = $verbindung->query($sqlKategorien);

// Alle Themen laden (für Übersicht)
$sqlThemen = "SELECT 
                t.thema_id,
                t.titel,
                t.erstellt_am,
                k.name AS kategoriename,
                b.benutzername
              FROM themen t
              JOIN kategorien k ON t.kategorie_id = k.kategorie_id
              JOIN benutzer   b ON t.benutzer_id = b.benutzer_id
              ORDER BY t.erstellt_am DESC";
$ergThemen = $verbindung->query($sqlThemen);

// Einige Beiträge laden (z.B. die neuesten 50) für Admin-Übersicht
$sqlBeitraege = "SELECT 
                    p.beitrag_id,
                    p.inhalt,
                    p.erstellt_am,
                    p.thema_id,
                    t.titel AS thematitel,
                    b.benutzername
                 FROM beitraege p
                 JOIN themen   t ON p.thema_id = t.thema_id
                 JOIN benutzer b ON p.benutzer_id = b.benutzer_id
                 ORDER BY p.erstellt_am DESC
                 LIMIT 50";
$ergBeitraege = $verbindung->query($sqlBeitraege);

require 'header.php';
?>
<!--  Admin-Bereich  -->
<h2>Admin-Bereich</h2>
<p>Willkommen im Admin-Bereich, <?php echo htmlspecialchars($_SESSION['benutzername']); ?>.</p>

<!-- Kategorien verwalten-->
<h3>Kategorien verwalten</h3>
<?php if ($ergKategorien && $ergKategorien->num_rows > 0): ?>
    <ul>
        <?php while ($kat = $ergKategorien->fetch_assoc()): ?>
            <li>
                <strong><?php echo htmlspecialchars($kat['name']); ?></strong>
                <br>
                <small>
                    Beschreibung: <?php echo htmlspecialchars($kat['beschreibung']); ?><br>
                    Anzahl Themen: <?php echo (int)$kat['anzahl_themen']; ?><br>
                </small>
                <!-- Bearbeiten / Löschen -->
                 <a href="kategorie.php?id=<?php echo (int)$kat['kategorie_id']; ?>">Kategorie anzeigen</a>
                |
                <a href="admin.php?aktion=kategorie_loeschen&id=<?php echo (int)$kat['kategorie_id']; ?>"
                   onclick="return confirm('Willst du diese Kategorie und alle zugehörigen Themen und Beiträge wirklich löschen?');">
                    Kategorie löschen
                </a>
            </li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p>Es wurden noch keine Kategorien angelegt.</p>
<?php endif; ?>

<!-- Formular zum Anlegen einer neuen Kategorie -->
<h4>Neue Kategorie anlegen</h4>

<form method="post" action="admin.php">
<!--    Verstecktes Feld, um zu signalisieren, dass eine Kategorie angelegt werden soll -->
    <input type="hidden" name="kategorie_anlegen" value="1">

    <label for="kategorie_name">Name der Kategorie:</label><br>
    <input type="text" id="kategorie_name" name="kategorie_name" required><br>

    <label for="kategorie_beschreibung">Beschreibung (optional):</label><br>
    <textarea id="kategorie_beschreibung" name="kategorie_beschreibung" rows="3" cols="40"></textarea><br>

    <button type="submit">Kategorie erstellen</button><br><br>
</form>


<!-- Themen verwalten-->
<h3>Themen verwalten</h3>
<?php if ($ergThemen && $ergThemen->num_rows > 0): ?>
    <ul>
        <?php while ($thema = $ergThemen->fetch_assoc()): ?>
            <li>
                <strong><?php echo htmlspecialchars($thema['titel']); ?></strong>
                <br>
                <small>
                    Kategorie: <?php echo htmlspecialchars($thema['kategoriename']); ?> |
                    erstellt von <?php echo htmlspecialchars($thema['benutzername']); ?> 
                    am <?php echo htmlspecialchars($thema['erstellt_am']); ?>
                </small>
                <br>
                <a href="thema.php?id=<?php echo (int)$thema['thema_id']; ?>">Thema anzeigen</a>
                |
                <a href="admin.php?aktion=thema_loeschen&id=<?php echo (int)$thema['thema_id']; ?>"
                   onclick="return confirm('Willst du dieses Thema und alle zugehörigen Beiträge wirklich löschen?');">
                    Thema löschen
                </a>
            </li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p>Es wurden noch keine Themen erstellt.</p>
<?php endif; ?>

<!-- Beiträge verwalten-->
<h3>Beiträge verwalten (neueste 50)</h3>
<?php if ($ergBeitraege && $ergBeitraege->num_rows > 0): ?>
    <ul>
        <?php
        // Prepared Statement, um für ein Thema den ersten Beitrag zu finden
        $sqlErsterBeitrag = "SELECT MIN(beitrag_id) AS erster_id FROM beitraege WHERE thema_id = ?";
        $stmtErster = $verbindung->prepare($sqlErsterBeitrag);

        while ($beitrag = $ergBeitraege->fetch_assoc()):
            $themaId   = (int)$beitrag['thema_id'];
            $beitragId = (int)$beitrag['beitrag_id'];

            // Für dieses Thema den ersten Beitrag ermitteln
            $stmtErster->bind_param('i', $themaId);
            $stmtErster->execute();
            $resultErster = $stmtErster->get_result();
            $rowErster = $resultErster->fetch_assoc();
            $ersterId = (int)$rowErster['erster_id'];

            // Prüfen: ist dieser Beitrag der Startbeitrag?
            $istStartBeitrag = ($beitragId === $ersterId);
        ?>
            <li>
                <strong>Beitrag in: <?php echo htmlspecialchars($beitrag['thematitel']); ?></strong>
                <br>
                <small>
                    von <?php echo htmlspecialchars($beitrag['benutzername']); ?> 
                    am <?php echo htmlspecialchars($beitrag['erstellt_am']); ?>
                </small>
                <br>
                <span>
                    <?php echo nl2br(htmlspecialchars(mb_strimwidth($beitrag['inhalt'], 0, 120, '...'))); ?>
                </span>
                <br>
                <!-- Link zum Thema -->
                <a href="thema.php?id=<?php echo $themaId; ?>">Zum Thema</a>
                |
                <?php if ($istStartBeitrag): ?>
                    <!-- Startbeitrag: nicht direkt löschbar -->
                    <em>Startbeitrag – zum Löschen bitte „Thema löschen“ verwenden.</em>
                <?php else: ?>
                    <!-- Normale Beiträge können gelöscht werden -->
                    <a href="admin.php?aktion=beitrag_loeschen&id=<?php echo $beitragId; ?>"
                       onclick="return confirm('Willst du diesen Beitrag wirklich löschen?');">
                        Beitrag löschen
                    </a>
                <?php endif; ?>
            </li>
        <?php endwhile;

        $stmtErster->close();
        ?>
    </ul>
    <!-- Anzeige der Meldung, falls vorhanden -->
    <?php if ($meldung !== ''): ?>
    <p style="color: green;"><?php echo htmlspecialchars($meldung); ?></p>
<?php endif; ?>
<?php else: ?>
    <p>Es wurden noch keine Beiträge geschrieben.</p>
<?php endif; ?>

<?php
require 'footer.php';
?>
