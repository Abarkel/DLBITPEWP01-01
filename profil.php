<?php
// Datenbankverbindung einbinden
require_once 'db.php';

// Session sicherstellen (falls header.php später eingebunden wird)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prüfen, ob ein Benutzer eingeloggt ist
if (!isset($_SESSION['benutzer_id'])) {
    // Kein Login -> direkt zur Login-Seite umleiten
    header('Location: login.php');
    exit;
}


// Wenn der Benutzer eingeloggt ist 
$benutzerId = (int)$_SESSION['benutzer_id'];

// Prüfen, ob das Formular "Konto löschen" abgeschickt wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konto_loeschen'])) {
    // Benutzer als gelöscht markieren (Soft Delete)
    $sqlLoeschen = "UPDATE benutzer 
                    SET geloescht_am = NOW() 
                    WHERE benutzer_id = ?";
    $stmtLoeschen = $verbindung->prepare($sqlLoeschen);
    $stmtLoeschen->bind_param('i', $benutzerId);

    if ($stmtLoeschen->execute()) {
        $stmtLoeschen->close();

        // Session-Daten löschen
        $_SESSION = [];

        // Session-Cookie löschen (falls vorhanden)
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Session zerstören
        session_destroy();

        // Zur Startseite mit Hinweis umleiten
        header('Location: index.php?konto_geloescht=1');
        exit;
    } else {
        // Fehler beim Update (optional: Fehlermeldung behandeln)
        $stmtLoeschen->close();
        // Du könntest hier eine Variable $fehlermeldungProfil setzen, wenn du magst
    }
}


// Benutzer-Daten aus der Datenbank holen
$sqlBenutzer = "SELECT benutzername, email, geburtsdatum, erstellt_am , rolle
                FROM benutzer 
                WHERE benutzer_id = ?";
$stmtBenutzer = $verbindung->prepare($sqlBenutzer);
$stmtBenutzer->bind_param('i', $benutzerId);
$stmtBenutzer->execute();
$ergebnisBenutzer = $stmtBenutzer->get_result();

if (!$ergebnisBenutzer || $ergebnisBenutzer->num_rows === 0) {
    // Sollte eigentlich nicht passieren, aber zur Sicherheit:
    require 'header.php';
?>
    <h2>Profil</h2>
    <p style="color: red;">Benutzer wurde nicht gefunden.</p>
<?php
    require 'footer.php';
    exit;
}

$benutzer = $ergebnisBenutzer->fetch_assoc();
$stmtBenutzer->close();

// Alle Themen dieses Benutzers laden
$sqlThemen = "SELECT 
                t.thema_id, 
                t.titel, 
                t.erstellt_am,
                k.name AS kategoriename,
                k.kategorie_id
              FROM themen t
              JOIN kategorien k ON t.kategorie_id = k.kategorie_id
              WHERE t.benutzer_id = ?
              ORDER BY t.erstellt_am DESC";

$stmtThemen = $verbindung->prepare($sqlThemen);
$stmtThemen->bind_param('i', $benutzerId);
$stmtThemen->execute();
$ergebnisThemen = $stmtThemen->get_result();

// Ab hier HTML
require 'header.php';
?>

<h2>Mein Profil</h2>

<h3>Benutzerdaten</h3>
<ul>
    <li><strong>Benutzername:</strong> <?php echo htmlspecialchars($benutzer['benutzername']); ?></li>
    <li><strong>E-Mail:</strong> <?php echo htmlspecialchars($benutzer['email']); ?></li>
    <li><strong>Geburtsdatum:</strong> <?php echo htmlspecialchars($benutzer['geburtsdatum']); ?></li>
    <li><strong>Registriert seit:</strong> <?php echo htmlspecialchars($benutzer['erstellt_am']); ?></li>
    <li><strong>Rolle:</strong> <?php echo htmlspecialchars($benutzer['rolle']); ?></li>
</ul>

<h3>Meine Themen</h3>

<?php if ($ergebnisThemen && $ergebnisThemen->num_rows > 0): ?>
    <ul>
        <?php while ($thema = $ergebnisThemen->fetch_assoc()): ?>
            <li>
                <a href="thema.php?id=<?php echo (int)$thema['thema_id']; ?>">
                    <strong><?php echo htmlspecialchars($thema['titel']); ?></strong>
                </a>
                <br>
                <small>
                    Kategorie:
                    <a href="kategorie.php?id=<?php echo (int)$thema['kategorie_id']; ?>">
                        <?php echo htmlspecialchars($thema['kategoriename']); ?>
                    </a>
                    |
                    erstellt am <?php echo htmlspecialchars($thema['erstellt_am']); ?>
                </small>
            </li>
        <?php endwhile; ?>
    </ul>
<?php else: ?>
    <p>Du hast noch keine Themen erstellt.</p>
<?php endif; ?>

<?php
// Statement schließen
$stmtThemen->close();
?>

<h3>Mein Konto</h3>

<p>
    Achtung: Wenn du dein Konto löschst, kannst du dich nicht mehr einloggen.
    Deine bereits geschriebenen Themen und Beiträge bleiben im Forum erhalten.
</p>

<form method="post" action="profil.php"
    onsubmit="return confirm('Möchtest du dein Konto wirklich dauerhaft löschen? Dieser Vorgang kann nicht rückgängig gemacht werden.');">
    <!-- Hidden-Feld nur als Absicherung, damit wir wissen, welches Formular abgeschickt wurde -->
    <input type="hidden" name="konto_loeschen" value="1">
    <button type="submit">Mein Konto dauerhaft löschen</button>
</form>


<?php
require 'footer.php';
?>