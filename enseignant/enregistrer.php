<?php
// enseignant/enregistrer.php : enregistre un enseignant dans MySQL
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: enseignant.php');
    exit;
}

$champPost = static function (string $nom): string {
    $valeur = $_POST[$nom] ?? '';
    return is_string($valeur) ? trim($valeur) : '';
};

$id = $champPost('id');
$numero = $champPost('numero');
$civilite = $champPost('civilite');
$nomPrenom = $champPost('nom_prenom');
$adresse = $champPost('adresse');
$dateNaissance = $champPost('date_naissance');
$lieuNaissance = $champPost('lieu_naissance');
$pays = $champPost('pays');
$grade = $champPost('grade');
$specialite = $champPost('specialite');
$erreurs = [];

if ($id !== '' && filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) === false) {
    $erreurs[] = "L'ID doit être un entier positif.";
}
if (!preg_match('/^[0-9]{1,10}$/', $numero)) {
    $erreurs[] = "Le numéro est obligatoire (10 chiffres maximum).";
}
if (!in_array($civilite, $CIVILITES, true)) {
    $erreurs[] = "La civilité est invalide.";
}
if ($nomPrenom === '' || mb_strlen($nomPrenom) > 80) {
    $erreurs[] = "Le nom / prénom est obligatoire (80 caractères maximum).";
}
if ($adresse === '' || mb_strlen($adresse) > 150) {
    $erreurs[] = "L'adresse est obligatoire (150 caractères maximum).";
}
$dateValide = DateTimeImmutable::createFromFormat('!Y-m-d', $dateNaissance);
$erreursDate = DateTimeImmutable::getLastErrors();
if (!$dateValide || $dateValide->format('Y-m-d') !== $dateNaissance || ($erreursDate !== false && ($erreursDate['warning_count'] > 0 || $erreursDate['error_count'] > 0))) {
    $erreurs[] = "La date de naissance est invalide.";
}
if ($lieuNaissance === '' || mb_strlen($lieuNaissance) > 80) {
    $erreurs[] = "Le lieu de naissance est obligatoire (80 caractères maximum).";
}
if (!in_array($pays, $PAYS, true)) {
    $erreurs[] = "Le pays est invalide.";
}
if (!in_array($grade, $GRADES_ENSEIGNANT, true)) {
    $erreurs[] = "Le grade est invalide.";
}
if (!in_array($specialite, $SPECIALITES_ENSEIGNANT, true)) {
    $erreurs[] = "La spécialité est invalide.";
}

if (!$erreurs) {
    try {
        $colonnesId = $id === '' ? '' : 'id, ';
        $valeursId = $id === '' ? '' : ':id, ';
        $stmt = $pdo->prepare(
            'INSERT INTO enseignant (' . $colonnesId . 'numero, civilite, nom_prenom, adresse, date_naissance, lieu_naissance, pays, grade, specialite)
             VALUES (' . $valeursId . ':numero, :civilite, :nom_prenom, :adresse, :date_naissance, :lieu_naissance, :pays, :grade, :specialite)'
        );
        $valeurs = [
            ':numero' => $numero,
            ':civilite' => $civilite,
            ':nom_prenom' => $nomPrenom,
            ':adresse' => $adresse,
            ':date_naissance' => $dateNaissance,
            ':lieu_naissance' => $lieuNaissance,
            ':pays' => $pays,
            ':grade' => $grade,
            ':specialite' => $specialite,
        ];
        if ($id !== '') {
            $valeurs[':id'] = (int)$id;
        }
        $stmt->execute($valeurs);
        $idEnregistre = $id !== '' ? (int)$id : (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $erreurs[] = "Cet ID existe déjà.";
        } else {
            $erreurs[] = "Erreur de base de données.";
        }
    }
}

$base  = '../';
$titre = 'Enregistrement enseignant';
require __DIR__ . '/../includes/header.php';
if ($erreurs) {
    foreach ($erreurs as $erreur) {
        echo '<p class="erreur">' . h($erreur) . '</p>';
    }
    echo '<p><a href="enseignant.php">Retour au formulaire</a></p>';
} else {
    echo '<p class="ok">Enseignant enregistré avec l’ID ' . h($idEnregistre) . '.</p>';
    echo '<p><a href="enseignant.php">Ajouter un autre</a> | <a href="liste.php">Affichage Liste</a></p>';
}
require __DIR__ . '/../includes/footer.php';
