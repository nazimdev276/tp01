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
$modules      = post_liste('modules', $MODULES);
$applications = post_liste('applications', $APPLICATIONS);
$voitures     = post_liste('voiture', $VOITURES);
$activites    = post_liste('activites', $ACTIVITES);
$typeFoyer = $_POST['type_foyer'] ?? 'Individuelle';
if (!is_string($typeFoyer) || !in_array($typeFoyer, $TYPES_FOYER, true)) {
    $typeFoyer = '';
}
$stmt = $pdo->prepare('SELECT libelle FROM moyen_transport');
$stmt->execute();
$transports = $stmt->fetchAll(PDO::FETCH_COLUMN);
$transportsPostes = $_POST['transport'] ?? [];
$transportsSelectionnes = [];
if (is_array($transportsPostes)) {
    foreach ($transportsPostes as $transportOption) {
        if (is_string($transportOption) && in_array($transportOption, $transports, true)) {
            $transportsSelectionnes[] = $transportOption;
        }
    }
}
$description = $_POST['description'] ?? '';
$description = is_string($description) ? trim($description) : '';
$groupeSanguin = $_POST['groupe_sanguin'] ?? '';
if (!is_string($groupeSanguin) || !in_array($groupeSanguin, $GROUPES_SANGUINS, true)) {
    $groupeSanguin = '';
}
$sport = $_POST['sport'] ?? '';
$sport = is_string($sport) ? $sport : '';
if ($sport !== '') {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM sport WHERE libelle = :libelle');
    $stmt->execute([':libelle' => $sport]);
    if ($stmt->fetchColumn() == 0) {
        $sport = '';
    }
}

// On retrouve le libellé de la nationalité à partir de son code
$libelleNat = '';
$stmt = $pdo->prepare('SELECT libelle FROM nationalite WHERE code = :code');
$stmt->execute([':code' => $_POST['nationalite'] ?? '']);
$ligne = $stmt->fetch();
if ($ligne) {
    $libelleNat = $ligne['libelle'];
}

$libelleFiliere = '';
$filiereCode = $_POST['filiere'] ?? '';
if (is_string($filiereCode)) {
    $stmt = $pdo->prepare('SELECT libelle FROM filiere WHERE code = :code');
    $stmt->execute([':code' => $filiereCode]);
    $ligne = $stmt->fetch();
    if ($ligne) {
        $libelleFiliere = $ligne['libelle'];
    }
}
?>
<table class="liste">
    <tr><td>Numéro</td><td><?= h($_POST['numero'] ?? '') ?></td></tr>
    <tr><td>Type de foyer</td><td><?= h($typeFoyer) ?></td></tr>
    <tr><td>Civilité</td><td><?= h($_POST['civilite'] ?? '') ?></td></tr>
    <tr><td>Nom / Prénom</td><td><?= h($_POST['nom_prenom'] ?? '') ?></td></tr>
    <tr><td>Adresse</td><td><?= h($_POST['adresse'] ?? '') ?></td></tr>
    <tr><td>No postal / Localité</td><td><?= h($_POST['code_postal'] ?? '') ?> <?= h($_POST['localite'] ?? '') ?></td></tr>
    <tr><td>Pays</td><td><?= h($_POST['pays'] ?? '') ?></td></tr>
    <tr><td>Filière d’inscription</td><td><?= h($libelleFiliere) ?></td></tr>
    <tr><td>Plateforme(s)</td><td><?= h(implode(', ', $plateformes)) ?></td></tr>
    <tr><td>Module(s)</td><td><?= h(implode(', ', $modules)) ?></td></tr>
    <tr><td>Application(s)</td><td><?= h(implode(', ', $applications)) ?></td></tr>
    <tr><td>Voiture(s)</td><td><?= h(implode(', ', $voitures)) ?></td></tr>
    <tr><td>Moyen(x) de transport</td><td><?= h(implode(', ', $transportsSelectionnes)) ?></td></tr>
    <tr><td>Description</td><td><?= h($description) ?></td></tr>
    <tr><td>Groupe sanguin</td><td><?= h($groupeSanguin) ?></td></tr>
    <tr><td>Sport</td><td><?= h($sport) ?></td></tr>
    <tr><td>Activité(s)</td><td><?= h(implode(', ', $activites)) ?></td></tr>
    <tr><td>Nationalité</td><td><?= h($libelleNat) ?> (<?= h($_POST['nationalite'] ?? '') ?>)</td></tr>
</table>
<p><a href="formulaire.php">Retour au formulaire</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
