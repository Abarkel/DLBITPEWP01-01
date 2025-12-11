<?php
// Datenbankverbindung einbinden
require_once 'db.php';

// Meldungen für diese Seite
$meldung = '';
$fehlermeldungLoeschen = '';
$fehlermeldungAntwort = '';

// Session sicherstellen (falls header.php später eingebunden wird)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prüfen, ob ein Benutzer eingeloggt ist
$istEingeloggt = isset($_SESSION['benutzer_id']);
$benutzerId    = $istEingeloggt ? (int)$_SESSION['benutzer_id'] : 0;
$benutzername  = $istEingeloggt ? $_SESSION['benutzername'] : null;
$aktuellerRolle = $istEingeloggt ? $_SESSION['rolle'] : null;

// Prüfen, ob eine Lösch-Aktion für einen Beitrag angefordert wurde
if (isset($_GET['aktion'], $_GET['beitrag_id']) && $_GET['aktion'] === 'beitrag_loeschen') {
    // Nutzer muss eingeloggt sein
    if (!$istEingeloggt) {
        $fehlermeldungLoeschen = 'Zum Löschen eines Beitrags musst du angemeldet sein.';
    } else {
        $beitragId = (int)$_GET['beitrag_id'];

        // 1. Beitrag laden, um thema_id und benutzer_id zu erfahren
        $sqlBeitrag = "SELECT beitrag_id, thema_id, benutzer_id 
                       FROM beitraege 
                       WHERE beitrag_id = ?";
        $stmtB = $verbindung->prepare($sqlBeitrag);
        $stmtB->bind_param('i', $beitragId);
        $stmtB->execute();
        $resB = $stmtB->get_result();

        if (!$resB || $resB->num_rows === 0) {
            $fehlermeldungLoeschen = 'Der Beitrag wurde nicht gefunden.';
        } else {
            $rowB       = $resB->fetch_assoc();
            $themaId    = (int)$rowB['thema_id'];
            $besitzerId = (int)$rowB['benutzer_id'];

            $istAdmin = ($aktuellerRolle === 'admin' || $aktuellerRolle === 'Admin');

            // 2. Darf der Nutzer diesen Beitrag löschen?
            if ($besitzerId !== (int)$_SESSION['benutzer_id'] && !$istAdmin) {
                $fehlermeldungLoeschen = 'Du darfst nur deine eigenen Beiträge löschen.';
            } else {
                // 3. Ersten Beitrag in diesem Thema ermitteln
                $sqlErster = "SELECT MIN(beitrag_id) AS erster_id 
                              FROM beitraege 
                              WHERE thema_id = ?";
                $stmtE = $verbindung->prepare($sqlErster);
                $stmtE->bind_param('i', $themaId);
                $stmtE->execute();
                $resE  = $stmtE->get_result();
                $rowE  = $resE->fetch_assoc();
                $ersterId = (int)$rowE['erster_id'];
                $stmtE->close();

                if ($beitragId === $ersterId) {
                    $fehlermeldungLoeschen  = 'Den ersten Beitrag eines Themas kannst du nicht löschen.';
                } else {
                    // 4. Beitrag löschen
                    $sqlDel = "DELETE FROM beitraege WHERE beitrag_id = ?";
                    $stmtDel = $verbindung->prepare($sqlDel);
                    $stmtDel->bind_param('i', $beitragId);

                    if ($stmtDel->execute()) {
                        $meldung = 'Dein Beitrag wurde gelöscht.';
                    } else {
                        $fehlermeldungLoeschen = 'Fehler beim Löschen deines Beitrags.';
                    }

                    $stmtDel->close();
                }
            }
        }

        $stmtB->close();
    }
}


// Thema-ID aus der URL holen, z.B. thema.php?id=3
$themaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Grundcheck: ist die ID sinnvoll?
if ($themaId <= 0) {
    die('Ungültige Thema-ID.');
}

// Thema + Kategorie + Ersteller aus der Datenbank holen
$sqlThema = "SELECT 
                t.thema_id,
                t.titel,
                t.erstellt_am,
                t.kategorie_id,
                k.name AS kategoriename,
                b.benutzername AS themenersteller
             FROM themen t
             JOIN kategorien k ON t.kategorie_id = k.kategorie_id
             JOIN benutzer b   ON t.benutzer_id = b.benutzer_id
             WHERE t.thema_id = ?
             LIMIT 1";
// Vorbereitung und Ausführung der Abfrage
$stmtThema = $verbindung->prepare($sqlThema);
$stmtThema->bind_param('i', $themaId);
$stmtThema->execute();
$ergebnisThema = $stmtThema->get_result();

if (!$ergebnisThema || $ergebnisThema->num_rows === 0) {
    die('Thema nicht gefunden.');
}

$thema = $ergebnisThema->fetch_assoc();
$stmtThema->close();

// Kategorie-ID für "Zurück zur Kategorie"-Link
$kategorieId   = (int)$thema['kategorie_id'];
$kategoriename = $thema['kategoriename'];

