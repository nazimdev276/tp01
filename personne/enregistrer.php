<?php
// personne/enregistrer.php : valide puis enregistre une personne dans MySQL
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: formulaire.php');
    exit;
}

// 1) Récupération des champs
$numero     = trim($_POST['numero'] ?? '');
$civilite   = $_POST['civilite'] ?? '';
$nomPrenom  = trim($_POST['nom_prenom'] ?? '');
$adresse    = trim($_POST['adresse'] ?? '');
$codePostal = trim($_POST['code_postal'] ?? '');
$localite   = trim($_POST['localite'] ?? '');
$pays       = $_POST['pays'] ?? '';
$natCode    = $_POST['nationalite'] ?? '';
$plateformes  = post_liste('plateformes', $PLATEFORMES);
$applications = post_liste('applications', $APPLICATIONS);

// 2) Validation
$erreurs = [];
if (!preg_match('/^[0-9]{1,10}$/', $numero))            $erreurs[] = "Numéro invalide (chiffres uniquement).";
if (!in_array($civilite, $CIVILITES, true))              $erreurs[] = "Civilité invalide.";
if ($nomPrenom === '' || mb_strlen($nomPrenom) > 80)     $erreurs[] = "Nom / Prénom obligatoire (80 caractères max).";
if ($adresse === '' || mb_strlen($adresse) > 150)        $erreurs[] = "Adresse obligatoire (150 caractères max).";
if (!preg_match('/^[0-9]{4,10}$/', $codePostal))         $erreurs[] = "Code postal invalide.";
if ($localite === '' || mb_strlen($localite) > 60)       $erreurs[] = "Localité obligatoire (60 caractères max).";
if (!in_array($pays, $PAYS, true))                       $erreurs[] = "Pays invalide.";

// La nationalité doit exister dans la table nationalite
$stmt = $pdo->prepare('SELECT COUNT(*) FROM nationalite WHERE code = :code');
$stmt->execute([':code' => $natCode]);
if ($stmt->fetchColumn() == 0) {
    $erreurs[] = "Nationalité invalide.";
}

// 3) Image (facultative)
$photo = null;
if (!$erreurs) {
    [$photo, $erreurImage] = enregistrer_image($_FILES['photo'] ?? []);
    if ($erreurImage) $erreurs[] = $erreurImage;
}

// 4) Insertion avec requête préparée
if (!$erreurs) {
    try {
        $sql = 'INSERT INTO personne
                (numero, civilite, nom_prenom, adresse, code_postal, localite, pays,
                 plateformes, applications, nationalite_code, photo)
                VALUES
                (:numero, :civilite, :nom, :adresse, :cp, :localite, :pays,
                 :plateformes, :applications, :nat, :photo)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':numero'       => $numero,
            ':civilite'     => $civilite,
            ':nom'          => $nomPrenom,
            ':adresse'      => $adresse,
            ':cp'           => $codePostal,
            ':localite'     => $localite,
            ':pays'         => $pays,
            ':plateformes'  => implode(', ', $plateformes),   // ex: "Windows, Macintosh"
            ':applications' => implode(', ', $applications),  // ex: "Internet, Bureautique"
            ':nat'          => $natCode,
            ':photo'        => $photo,
        ]);
    } catch (PDOException $e) {
        $erreurs[] = "Erreur de base de données.";
    }
}

$base  = '../';
$titre = 'Enregistrement personne';
require __DIR__ . '/../includes/header.php';

if ($erreurs) {
    foreach ($erreurs as $e) echo '<p class="erreur">' . h($e) . '</p>';
    echo '<p><a href="javascript:history.back()">Retour au formulaire</a></p>';
} else {
    echo '<p class="ok">Personne enregistrée : ' . h($nomPrenom) . '</p>';
    echo '<p><a href="formulaire.php">Nouvelle personne</a> | <a href="liste.php">Affichage Liste</a></p>';
}
require __DIR__ . '/../includes/footer.php';
