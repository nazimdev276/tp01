<?php
// filiere/liste.php : affiche toutes les filières lues dans MySQL
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare('SELECT code, libelle FROM filiere ORDER BY libelle');
$stmt->execute();
$filieres = $stmt->fetchAll();

$base  = '../';
$titre = 'Liste des filières';
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$filieres): ?>
    <p>Aucune filière enregistrée.</p>
<?php else: ?>
    <table class="liste">
        <tr><th>Code</th><th>Filière</th><th>Action</th></tr>
        <?php foreach ($filieres as $f): ?>
            <tr>
                <td><?= h($f['code']) ?></td>
                <td><?= h($f['libelle']) ?></td>
                <td><a href="filiere.php?code=<?= rawurlencode($f['code']) ?>">Modifier</a></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<p><a href="filiere.php">Ajouter une filière</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
