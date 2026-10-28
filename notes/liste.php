<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare(
    'SELECT Num_Etudiant, Filiere, Nom_module, code_module, coefficient, `Note`
     FROM Notes
     ORDER BY Num_Etudiant, Nom_module'
);
$stmt->execute();
$notes = $stmt->fetchAll();

$base = '../';
$titre = 'Liste des notes';
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$notes): ?>
    <p>Aucune note enregistrée.</p>
<?php else: ?>
    <div class="table-scroll">
        <table class="liste">
            <thead>
                <tr>
                    <th>ID étudiant</th><th>Filière</th><th>Module</th>
                    <th>Code module</th><th>Coefficient</th><th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($notes as $note): ?>
                    <tr>
                        <td><?= h($note['Num_Etudiant']) ?></td>
                        <td><?= h($note['Filiere']) ?></td>
                        <td><?= h($note['Nom_module']) ?></td>
                        <td><?= h($note['code_module']) ?></td>
                        <td><?= h($note['coefficient']) ?></td>
                        <td><?= h($note['Note']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<p><a class="button-link" href="bulletin.php">Retour à la saisie</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
