<?php
// Datenbankverbindung einbinden
require_once 'db.php';

// Variable für Fehlermeldungen und Erfolgsmeldungen
$fehlermeldung = '';
$erfolgsmeldung = '';

// Prüfen, ob das Formular abgeschickt wurde (HTTP-Methode = POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formulardaten aus $_POST auslesen
    // Der ?? '' Teil sorgt dafür, dass im Zweifel ein leerer String gesetzt wird
    $benutzername = trim($_POST['benutzername'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $passwort     = $_POST['passwort'] ?? '';
    $geburtsdatum = $_POST['geburtsdatum'] ?? '';

    // 1. Grundlegende Validierung: sind alle Felder ausgefüllt?
    if ($benutzername === '' || $email === '' || $passwort === '' || $geburtsdatum === '') {
        $fehlermeldung = 'Bitte alle Felder ausfüllen.';
    } 
    // 2. E-Mail-Format prüfen
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fehlermeldung = 'Bitte eine gültige E-Mail-Adresse eingeben.';
    } 
    // 3. Passwortlänge prüfen (z. B. mindestens 8 Zeichen)
    elseif (strlen($passwort) < 8) {
        $fehlermeldung = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
    } 
    else {
        // 4. Geburtsdatum in ein DateTime-Objekt umwandeln
        $geburtsdatumObj = DateTime::createFromFormat('Y-m-d', $geburtsdatum);

        if (!$geburtsdatumObj) {
            // Falls das Datum nicht im erwarteten Format ist
            $fehlermeldung = 'Das Geburtsdatum ist ungültig.';
        } else {
            // Heutiges Datum holen
            $heute = new DateTime();

            // Altersdifferenz berechnen
            $alter = $heute->diff($geburtsdatumObj)->y;

            // 5. Prüfen, ob der Nutzer mindestens 16 Jahre alt ist
            if ($alter < 16) {
                $fehlermeldung = 'Du musst mindestens 16 Jahre alt sein, um dich zu registrieren.';
            } else {
              // 6. Prüfen, ob die E-Mail schon existiert
$sqlPruefung = "SELECT benutzer_id, geloescht_am 
                FROM benutzer 
                WHERE email = ?";
$stmtPruefung = $verbindung->prepare($sqlPruefung);
$stmtPruefung->bind_param('s', $email);
$stmtPruefung->execute();
$resultPruefung = $stmtPruefung->get_result();

if ($resultPruefung && $resultPruefung->num_rows > 0) {
    // Es gibt bereits einen Eintrag mit dieser E-Mail
    $row = $resultPruefung->fetch_assoc();
    $existierendeBenutzerId = (int)$row['benutzer_id'];
    $geloeschtAm = $row['geloescht_am'];

    if (is_null($geloeschtAm)) {
        // Konto ist noch aktiv -> normale Fehlermeldung
        $fehlermeldung = 'Diese E-Mail-Adresse ist bereits registriert.';
    } else {
        // Konto wurde früher gelöscht -> Konto reaktivieren
        $passworthash = password_hash($passwort, PASSWORD_DEFAULT);

        $sqlUpdate = "UPDATE benutzer
                      SET benutzername = ?, 
                          passworthash = ?, 
                          geburtsdatum = ?, 
                          rolle = 'nutzer',
                          geloescht_am = NULL
                      WHERE benutzer_id = ?";
        $stmtUpdate = $verbindung->prepare($sqlUpdate);
        $stmtUpdate->bind_param('sssi', $benutzername, $passworthash, $geburtsdatum, $existierendeBenutzerId);

        if ($stmtUpdate->execute()) {
            $erfolgsmeldung = 'Dein zuvor gelöschtes Konto wurde reaktiviert. Du kannst dich jetzt einloggen.';
            // Felder leeren
            $benutzername = '';
            $email        = '';
            $geburtsdatum = '';
        } else {
            $fehlermeldung = 'Fehler beim Reaktivieren deines Kontos.';
        }

        $stmtUpdate->close();
    }

   // $stmtPruefung->close();
}
                else {
                    // 7. Passwort sicher hashen
                    $passworthash = password_hash($passwort, PASSWORD_DEFAULT);

                    // 8. Neuen Benutzer in die Datenbank einfügen
                    $sqlEintrag = "INSERT INTO benutzer (benutzername, email, passworthash, geburtsdatum, rolle) 
                                   VALUES (?, ?, ?, ?, 'nutzer')";
                    $stmtEintrag = $verbindung->prepare($sqlEintrag);

                    // "ssss" bedeutet: 4x String-Parameter
                    $stmtEintrag->bind_param('ssss', $benutzername, $email, $passworthash, $geburtsdatum);

                    if ($stmtEintrag->execute()) {
                        $erfolgsmeldung = 'Registrierung erfolgreich! Du kannst dich jetzt einloggen.';
                        // Felder leeren, damit das Formular nach Erfolg leer ist
                        $benutzername = '';
                        $email        = '';
                        $geburtsdatum = '';
                    } else {
                        $fehlermeldung = 'Fehler beim Speichern in der Datenbank.';
                    }

                    $stmtEintrag->close();
                }

                $stmtPruefung->close();
            }
        }
    }
}
?>

<?php 
// header.php einbinden (HTML-Kopf, <body>, Überschrift usw.)
require 'header.php'; 
?>

<h2>Registrierung</h2>
<p>Bitte registriere dich, um am Anime-Forum teilzunehmen.</p>

<!-- 
    HTML-Formular für die Registrierung.
    method="post" bedeutet: die Daten werden per POST an den Server gesendet.
    action="register.php" bedeutet: die gleiche Datei verarbeitet die Daten.
-->
<form method="post" action="register.php">
    <!-- Benutzername-Feld -->
    <label for="benutzername">Benutzername:</label><br>
    <input type="text" id="benutzername" name="benutzername" 
           value="<?php echo isset($benutzername) ? htmlspecialchars($benutzername) : ''; ?>" 
           required><br><br>

    <!-- E-Mail-Feld -->
    <label for="email">E-Mail:</label><br>
    <input type="email" id="email" name="email" 
           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
           required><br><br>

    <!-- Passwort-Feld -->
    <label for="passwort">Passwort:</label><br>
    <input type="password" id="passwort" name="passwort" required><br><br>

    <!-- Geburtsdatum-Feld (für Altersprüfung ab 16) -->
    <label for="geburtsdatum">Geburtsdatum:</label><br>
    <input type="date" id="geburtsdatum" name="geburtsdatum" 
           value="<?php echo isset($geburtsdatum) ? htmlspecialchars($geburtsdatum) : ''; ?>" 
           required><br><br>

    <!-- Button zum Abschicken des Formulars -->
    <button type="submit">Registrieren</button>
</form>
<p>
    Du hast schon ein Konto?
    <a href="login.php">Hier einloggen</a>
</p>

<?php if ($fehlermeldung !== ''): ?>
    <!-- Fehlermeldung in Rot anzeigen -->
    <p style="color: red;"><?php echo htmlspecialchars($fehlermeldung); ?></p>
<?php elseif ($erfolgsmeldung !== ''): ?>
    <!-- Erfolgsmeldung in Grün anzeigen -->
    <p style="color: green;"><?php echo htmlspecialchars($erfolgsmeldung); ?></p>
<?php endif; ?>

<?php 
// footer.php einbinden (Footer + </body></html>)
require 'footer.php'; 
?>

