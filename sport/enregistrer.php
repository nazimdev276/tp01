<?php
// sport/enregistrer.php : enregistre un sport
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: sport.php');
    exit;
}

$libelle = $_POST['libelle'] ?? '';
$libelle = is_string($libelle) ? trim($libelle) : '';
$erreurs = [];

if ($libelle === '' || mb_strlen($libelle) > 40) {
    $erreurs[] = "Le sport est obligatoire (40 caractères maximum).";
}

if (!$erreurs) {
    try {
        $stmt = $pdo->prepare('INSERT INTO sport (libelle) VALUES (:libelle)');
        $stmt->execute([':libelle' => $libelle]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $erreurs[] = "Ce sport existe déjà.";
        } else {
            $erreurs[] = "Erreur de base de données.";
        }
    }
}

$base = '../';
$titre = 'Enregistrement du sport';
require __DIR__ . '/../includes/header.php';
if ($erreurs) {
    foreach ($erreurs as $erreur) {
        echo '<p class="erreur">' . h($erreur) . '</p>';
    }
    echo '<p><a href="sport.php">Retour</a></p>';
} else {
    echo '<p class="ok">Sport enregistré : ' . h($libelle) . '</p>';
    echo '<p><a href="sport.php">Ajouter un autre</a> | <a href="liste.php">Affichage Liste</a></p>';
}
require __DIR__ . '/../includes/footer.php';
