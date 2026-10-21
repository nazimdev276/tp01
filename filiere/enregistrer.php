<?php
// filiere/enregistrer.php : insère une filière dans la table `filiere`
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: filiere.php');
    exit;
}

$code    = strtoupper(trim($_POST['code'] ?? ''));
$libelle = strtoupper(trim($_POST['libelle'] ?? ''));
$erreurs = [];

if ($code === '' || mb_strlen($code) > 10) {
    $erreurs[] = "Le code est obligatoire (10 caractères maximum).";
}
if ($libelle === '' || mb_strlen($libelle) > 10) {
    $erreurs[] = "La filière est obligatoire (10 caractères maximum).";
}

if (!$erreurs) {
    try {
        $stmt = $pdo->prepare('INSERT INTO filiere (code, libelle) VALUES (:code, :libelle)');
        $stmt->execute([':code' => $code, ':libelle' => $libelle]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $erreurs[] = "Ce code existe déjà.";
        } else {
            $erreurs[] = "Erreur de base de données.";
        }
    }
}

$base  = '../';
$titre = 'Enregistrement filière';
require __DIR__ . '/../includes/header.php';

if ($erreurs) {
    foreach ($erreurs as $e) {
        echo '<p class="erreur">' . h($e) . '</p>';
    }
    echo '<p><a href="filiere.php">Retour</a></p>';
} else {
    echo '<p class="ok">Filière enregistrée : ' . h($code) . ' | ' . h($libelle) . '</p>';
    echo '<p><a href="filiere.php">Ajouter une autre</a> | <a href="liste.php">Affichage Liste</a></p>';
}
require __DIR__ . '/../includes/footer.php';
