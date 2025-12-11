<?php

// Session wird in header.php gestartet.
 if (session_status() === PHP_SESSION_NONE) {
     session_start();
 }
 
// Datenbankverbindung einbinden
require_once 'db.php';

// Variable für Fehlermeldung
$fehlermeldung = '';

// Variable für Info-Meldung (z.B. nach erfolgreichem Logout)
$infomeldung = '';

// Prüfen, ob wir von logout.php kommen (URL-Parameter ?logout=1)
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    $infomeldung = 'Du wurdest erfolgreich ausgeloggt.';
}

// Prüfen, ob das Formular abgeschickt wurde (HTTP-Methode = POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formulardaten auslesen
    // loginname = "Benutzername oder E-Mail"
    $loginname = trim($_POST['loginname'] ?? '');
    $passwort  = $_POST['passwort'] ?? '';

    // 1. Prüfen, ob beide Felder ausgefüllt sind
    if ($loginname === '' || $passwort === '') {
        $fehlermeldung = 'Bitte Benutzername/E-Mail und Passwort eingeben.';
    } else {
        // 2. Benutzer in der Datenbank suchen:
        //    Entweder über E-Mail ODER über Benutzername
        $sql = "SELECT benutzer_id, benutzername, email, passworthash, rolle, geloescht_am 
                FROM benutzer 
                WHERE email = ? OR benutzername = ?
                LIMIT 1";

        // Prepared Statement vorbereiten (Schutz vor SQL-Injection)
        $stmt = $verbindung->prepare($sql);

        if ($stmt === false) {
            // Falls das Statement nicht vorbereitet werden konnte
            $fehlermeldung = 'Technischer Fehler beim Login (Datenbank-Problem).';
        } else {
            // "ss" = zwei Strings (email oder benutzername)
            $stmt->bind_param('ss', $loginname, $loginname);
            $stmt->execute();

            // Ergebnis holen
            $ergebnis = $stmt->get_result();

            if ($ergebnis && $ergebnis->num_rows === 1) {
                // Genau ein Benutzer gefunden
                $benutzer = $ergebnis->fetch_assoc();
                if (!is_null($benutzer['geloescht_am'])) {
                      // Konto wurde gelöscht -> keine Anmeldung erlauben
                     $fehlermeldung = 'Dieses Konto wurde gelöscht und kann nicht mehr verwendet werden.';
                 } 

                // 3. Passwort prüfen mit password_verify
                elseif (password_verify($passwort, $benutzer['passworthash'])) {
                    // 4. Login erfolgreich: Benutzer-Daten in der Session speichern
                    // (Session wurde in header.php gestartet)
                    $_SESSION['benutzer_id']   = $benutzer['benutzer_id'];
                    $_SESSION['benutzername']  = $benutzer['benutzername'];
                    $_SESSION['rolle']         = $benutzer['rolle'];

                    // Optional: kleine Erfolgsmeldung in der Session (Flash-Message)
                    $_SESSION['login_erfolg'] = 'Login erfolgreich. Willkommen, ' . $benutzer['benutzername'] . '!';

                    // 5. Weiterleitung auf die Startseite
                    header('Location: index.php');
                    exit;
                } else {
                    // Passwort falsch
                    $fehlermeldung = 'Benutzername/E-Mail oder Passwort ist falsch.';
                }
            } else {
                // Kein Benutzer mit diesem Loginnamen gefunden
                $fehlermeldung = 'Benutzername/E-Mail oder Passwort ist falsch.';
            }

            $stmt->close();
        }
    }
}
?>

<?php 
// header.php einbinden (HTML-Kopf, <body>, Überschrift usw.)
require 'header.php'; 
?>

<h2>Login</h2>
<p>Bitte melde dich an, um auf alle Funktionen des Anime-Forums zuzugreifen.</p>



<!-- 
    HTML-Formular für den Login.
    Der Benutzer kann hier E-Mail ODER Benutzername eingeben.
-->
<form method="post" action="login.php">
    <!-- Benutzername oder E-Mail -->
    <label for="loginname">Benutzername oder E-Mail:</label><br>
    <input type="text" id="loginname" name="loginname" required><br><br>

    <!-- Passwort -->
    <label for="passwort">Passwort:</label><br>
    <input type="password" id="passwort" name="passwort" required><br><br>

    <button type="submit">Login</button>
</form>

<?php if ($infomeldung !== ''): ?>
    <!-- Info-Meldung in Grün anzeigen (z.B. nach Logout) -->
    <p style="color: green;"><?php echo htmlspecialchars($infomeldung); ?></p>
<?php endif; ?>

<?php if ($fehlermeldung !== ''): ?>
    <!-- Fehlermeldung in Rot anzeigen -->
    <p style="color: red;"><?php echo htmlspecialchars($fehlermeldung); ?></p>
<?php endif; ?>

<p>
    Noch kein Konto? 
    <a href="register.php">Jetzt registrieren</a>
</p>

<p>
    Passwort vergessen? 
    <!-- Diese Seite kannst du später optional anlegen und in der Doku als Erweiterung erklären -->
    <a href="passwort_vergessen.php">Passwort zurücksetzen (geplante Funktion)</a>
</p>

<?php 
// footer.php einbinden (Footer + </body></html>)
require 'footer.php'; 
?>
