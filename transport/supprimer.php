<?php
// transport/supprimer.php : supprime un moyen de transport inutilisé
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$erreur = null;
$libelle = $_POST['libelle'] ?? '';
$libelle = is_string($libelle) ? trim($libelle) : '';
$retourVoyageur = ($_POST['retour'] ?? '') === 'voyageur';
$idVoyageur = $_POST['id'] ?? '';
if (!is_string($idVoyageur) || filter_var($idVoyageur, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 2147483647],
]) === false) {
    $idVoyageur = '';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $libelle === '' || mb_strlen($libelle) > 20) {
    $erreur = "Demande de suppression invalide.";
} else {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM personne WHERE FIND_IN_SET(:libelle, REPLACE(transport, ', ', ',')) > 0");
    $stmt->execute([':libelle' => $libelle]);
    if ((int)$stmt->fetchColumn() > 0) {
        $erreur = "Ce moyen de transport est utilisé par un étudiant et ne peut pas être supprimé.";
    } else {
        $stmt = $pdo->prepare('DELETE FROM moyen_transport WHERE libelle = :libelle');
        $stmt->execute([':libelle' => $libelle]);
        if ($stmt->rowCount() === 0) {
            $erreur = "Aucun moyen de transport correspondant n’a été trouvé.";
        }
    }
}

$base = '../';
$titre = 'Suppression d’un moyen de transport';
require __DIR__ . '/../includes/header.php';
if ($erreur !== null) {
    echo '<p class="erreur">' . h($erreur) . '</p>';
} else {
    echo '<p class="ok">Le moyen de transport ' . h($libelle) . ' a été supprimé.</p>';
}
?>
<p><a href="<?= $retourVoyageur ? '../personne/voiture.php?id=' . rawurlencode($idVoyageur) : 'liste.php' ?>">Retour à <?= $retourVoyageur ? 'la fiche de l’étudiant' : 'la liste des transports' ?></a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