// Prüfen, ob das Formular für eine neue Antwort abgeschickt wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Inhalt des Antwort-Textfelds holen
    $inhalt = trim($_POST['inhalt'] ?? '');

    // Ist der Benutzer überhaupt eingeloggt?
    if (!$istEingeloggt) {
        $fehlermeldungAntwort  = 'Du musst eingeloggt sein, um auf ein Thema zu antworten.';
    }
    // Ist der Inhalt leer?
    elseif ($inhalt === '') {
        $fehlermeldungAntwort = 'Die Antwort darf nicht leer sein.';
    } else {
        // Neuen Beitrag in der Tabelle "beitraege" speichern
        $sqlAntwort = "INSERT INTO beitraege (thema_id, benutzer_id, inhalt)
                       VALUES (?, ?, ?)";
        $stmtAntwort = $verbindung->prepare($sqlAntwort);
        $stmtAntwort->bind_param('iis', $themaId, $benutzerId, $inhalt);

        if ($stmtAntwort->execute()) {
            $stmtAntwort->close();

            // PRG-Pattern (Post-Redirect-Get):
            // Nach erfolgreichem Speichern weiterleiten,
            // damit ein Reload nicht denselben Beitrag nochmal sendet.
            header('Location: thema.php?id=' . $themaId);
            exit;
        } else {
            $fehlermeldungAntwort = 'Fehler beim Speichern der Antwort.';
            $stmtAntwort->close();
        }
    }
}

// Jetzt ALLE Beiträge zu diesem Thema laden
$sqlBeitraege = "SELECT 
                    p.beitrag_id,
                    p.inhalt,
                    p.benutzer_id,
                    p.erstellt_am,
                    p.bearbeitet_am,
                    b.benutzername
                 FROM beitraege p
                 JOIN benutzer b ON p.benutzer_id = b.benutzer_id
                 WHERE p.thema_id = ?
                 ORDER BY p.erstellt_am ASC";

$stmtBeitraege = $verbindung->prepare($sqlBeitraege);
$stmtBeitraege->bind_param('i', $themaId);
$stmtBeitraege->execute();
$ergebnisBeitraege = $stmtBeitraege->get_result();

// Ab hier HTML-Ausgabe
require 'header.php';
?>

<h2><?php echo htmlspecialchars($thema['titel']); ?></h2>

<p>
    Kategorie:
    <a href="kategorie.php?id=<?php echo $kategorieId; ?>">
        <?php echo htmlspecialchars($kategoriename); ?>
    </a>
    <br>
    Erstellt von
    <strong><?php echo htmlspecialchars($thema['themenersteller']); ?></strong>
    am
    <?php echo htmlspecialchars($thema['erstellt_am']); ?>
</p>

<h3>Beiträge</h3>
<?php if ($ergebnisBeitraege && $ergebnisBeitraege->num_rows > 0): ?>
    <?php while ($beitrag = $ergebnisBeitraege->fetch_assoc()): ?>
        <div class="beitrag-box">
            <p><?php echo nl2br(htmlspecialchars($beitrag['inhalt'])); ?></p>
            <small>
                von <?php echo htmlspecialchars($beitrag['benutzername']); ?>
                am <?php echo htmlspecialchars($beitrag['erstellt_am']); ?>
            </small>

            <?php
            // Darf der aktuelle Nutzer diesen Beitrag löschen?
            $istAdmin = ($aktuellerRolle === 'admin' || $aktuellerRolle === 'Admin');

            if (
                $istEingeloggt
                && (
                    (int)$beitrag['benutzer_id'] === (int)$_SESSION['benutzer_id']
                    || $istAdmin
                )
            ):
            ?>
                <br>
                <a href="thema.php?id=<?php echo (int)$themaId; ?>&aktion=beitrag_loeschen&beitrag_id=<?php echo (int)$beitrag['beitrag_id']; ?>"
                    onclick="return confirm('Willst du diesen Beitrag wirklich löschen?');">
                    Beitrag löschen
                </a>
            <?php endif; ?>
        </div>

    <?php endwhile; ?>

<?php else: ?>
    <p>Es wurden noch keine Beiträge in diesem Thema geschrieben.</p>
<?php endif; ?>

<!-- Meldungen anzeigen-->
<?php if ($meldung !== ''): ?>
    <p class="msg-info"><?php echo htmlspecialchars($meldung); ?></p>
<?php endif; ?>

<?php if ($fehlermeldungLoeschen !== ''): ?>
    <p class="msg-error"><?php echo htmlspecialchars($fehlermeldungLoeschen); ?></p>
<?php endif; ?>

<?php
// Statement schließen
$stmtBeitraege->close();
?>

<h3>Antwort schreiben</h3>

<?php if ($istEingeloggt): ?>
    <!-- Formular für eine neue Antwort (nur für eingeloggte Nutzer sichtbar) -->
    <form method="post" action="thema.php?id=<?php echo $themaId; ?>">
        <label for="inhalt">Deine Antwort:</label><br>
        <textarea id="inhalt" name="inhalt" rows="5" cols="60" required></textarea><br><br>

        <button type="submit">Antwort absenden</button>
    </form>
<?php else: ?>
    <p>
        Du musst eingeloggt sein, um auf dieses Thema zu antworten.
        <a href="login.php">Jetzt einloggen</a> oder
        <a href="register.php">registrieren</a>.
    </p>
<?php endif; ?>
<!-- Fehlermeldungen für das Antworten -->
<?php if ($fehlermeldungAntwort !== ''): ?>
    <p class="msg-error"><?php echo htmlspecialchars($fehlermeldungAntwort); ?></p>
<?php endif; ?>

<p>
    <a href="kategorie.php?id=<?php echo $kategorieId; ?>">Zurück zur Kategorie</a>
</p>

<?php
require 'footer.php';
?>