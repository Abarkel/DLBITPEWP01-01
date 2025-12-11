<?php
// DB-Verbindung einbinden
require_once 'db.php';

// Prüfen, ob eine kategorie_id übergeben wurde
$kategorieId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Falls keine oder ungültige ID: einfache Fehlermeldung
if ($kategorieId <= 0) {
    die('Ungültige Kategorie.');
}

// Kategorie aus der Datenbank holen
$sqlKategorie = "SELECT name, beschreibung FROM kategorien WHERE kategorie_id = ?";
$stmtKat = $verbindung->prepare($sqlKategorie);
$stmtKat->bind_param('i', $kategorieId);
$stmtKat->execute();
$ergebnisKat = $stmtKat->get_result();

if (!$ergebnisKat || $ergebnisKat->num_rows === 0) {
    die('Kategorie nicht gefunden.');
}

$kategorie = $ergebnisKat->fetch_assoc();

$stmtKat->close();

// Erfolgsmeldung nach dem Erstellen eines neuen Themas
$erfolgsmeldung = '';
if (isset($_GET['erfolg']) && $_GET['erfolg'] === '1') {
    $erfolgsmeldung = 'Dein Thema wurde erfolgreich erstellt.';
}

// Themen (Threads) dieser Kategorie aus der Datenbank holen
$sqlThemen = "SELECT t.thema_id, t.titel, t.erstellt_am, b.benutzername
              FROM themen t
              JOIN benutzer b ON t.benutzer_id = b.benutzer_id
              WHERE t.kategorie_id = ?
              ORDER BY t.erstellt_am DESC";
// Vorbereitung und Ausführung der Abfrage
$stmtThemen = $verbindung->prepare($sqlThemen);
$stmtThemen->bind_param('i', $kategorieId);
$stmtThemen->execute();
$ergebnisThemen = $stmtThemen->get_result();
?>

<?php require 'header.php'; ?>

<h2><?php echo htmlspecialchars($kategorie['name']); ?></h2>
<p><?php echo htmlspecialchars($kategorie['beschreibung']); ?></p>



<h3>Themen in dieser Kategorie</h3>

<ul>
    <?php if ($ergebnisThemen && $ergebnisThemen->num_rows > 0): ?>
        <?php while ($thema = $ergebnisThemen->fetch_assoc()): ?>
            <li>
                <!-- Link zur Themen-Seite (thema.php, bauen wir gleich) -->
                <a href="thema.php?id=<?php echo (int)$thema['thema_id']; ?>">
                    <strong><?php echo htmlspecialchars($thema['titel']); ?></strong>
                </a>
                <br>
                <small>
                    erstellt von
                    <?php echo htmlspecialchars($thema['benutzername']); ?>
                    am
                    <?php echo htmlspecialchars($thema['erstellt_am']); ?>
                </small>
            </li>
        <?php endwhile; ?>
    <?php else: ?>
        <li>Es wurden noch keine Themen in dieser Kategorie erstellt.</li>
    <?php endif; ?>
</ul>

<?php if ($erfolgsmeldung !== ''): ?>
    <p style="color: green;"><?php echo htmlspecialchars($erfolgsmeldung); ?></p>
<?php endif; ?>

<!-- Link wird IMMER angezeigt.
     Die eigentliche Prüfung, ob jemand eingeloggt ist,
     passiert in thema_erstellen.php. -->
<p>
    <a href="thema_erstellen.php?kategorie_id=<?php echo (int)$kategorieId; ?>">
        Neues Thema in dieser Kategorie erstellen
    </a>
</p>


<?php
// Statement für Themen schließen
$stmtThemen->close();
?>

<?php require 'footer.php'; ?>