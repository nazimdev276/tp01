<?php
// personne/afficher_php.php : bouton "Affichage PHP"
// Affiche simplement les valeurs reçues du formulaire (rien n'est enregistré).
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

$base  = '../';
$titre = 'Affichage PHP';
require __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<p>Aucune donnée reçue. <a href="formulaire.php">Retour au formulaire</a></p>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$plateformes  = post_liste('plateformes', $PLATEFORMES);
$applications = post_liste('applications', $APPLICATIONS);

// On retrouve le libellé de la nationalité à partir de son code
$libelleNat = '';
$stmt = $pdo->prepare('SELECT libelle FROM nationalite WHERE code = :code');
$stmt->execute([':code' => $_POST['nationalite'] ?? '']);
$ligne = $stmt->fetch();
if ($ligne) {
    $libelleNat = $ligne['libelle'];
}
?>
<table class="liste">
    <tr><td>Numéro</td><td><?= h($_POST['numero'] ?? '') ?></td></tr>
    <tr><td>Civilité</td><td><?= h($_POST['civilite'] ?? '') ?></td></tr>
    <tr><td>Nom / Prénom</td><td><?= h($_POST['nom_prenom'] ?? '') ?></td></tr>
    <tr><td>Adresse</td><td><?= h($_POST['adresse'] ?? '') ?></td></tr>
    <tr><td>No postal / Localité</td><td><?= h($_POST['code_postal'] ?? '') ?> <?= h($_POST['localite'] ?? '') ?></td></tr>
    <tr><td>Pays</td><td><?= h($_POST['pays'] ?? '') ?></td></tr>
    <tr><td>Plateforme(s)</td><td><?= h(implode(', ', $plateformes)) ?></td></tr>
    <tr><td>Application(s)</td><td><?= h(implode(', ', $applications)) ?></td></tr>
    <tr><td>Nationalité</td><td><?= h($libelleNat) ?> (<?= h($_POST['nationalite'] ?? '') ?>)</td></tr>
</table>
<p><a href="formulaire.php">Retour au formulaire</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
