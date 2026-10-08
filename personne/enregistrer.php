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
$transport  = $_POST['transport'] ?? '';
$idFormulaire = $_POST['id'] ?? '';
$plateformes  = post_liste('plateformes', $PLATEFORMES);
$applications = post_liste('applications', $APPLICATIONS);
$activites    = post_liste('activites', $ACTIVITES);
$idPersonne = null;
$personneExistante = null;
$succes = false;

$stmt = $pdo->prepare('SELECT libelle FROM moyen_transport');
$stmt->execute();
$transports = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 2) Validation
$erreurs = [];
if ($idFormulaire !== '') {
    if ((!is_string($idFormulaire) && !is_int($idFormulaire)) || filter_var((string)$idFormulaire, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]) === false) {
        $erreurs[] = "Identifiant invalide.";
    } else {
        $idPersonne = (int)$idFormulaire;
        $stmt = $pdo->prepare('SELECT id, photo FROM personne WHERE id = :id');
        $stmt->execute([':id' => $idPersonne]);
        $personneExistante = $stmt->fetch() ?: null;
    }
}
if (!preg_match('/^[0-9]{1,10}$/', $numero))            $erreurs[] = "Numéro invalide (chiffres uniquement).";
if (!in_array($civilite, $CIVILITES, true))              $erreurs[] = "Civilité invalide.";
if ($nomPrenom === '' || mb_strlen($nomPrenom) > 80)     $erreurs[] = "Nom / Prénom obligatoire (80 caractères max).";
if ($adresse === '' || mb_strlen($adresse) > 150)        $erreurs[] = "Adresse obligatoire (150 caractères max).";
if (!preg_match('/^[0-9]{4,10}$/', $codePostal))         $erreurs[] = "Code postal invalide.";
if ($localite === '' || mb_strlen($localite) > 60)       $erreurs[] = "Localité obligatoire (60 caractères max).";
if (!in_array($pays, $PAYS, true))                       $erreurs[] = "Pays invalide.";
if (!is_string($transport) || !in_array($transport, $transports, true)) $erreurs[] = "Moyen de transport invalide.";

// La nationalité doit exister dans la table nationalite
$stmt = $pdo->prepare('SELECT COUNT(*) FROM nationalite WHERE code = :code');
$stmt->execute([':code' => $natCode]);
if ($stmt->fetchColumn() == 0) {
    $erreurs[] = "Nationalité invalide.";
}

// 3) Image (facultative)
$photo = $personneExistante['photo'] ?? null;
if (!$erreurs) {
    [$nouvellePhoto, $erreurImage] = enregistrer_image($_FILES['photo'] ?? []);
    if ($erreurImage) {
        $erreurs[] = $erreurImage;
    } elseif ($nouvellePhoto !== null) {
        $photo = $nouvellePhoto;
    }
}

// 4) Mise à jour si l'ID existe, sinon création avec l'ID demandé
if (!$erreurs) {
    try {
        $valeurs = [
            ':numero'       => $numero,
            ':civilite'     => $civilite,
            ':nom'          => $nomPrenom,
            ':adresse'      => $adresse,
            ':cp'           => $codePostal,
            ':localite'     => $localite,
            ':pays'         => $pays,
            ':plateformes'  => implode(', ', $plateformes),   // ex: "Windows, Macintosh"
            ':applications' => implode(', ', $applications),  // ex: "Internet, Bureautique"
            ':transport'    => $transport,
            ':activites'    => implode(', ', $activites),
            ':nat'          => $natCode,
            ':photo'        => $photo,
        ];

        if ($idPersonne !== null && $personneExistante) {
            $sql = 'UPDATE personne
                    SET numero = :numero, civilite = :civilite, nom_prenom = :nom,
                        adresse = :adresse, code_postal = :cp, localite = :localite, pays = :pays,
                        plateformes = :plateformes, applications = :applications,
                        transport = :transport, activites = :activites,
                        nationalite_code = :nat, photo = :photo
                    WHERE id = :id';
            $valeurs[':id'] = $idPersonne;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($valeurs);
        } else {
            $colonnes = $idPersonne === null ? '' : 'id, ';
            $parametreId = $idPersonne === null ? '' : ':id, ';
            $sql = 'INSERT INTO personne
                    (' . $colonnes . 'numero, civilite, nom_prenom, adresse, code_postal, localite, pays,
                     plateformes, applications, transport, activites, nationalite_code, photo)
                    VALUES
                    (' . $parametreId . ':numero, :civilite, :nom, :adresse, :cp, :localite, :pays,
                     :plateformes, :applications, :transport, :activites, :nat, :photo)';
            if ($idPersonne !== null) {
                $valeurs[':id'] = $idPersonne;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($valeurs);
        }
        $succes = true;
    } catch (PDOException $e) {
        $erreurs[] = "Échec de l'enregistrement. Vérifiez l'identifiant et les données puis réessayez.";
    }
}

$base  = '../';
$titre = 'Enregistrement personne';
require __DIR__ . '/../includes/header.php';

if ($erreurs) {
    foreach ($erreurs as $e) echo '<p class="erreur">' . h($e) . '</p>';
    echo '<p><a href="javascript:history.back()">Retour au formulaire</a> | <a href="formulaire.php">Nouvelle recherche</a></p>';
} elseif ($succes && $personneExistante) {
    echo '<p class="ok">Les informations de la personne ID ' . h($idPersonne) . ' ont été modifiées.</p>';
    echo '<p><a href="formulaire.php?id=' . h($idPersonne) . '">Continuer la modification</a> | <a href="liste.php">Affichage Liste</a></p>';
} elseif ($succes && $idPersonne !== null) {
    echo '<p class="ok">La personne a été créée avec l’ID ' . h($idPersonne) . '.</p>';
    echo '<p><a href="formulaire.php?id=' . h($idPersonne) . '">Modifier cette personne</a> | <a href="liste.php">Affichage Liste</a></p>';
} elseif ($succes) {
    echo '<p class="ok">Personne enregistrée : ' . h($nomPrenom) . '</p>';
    echo '<p><a href="formulaire.php">Nouvelle personne</a> | <a href="liste.php">Affichage Liste</a></p>';
}
require __DIR__ . '/../includes/footer.php';
