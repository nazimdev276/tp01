<?php
// sport/liste.php : affiche les sports enregistrés
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare('SELECT libelle FROM sport ORDER BY libelle');
$stmt->execute();
$sports = $stmt->fetchAll(PDO::FETCH_COLUMN);

$base = '../';
$titre = 'Liste des sports';
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$sports): ?>
    <p>Aucun sport enregistré.</p>
<?php else: ?>
    <table class="liste">
        <tr><th>Sport</th></tr>
        <?php foreach ($sports as $sport): ?>
            <tr><td><?= h($sport) ?></td></tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<p><a href="sport.php">Ajouter un sport</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
