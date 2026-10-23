<?php
// personne/voyageur_supprimer.php : supprime un voyageur existant
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$erreur = null;
$supprime = false;
$id = $_POST['id'] ?? '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $erreur = 'La suppression doit être demandée depuis la fiche ou la liste des voyageurs.';
} elseif ((!is_string($id) && !is_int($id)) || filter_var((string)$id, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 2147483647],
]) === false) {
    $erreur = "Identifiant invalide : le voyageur n'a pas été supprimé.";
} else {
    try {
        $stmt = $pdo->prepare('DELETE FROM voyageur WHERE id = :id');
        $stmt->execute([':id' => (int)$id]);
        if ($stmt->rowCount() === 1) {
            $supprime = true;
        } else {
            $erreur = "Aucun voyageur trouvé pour cet identifiant.";
        }
    } catch (PDOException $e) {
        $erreur = "Échec de la suppression du voyageur.";
    }
}

$base = '../';
$titre = 'Suppression d’un voyageur';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($supprime): ?>
    <p class="ok">Le voyageur ID <?= h($id) ?> a été supprimé.</p>
<?php else: ?>
    <p class="erreur"><?= h($erreur) ?></p>
<?php endif; ?>
<p><a href="voyageur_liste.php">Retour à la liste des voyageurs</a> | <a href="voyageurs.php">Gestion des voyageurs</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
