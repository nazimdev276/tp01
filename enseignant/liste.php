<?php
// enseignant/liste.php : affiche les enseignants enregistrés
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare('SELECT id, numero, civilite, nom_prenom, adresse, date_naissance, lieu_naissance, pays, grade, specialite FROM enseignant ORDER BY id');
$stmt->execute();
$enseignants = $stmt->fetchAll();

$base  = '../';
$titre = 'Liste des enseignants';
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$enseignants): ?>
    <p>Aucun enseignant enregistré.</p>
<?php else: ?>
    <table class="liste">
        <tr>
            <th>Id</th><th>Numéro</th><th>Civilité</th><th>Nom / Prénom</th><th>Adresse</th>
            <th>Date de naissance</th><th>Lieu de naissance</th><th>Pays</th><th>Grade</th><th>Spécialité</th>
        </tr>
        <?php foreach ($enseignants as $enseignant): ?>
            <tr>
                <td><?= h($enseignant['id']) ?></td>
                <td><?= h($enseignant['numero']) ?></td>
                <td><?= h($enseignant['civilite']) ?></td>
                <td><?= h($enseignant['nom_prenom']) ?></td>
                <td><?= h($enseignant['adresse']) ?></td>
                <td><?= h($enseignant['date_naissance']) ?></td>
                <td><?= h($enseignant['lieu_naissance']) ?></td>
                <td><?= h($enseignant['pays']) ?></td>
                <td><?= h($enseignant['grade']) ?></td>
                <td><?= h($enseignant['specialite']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<p><a href="enseignant.php">Ajouter un enseignant</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
