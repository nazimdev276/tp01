<?php
// module/liste.php : affiche les modules enregistrés
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare(
    'SELECT m.id, m.code, m.designation, m.coefficient, m.volume_horaire, f.libelle AS filiere
     FROM module m
     JOIN filiere f ON f.code = m.filiere_code
     ORDER BY m.id'
);
$stmt->execute();
$modules = $stmt->fetchAll();

$base  = '../';
$titre = 'Liste des modules';
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$modules): ?>
    <p>Aucun module enregistré.</p>
<?php else: ?>
    <table class="liste">
        <tr>
            <th>Id</th><th>Code module</th><th>Désignation module</th>
            <th>Coefficient</th><th>Volume horaire</th><th>Filière</th>
        </tr>
        <?php foreach ($modules as $module): ?>
            <tr>
                <td><?= h($module['id']) ?></td>
                <td><?= h($module['code']) ?></td>
                <td><?= h($module['designation']) ?></td>
                <td><?= h($module['coefficient']) ?></td>
                <td><?= h($module['volume_horaire']) ?></td>
                <td><?= h($module['filiere']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<p><a href="module.php">Ajouter un module</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
