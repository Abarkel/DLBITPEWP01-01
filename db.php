<?php
$host = 'localhost';
$benutzer = 'root';
$passwort = ''; // Standard bei XAMPP: kein Passwort
$datenbank = 'anime_forum';
// Verbindung zur Datenbank herstellen
$verbindung = new mysqli($host, $benutzer, $passwort, $datenbank);

if ($verbindung->connect_error) {
    die('Verbindungsfehler: ' . $verbindung->connect_error);
}

// Zeichensatz auf UTF-8 setzen
$verbindung->set_charset('utf8mb4');
