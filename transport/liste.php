<?php
// transport/liste.php : affiche les moyens de transport enregistrés
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare('SELECT libelle, description FROM moyen_transport ORDER BY libelle');
$stmt->execute();
$transports = $stmt->fetchAll();

$base = '../';
$titre = 'Liste des moyens de transport';
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$transports): ?>
    <p>Aucun moyen de transport enregistré.</p>
<?php else: ?>
    <table class="liste">
        <tr><th>Moyen de transport</th><th>Description</th><th>Action</th></tr>
        <?php foreach ($transports as $transport): ?>
            <tr>
                <td><?= h($transport['libelle']) ?></td>
                <td><?= h($transport['description']) ?></td>
                <td>
                    <form method="post" action="supprimer.php" onsubmit="return confirm('Supprimer ce moyen de transport ?');">
                        <input type="hidden" name="libelle" value="<?= h($transport['libelle']) ?>">
                        <button type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<p><a href="transport.php">Ajouter un moyen de transport</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
