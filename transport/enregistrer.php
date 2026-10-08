<?php
// transport/enregistrer.php : enregistre un moyen de transport
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: transport.php');
    exit;
}

$libelle = $_POST['libelle'] ?? '';
$libelle = is_string($libelle) ? trim($libelle) : '';
$erreurs = [];

if ($libelle === '' || mb_strlen($libelle) > 20) {
    $erreurs[] = "Le moyen de transport est obligatoire (20 caractères maximum).";
}

if (!$erreurs) {
    try {
        $stmt = $pdo->prepare('INSERT INTO moyen_transport (libelle) VALUES (:libelle)');
        $stmt->execute([':libelle' => $libelle]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $erreurs[] = "Ce moyen de transport existe déjà.";
        } else {
            $erreurs[] = "Erreur de base de données.";
        }
    }
}

$base = '../';
$titre = 'Enregistrement du moyen de transport';
require __DIR__ . '/../includes/header.php';
if ($erreurs) {
    foreach ($erreurs as $erreur) {
        echo '<p class="erreur">' . h($erreur) . '</p>';
    }
    echo '<p><a href="transport.php">Retour</a></p>';
} else {
    echo '<p class="ok">Moyen de transport enregistré : ' . h($libelle) . '</p>';
    echo '<p><a href="transport.php">Ajouter un autre</a> | <a href="liste.php">Affichage Liste</a></p>';
}
require __DIR__ . '/../includes/footer.php';
