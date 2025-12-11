<?php
// Session starten, falls noch keine existiert
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Alle Session-Daten löschen
$_SESSION = [];

// Session-Cookie (falls gesetzt) löschen
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

// Nach dem Logout auf die Login-Seite weiterleiten
// und einen URL-Parameter anhängen, damit wir dort wissen, 
// dass der Logout erfolgreich war.
header('Location: login.php?logout=1');
exit;
