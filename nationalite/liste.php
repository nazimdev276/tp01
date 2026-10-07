<?php
// nationalite/liste.php : affiche toutes les nationalités lues dans MySQL
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare('SELECT code, libelle FROM nationalite ORDER BY libelle');
$stmt->execute();
$nationalites = $stmt->fetchAll();

$base  = '../';
$titre = 'Liste des nationalités';
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$nationalites): ?>
    <p>Aucune nationalité enregistrée.</p>
<?php else: ?>
    <table class="liste">
        <tr><th>Code</th><th>Nationalité</th></tr>
        <?php foreach ($nationalites as $n): ?>
            <tr>
                <td><?= h($n['code']) ?></td>
                <td><?= h($n['libelle']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<p><a href="nationalite.php">Ajouter une nationalité</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
