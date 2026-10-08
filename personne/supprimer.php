<?php
// personne/supprimer.php : supprime une personne existante
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$erreur = null;
$supprime = false;
$id = $_POST['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $erreur = "La suppression doit être demandée depuis la liste des personnes.";
} elseif ((!is_string($id) && !is_int($id)) || filter_var((string)$id, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 2147483647],
]) === false) {
    $erreur = "Identifiant invalide : la personne n'a pas été supprimée.";
} else {
    try {
        $stmt = $pdo->prepare('DELETE FROM personne WHERE id = :id');
        $stmt->execute([':id' => (int)$id]);
        if ($stmt->rowCount() === 1) {
            $supprime = true;
        } else {
            $erreur = "Aucune personne trouvée pour cet identifiant.";
        }
    } catch (PDOException $e) {
        $erreur = "Échec de la suppression de la personne.";
    }
}

$base = '../';
$titre = 'Suppression d’une personne';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($supprime): ?>
    <p class="ok">La personne ID <?= h($id) ?> a été supprimée.</p>
<?php else: ?>
    <p class="erreur"><?= h($erreur) ?></p>
<?php endif; ?>
<p><a href="liste.php">Retour à la liste</a> | <a href="formulaire.php">Nouvelle personne</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
