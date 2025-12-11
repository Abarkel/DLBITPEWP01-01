<?php
// Datenbankverbindung einbinden
require_once 'db.php';

// Session sicherstellen (falls header.php noch nicht eingebunden ist)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prüfen, ob ein Benutzer eingeloggt ist
$istEingeloggt = isset($_SESSION['benutzer_id']);
$benutzerId = $istEingeloggt ? (int)$_SESSION['benutzer_id'] : 0;

// Kategorie-ID ermitteln:
// - Beim ersten Aufruf über GET (?kategorie_id=...)
// - Beim Formular-POST über hidden-Feld
$kategorieId = 0;

if (isset($_GET['kategorie_id'])) {
    $kategorieId = (int)$_GET['kategorie_id'];
} elseif (isset($_POST['kategorie_id'])) {
    $kategorieId = (int)$_POST['kategorie_id'];
}

// Grundcheck: gültige Kategorie-ID?
if ($kategorieId <= 0) {
    die('Ungültige Kategorie-ID.');
}

// Kategorie-Daten holen (für Überschrift usw.)
$sqlKategorie = "SELECT name FROM kategorien WHERE kategorie_id = ?";
$stmtKat = $verbindung->prepare($sqlKategorie);
$stmtKat->bind_param('i', $kategorieId);
$stmtKat->execute();
$ergebnisKat = $stmtKat->get_result();

if (!$ergebnisKat || $ergebnisKat->num_rows === 0) {
    die('Kategorie nicht gefunden.');
}

$kategorie = $ergebnisKat->fetch_assoc();
$stmtKat->close();

// Variablen für Fehlermeldungen / Formularwerte
$fehlermeldung = '';
$titel = '';
$inhalt = '';

// Prüfen: ist der Benutzer eingeloggt?
// Wenn nicht, zeigen wir eine Fehlermeldung + Links und brechen ab.
if (!$istEingeloggt) {
    require 'header.php';
?>
    <h2>Neues Thema erstellen</h2>
    <p style="color: red;">
        Du musst eingeloggt sein, um ein neues Thema zu erstellen.
    </p>
    <p>
        <a href="login.php">Zum Login</a> |
        <a href="register.php">Jetzt registrieren</a>
    </p>
<?php
    require 'footer.php';
    exit;
}

// Wenn wir hier sind, ist der Benutzer eingeloggt.
// Jetzt prüfen wir, ob das Formular abgeschickt wurde.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formulardaten auslesen
    $titel  = trim($_POST['titel'] ?? '');
    $inhalt = trim($_POST['inhalt'] ?? '');

    // Einfache Validierung: Titel und Inhalt dürfen nicht leer sein
    if ($titel === '' || $inhalt === '') {
        $fehlermeldung = 'Bitte sowohl einen Titel als auch einen Inhalt für das Thema eingeben.';
    } else {
        // 1. Neues Thema in der Tabelle "themen" anlegen
        $sqlThema = "INSERT INTO themen (kategorie_id, benutzer_id, titel) 
                     VALUES (?, ?, ?)";
        $stmtThema = $verbindung->prepare($sqlThema);
        $stmtThema->bind_param('iis', $kategorieId, $benutzerId, $titel);

        if ($stmtThema->execute()) {
            // ID des neu erstellten Themas holen
            $neueThemaId = $verbindung->insert_id;
            $stmtThema->close();

            // 2. Ersten Beitrag in der Tabelle "beitraege" anlegen
            $sqlBeitrag = "INSERT INTO beitraege (thema_id, benutzer_id, inhalt) 
                           VALUES (?, ?, ?)";
            $stmtBeitrag = $verbindung->prepare($sqlBeitrag);
            $stmtBeitrag->bind_param('iis', $neueThemaId, $benutzerId, $inhalt);

            if ($stmtBeitrag->execute()) {
                $stmtBeitrag->close();

                // Weiterleitung zur Kategorie-Seite mit Erfolgsmeldung
                header('Location: kategorie.php?id=' . $kategorieId . '&erfolg=1');
                exit;
            } else {
                $fehlermeldung = 'Fehler beim Speichern des ersten Beitrags.';
                $stmtBeitrag->close();
            }
        } else {
            $fehlermeldung = 'Fehler beim Speichern des neuen Themas.';
            $stmtThema->close();
        }
    }
}

// HTML-Teil: Formular anzeigen (für GET oder bei Fehlern)
require 'header.php';
?>

<h2>Neues Thema in: <?php echo htmlspecialchars($kategorie['name']); ?></h2>

<?php if ($fehlermeldung !== ''): ?>
    <p style="color: red;"><?php echo htmlspecialchars($fehlermeldung); ?></p>
<?php endif; ?>

<form method="post" action="thema_erstellen.php">
    <!-- Kategorie-ID als verstecktes Feld mitgeben -->
    <input type="hidden" name="kategorie_id" value="<?php echo (int)$kategorieId; ?>">
    <!-- Formularfelder für Titel und Inhalt -->
    <label for="titel">Titel des Themas:</label><br>
    <input type="text" id="titel" name="titel"
        value="<?php echo htmlspecialchars($titel); ?>"
        required><br><br>
    <!-- Inhalt des ersten Beitrags -->
    <label for="inhalt">Erster Beitrag:</label><br>
    <textarea id="inhalt" name="inhalt" rows="5" cols="50" required>
        <?php echo htmlspecialchars($inhalt); ?> 
    </textarea><br><br>
    <!-- Absende-Button -->
    <button type="submit">Thema erstellen</button>
</form>

<p>
    <a href="kategorie.php?id=<?php echo (int)$kategorieId; ?>">Zurück zur Kategorie</a>
</p>

<?php
require 'footer.php';
?>