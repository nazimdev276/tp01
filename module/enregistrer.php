<?php
// module/enregistrer.php : enregistre un module dans MySQL
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: module.php');
    exit;
}

$champPost = static function (string $nom): string {
    $valeur = $_POST[$nom] ?? '';
    return is_string($valeur) ? trim($valeur) : '';
};

$id = $champPost('id');
$code = strtoupper($champPost('code'));
$designation = $champPost('designation');
$coefficient = $champPost('coefficient');
$volumeHoraire = $champPost('volume_horaire');
$filiereCode = $champPost('filiere');
$erreurs = [];

if ($id !== '' && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false) {
    $erreurs[] = "L'ID doit être un entier positif.";
}
if ($code === '' || mb_strlen($code) > 20) {
    $erreurs[] = "Le code module est obligatoire (20 caractères maximum).";
}
if ($designation === '' || mb_strlen($designation) > 100) {
    $erreurs[] = "La désignation est obligatoire (100 caractères maximum).";
}
if (!preg_match('/^(?:0|[1-9][0-9]{0,2})(?:\.[0-9]{1,2})?$/', $coefficient) || (float)$coefficient <= 0 || (float)$coefficient > 999.99) {
    $erreurs[] = "Le coefficient doit être supérieur à 0 et inférieur ou égal à 999,99.";
}
if (!preg_match('/^(0|[1-9][0-9]{0,4})$/', $volumeHoraire) || (int)$volumeHoraire > 65535) {
    $erreurs[] = "Le volume horaire doit être un entier compris entre 0 et 65535.";
}

$stmt = $pdo->prepare('SELECT code FROM filiere');
$stmt->execute();
$codesFilieres = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($filiereCode, $codesFilieres, true)) {
    $erreurs[] = "La filière sélectionnée est invalide.";
}

if (!$erreurs) {
    try {
        $colonnesId = $id === '' ? '' : 'id, ';
        $valeursId = $id === '' ? '' : ':id, ';
        $stmt = $pdo->prepare(
            'INSERT INTO module (' . $colonnesId . 'code, designation, coefficient, volume_horaire, filiere_code)
             VALUES (' . $valeursId . ':code, :designation, :coefficient, :volume_horaire, :filiere)'
        );
        $valeurs = [
            ':code' => $code,
            ':designation' => $designation,
            ':coefficient' => $coefficient,
            ':volume_horaire' => (int)$volumeHoraire,
            ':filiere' => $filiereCode,
        ];
        if ($id !== '') {
            $valeurs[':id'] = (int)$id;
        }
        $stmt->execute($valeurs);
        $idEnregistre = $id !== '' ? (int)$id : (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $erreurs[] = "Cet ID ou ce code module existe déjà.";
        } else {
            $erreurs[] = "Erreur de base de données.";
        }
    }
}

$base  = '../';
$titre = 'Enregistrement module';
require __DIR__ . '/../includes/header.php';
if ($erreurs) {
    foreach ($erreurs as $erreur) {
        echo '<p class="erreur">' . h($erreur) . '</p>';
    }
    echo '<p><a href="module.php">Retour au formulaire</a></p>';
} else {
    echo '<p class="ok">Module enregistré avec l’ID ' . h($idEnregistre) . '.</p>';
    echo '<p><a href="module.php">Ajouter un autre</a> | <a href="liste.php">Affichage Liste</a></p>';
}
require __DIR__ . '/../includes/footer.php';
