<?php
// personne/liste.php : bouton "Affichage Liste" - affiche les personnes enregistrées
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

// JOIN : on récupère aussi le libellé de la nationalité
$sql = 'SELECT p.*, n.libelle AS nationalite
        FROM personne p
        LEFT JOIN nationalite n ON n.code = p.nationalite_code
        ORDER BY p.id';
$stmt = $pdo->prepare($sql);
$stmt->execute();
$personnes = $stmt->fetchAll();

$base  = '../';
$titre = 'Liste des personnes';
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$personnes): ?>
    <p>Aucune personne enregistrée.</p>
<?php else: ?>
    <table class="liste">
        <tr>
            <th>N°</th><th>Civilité</th><th>Nom / Prénom</th><th>Adresse</th><th>CP / Localité</th>
            <th>Pays</th><th>Plateformes</th><th>Applications</th><th>Nationalité</th><th>Photo</th>
        </tr>
        <?php foreach ($personnes as $p): ?>
            <tr>
                <td><?= h($p['numero']) ?></td>
                <td><?= h($p['civilite']) ?></td>
                <td><?= h($p['nom_prenom']) ?></td>
                <td><?= h($p['adresse']) ?></td>
                <td><?= h($p['code_postal']) ?> <?= h($p['localite']) ?></td>
                <td><?= h($p['pays']) ?></td>
                <td><?= h($p['plateformes']) ?></td>
                <td><?= h($p['applications']) ?></td>
                <td><?= h($p['nationalite']) ?></td>
                <td>
                    <?php if ($p['photo']): ?>
                        <img src="../uploads/<?= h($p['photo']) ?>" width="60" alt="photo">
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<p><a href="formulaire.php">Nouvelle personne</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
