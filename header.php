<?php
// Session starten, falls noch keine existiert
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prüfen, ob ein Benutzer eingeloggt ist
$istEingeloggt = isset($_SESSION['benutzer_id']);

// Wenn eingeloggt, können wir Name und Rolle aus der Session lesen
$aktuellerBenutzername = $istEingeloggt ? $_SESSION['benutzername'] : null;
$aktuellerRolle        = $istEingeloggt ? $_SESSION['rolle'] : null;

// Aktuelle Datei ermitteln, z.B. "index.php", "login.php", "register.php"
$aktuelleSeite = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="utf-8">
    <title>Anime-Forum</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Google-Fonts für Anime-Style -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Mochiy+Pop+One&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>


<body>
    <header>
        <h1>Anime-Forum</h1>

        <nav>
            <?php if ($istEingeloggt): ?>
                <!-- Bereich für eingeloggte Nutzer -->

                <span id="B_Name">
                    <strong><?php echo htmlspecialchars($aktuellerBenutzername); ?></strong>
                </span>
                |

                <!-- Link 'Mein Profil' (wenn wir NICHT schon auf profil.php sind) -->
                <?php if ($aktuelleSeite === 'profil.php'): ?>
                    <span class="nav-aktiv">Mein Profil</span>
                <?php else: ?>
                    <a href="profil.php">Mein Profil</a>
                <?php endif; ?>
                |
                <!-- Startseite: wenn wir auf index.php sind, nicht klickbar -->
                <?php if ($aktuelleSeite === 'index.php'): ?>
                    <span class="nav-aktiv">Startseite</span>
                <?php else: ?>
                    <a href="index.php">Startseite</a>
                <?php endif; ?>
                |
                <!-- Logout -->
                <a href="logout.php"
                    class="<?php echo $aktuelleSeite === 'logout.php' ? 'nav-aktiv' : ''; ?>">
                    Logout
                </a>
                <!-- Optional: Link für Admins -->
                <?php if ($aktuellerRolle === 'Admin'): ?>
                    |
                    <?php if ($aktuelleSeite === 'admin.php'): ?>
                        <span class="nav-aktiv">Admin-Bereich</span>
                    <?php else: ?>
                        <a href="admin.php">Admin-Bereich</a>
                    <?php endif; ?>
                <?php endif; ?>


            <?php else: ?>
                <!-- Bereich für Gäste (nicht eingeloggt) -->
                <!-- Startseite -->
                <?php if ($aktuelleSeite === 'index.php'): ?>
                    <span class="nav-aktiv">Startseite</span>
                <?php else: ?>
                    <a href="index.php">Startseite</a>
                <?php endif; ?>
                |
                <!-- Registrieren: Link nur anzeigen, wenn wir NICHT auf register.php sind -->
                <?php if ($aktuelleSeite !== 'register.php'): ?>
                    <a href="register.php">Registrieren</a>
                <?php else: ?>
                    <span class="nav-aktiv">Registrieren</span>
                <?php endif; ?>
                |
                <!-- Login: Link nur anzeigen, wenn wir NICHT auf login.php sind -->
                <?php if ($aktuelleSeite !== 'login.php'): ?>
                    <a href="login.php">Login</a>
                <?php else: ?>
                    <span class="nav-aktiv">Login</span>
                <?php endif; ?>

            <?php endif; ?>
        </nav>

    </header>
    <hr>
    <hr>

    <!-- Hauptbereich: zentrierter Container -->
    <main class="container">