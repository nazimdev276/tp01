<?php
// nationalite/enregistrer.php : insère une nationalité dans la table `nationalite`
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: nationalite.php');
    exit;
}

// 1) Récupération (trim enlève les espaces, strtoupper met en majuscules)
$code    = strtoupper(trim($_POST['code'] ?? ''));
$libelle = strtoupper(trim($_POST['libelle'] ?? ''));

// 2) Validation
$erreurs = [];
if ($code === '' || mb_strlen($code) > 3) {
    $erreurs[] = "Le code est obligatoire (3 caractères maximum).";
}
if ($libelle === '' || mb_strlen($libelle) > 40) {
    $erreurs[] = "La nationalité est obligatoire (40 caractères maximum).";
}

// 3) Insertion avec requête préparée
if (!$erreurs) {
    try {
        $stmt = $pdo->prepare('INSERT INTO nationalite (code, libelle) VALUES (:code, :libelle)');
        $stmt->execute([':code' => $code, ':libelle' => $libelle]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {      // 23000 = clé primaire déjà existante
            $erreurs[] = "Ce code existe déjà.";
        } else {
            $erreurs[] = "Erreur de base de données.";
        }
    }
}

$base  = '../';
$titre = 'Enregistrement nationalité';
require __DIR__ . '/../includes/header.php';

if ($erreurs) {
    foreach ($erreurs as $e) {
        echo '<p class="erreur">' . h($e) . '</p>';
    }
    echo '<p><a href="nationalite.php">Retour</a></p>';
} else {
    echo '<p class="ok">Nationalité enregistrée : ' . h($code) . ' | ' . h($libelle) . '</p>';
    echo '<p><a href="nationalite.php">Ajouter une autre</a> | <a href="liste.php">Affichage Liste</a></p>';
}
require __DIR__ . '/../includes/footer.php';
