<?php
// Datenbankverbindung einbinden
require_once 'db.php';

// Kategorien aus der Datenbank holen
$sql = "SELECT kategorie_id, name, beschreibung FROM kategorien";
$ergebnis = $verbindung->query($sql);

//Meldung nach Konto-Löschung
$infomeldung = '';
if (isset($_GET['konto_geloescht']) && $_GET['konto_geloescht'] === '1') {
    $infomeldung = 'Dein Konto wurde erfolgreich gelöscht.';
}
?>



<?php require 'header.php'; ?>

<div class="page-box">
    <h2>Willkommen in meinem Anime-Forum!</h2>
    <p>Diskutiere mit anderen Anime-Fans über deine Lieblingsserien, Charaktere und vieles mehr!</p>

    <h3>Kategorien</h3>
    <ul>
        <?php if ($ergebnis && $ergebnis->num_rows > 0): ?>
            <?php while ($zeile = $ergebnis->fetch_assoc()): ?>
                <li>
                    <div class="card">
                        <!-- Link zur Kategorie-Seite mit Übergabe der kategorie_id -->
                        <a href="kategorie.php?id=<?php echo (int)$zeile['kategorie_id']; ?>">
                            <strong><?php echo htmlspecialchars($zeile['name']); ?></strong>
                        </a>
                        <br>
                        <small><?php echo htmlspecialchars($zeile['beschreibung']); ?></small>
                    </div>
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li>Es sind noch keine Kategorien vorhanden.</li>
        <?php endif; ?>
    </ul>
    <!-- Anzeige der Infomeldung nach Konto-Löschung   --> 
    <?php if ($infomeldung !== ''): ?>
        <!-- Erfolgsmeldung nach Konto-Löschung -->
        <p class="msg-info"><?php echo htmlspecialchars($infomeldung); ?></p>
    <?php endif; ?>
</div>

<?php require 'footer.php'; ?>