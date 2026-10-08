<?php
// transport/liste.php : affiche les moyens de transport enregistrés
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare('SELECT libelle FROM moyen_transport ORDER BY libelle');
$stmt->execute();
$transports = $stmt->fetchAll(PDO::FETCH_COLUMN);

$base = '../';
$titre = 'Liste des moyens de transport';
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$transports): ?>
    <p>Aucun moyen de transport enregistré.</p>
<?php else: ?>
    <table class="liste">
        <tr><th>Moyen de transport</th></tr>
        <?php foreach ($transports as $transport): ?>
            <tr><td><?= h($transport) ?></td></tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<p><a href="transport.php">Ajouter un moyen de transport</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
