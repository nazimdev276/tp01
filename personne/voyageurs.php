<?php
// personne/voyageurs.php : recherche et modifie les informations d’un voyageur
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

$idRecherche = $_GET['id'] ?? $_POST['id'] ?? '';
$modeRecherche = array_key_exists('id', $_GET)
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? null) === 'modifier_voyageur');
$erreurRecherche = null;
$erreurs = [];
$voyageur = null;
$idValide = null;
$succes = false;

if (!is_string($idRecherche) && !is_int($idRecherche)) {
    $erreurRecherche = 'Identifiant invalide.';
    $idRecherche = '';
} elseif ($idRecherche !== '') {
    $idRecherche = trim((string)$idRecherche);
    $idValide = filter_var($idRecherche, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);
    if ($idValide === false) {
        $erreurRecherche = 'Identifiant invalide.';
        $idValide = null;
    } else {
        $stmt = $pdo->prepare(
            'SELECT v.id, v.pays, v.nom_prenom, v.adresse, v.type_foyer, v.description,
                    v.filiere_code, v.code_postal, v.numero, v.civilite,
                    v.transport, v.photo, f.libelle AS filiere
             FROM voyageur v
             LEFT JOIN filiere f ON f.code = v.filiere_code
             WHERE v.id = :id'
        );
        $stmt->execute([':id' => $idValide]);
        $voyageur = $stmt->fetch() ?: null;

        if ($voyageur && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? null) === 'modifier_voyageur') {
            $champ = static function (string $name): string {
                $value = $_POST[$name] ?? '';
                return is_string($value) ? trim($value) : '';
            };
            $pays = $champ('pays');
            $nomPrenom = $champ('nom_prenom');
            $adresse = $champ('adresse');
            $typeFoyer = $champ('type_foyer');
            $filiereCode = $champ('filiere_code');
            $codePostal = $champ('code_postal');
            $numero = $champ('numero');
            $civilite = $champ('civilite');
            $descriptionPost = $_POST['description'] ?? '';
            $description = is_string($descriptionPost) ? trim($descriptionPost) : '';
            $transportPost = $_POST['transport'] ?? [];

            if (!in_array($pays, $PAYS, true)) {
                $erreurs[] = 'Pays invalide.';
            }
            if ($nomPrenom === '' || mb_strlen($nomPrenom) > 80) {
                $erreurs[] = 'Le nom et prénom sont obligatoires (80 caractères maximum).';
            }
            if ($adresse === '' || mb_strlen($adresse) > 150) {
                $erreurs[] = 'L’adresse est obligatoire (150 caractères maximum).';
            }
            if (!in_array($typeFoyer, $TYPES_FOYER, true)) {
                $erreurs[] = 'Type de foyer invalide.';
            }
            if (!preg_match('/^[0-9]{4,10}$/', $codePostal)) {
                $erreurs[] = 'Code postal invalide.';
            }
            if (!preg_match('/^[0-9]{1,10}$/', $numero)) {
                $erreurs[] = 'Numéro invalide (chiffres uniquement).';
            }
            if (!in_array($civilite, $CIVILITES, true)) {
                $erreurs[] = 'Civilité invalide.';
            }
            if (!is_string($descriptionPost) || mb_strlen($description) > 500) {
                $erreurs[] = 'La description ne doit pas dépasser 500 caractères.';
            }

            $stmt = $pdo->prepare('SELECT code FROM filiere');
            $stmt->execute();
            $filieresDisponibles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array($filiereCode, $filieresDisponibles, true)) {
                $erreurs[] = 'Filière invalide.';
            }

            $stmt = $pdo->prepare('SELECT libelle FROM moyen_transport');
            $stmt->execute();
            $transportsDisponibles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $transportsSelectionnes = [];
            if (is_array($transportPost)) {
                foreach ($transportPost as $transportOption) {
                    if (is_string($transportOption) && in_array($transportOption, $transportsDisponibles, true)) {
                        $transportsSelectionnes[] = $transportOption;
                    } else {
                        $transportsSelectionnes[] = null;
                    }
                }
            } else {
                $transportsSelectionnes = [null];
            }
            $transportsSelectionnes = array_values(array_unique($transportsSelectionnes));
            $transport = implode(', ', array_filter($transportsSelectionnes, 'is_string'));
            if (in_array(null, $transportsSelectionnes, true) || mb_strlen($transport) > 255) {
                $erreurs[] = 'Moyens de transport invalides.';
            }

            $photo = $voyageur['photo'];
            if (!$erreurs) {
                [$nouvellePhoto, $erreurImage] = enregistrer_image($_FILES['photo'] ?? []);
                if ($erreurImage !== null) {
                    $erreurs[] = $erreurImage;
                } elseif ($nouvellePhoto !== null) {
                    $photo = $nouvellePhoto;
                }
            }

            if (!$erreurs) {
                $stmt = $pdo->prepare(
                    'UPDATE voyageur
                     SET pays = :pays, nom_prenom = :nom_prenom, adresse = :adresse,
                         type_foyer = :type_foyer, filiere_code = :filiere_code,
                         code_postal = :code_postal, numero = :numero, civilite = :civilite,
                         transport = :transport, description = :description, photo = :photo
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':pays' => $pays,
                    ':nom_prenom' => $nomPrenom,
                    ':adresse' => $adresse,
                    ':type_foyer' => $typeFoyer,
                    ':filiere_code' => $filiereCode,
                    ':code_postal' => $codePostal,
                    ':numero' => $numero,
                    ':civilite' => $civilite,
                    ':transport' => $transport,
                    ':description' => $description,
                    ':photo' => $photo,
                    ':id' => $idValide,
                ]);
                $succes = true;
            }

            $voyageur = array_merge($voyageur, [
                'pays' => $pays,
                'nom_prenom' => $nomPrenom,
                'adresse' => $adresse,
                'type_foyer' => $typeFoyer,
                'filiere_code' => $filiereCode,
                'code_postal' => $codePostal,
                'numero' => $numero,
                'civilite' => $civilite,
                'transport' => $transport,
                'description' => $description,
                'photo' => $photo,
            ]);
            if ($succes) {
                $stmt = $pdo->prepare('SELECT libelle FROM filiere WHERE code = :code');
                $stmt->execute([':code' => $filiereCode]);
                $voyageur['filiere'] = $stmt->fetchColumn() ?: '';
            }
        }
    }
}

$stmt = $pdo->prepare('SELECT libelle, description FROM moyen_transport ORDER BY libelle');
$stmt->execute();
$transports = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT code, libelle FROM filiere ORDER BY code');
$stmt->execute();
$filieres = $stmt->fetchAll();
$transportsSelectionnes = array_filter(array_map('trim', explode(',', $voyageur['transport'] ?? '')));

$nouveauVoyageur = [
    'numero' => '',
    'type_foyer' => $TYPES_FOYER[0],
    'civilite' => $CIVILITES[0],
    'nom_prenom' => '',
    'adresse' => '',
    'code_postal' => '',
    'pays' => $PAYS[0],
    'filiere_code' => '',
    'description' => '',
];
$transportsCreation = [];
$erreursCreation = [];
$succesCreation = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? null) === 'creer_voyageur') {
    $champ = static function (string $name): string {
        $value = $_POST[$name] ?? '';
        return is_string($value) ? trim($value) : '';
    };
    foreach (array_keys($nouveauVoyageur) as $name) {
        $nouveauVoyageur[$name] = $champ($name);
    }
    $descriptionPost = $_POST['description'] ?? '';
    $nouveauVoyageur['description'] = is_string($descriptionPost) ? trim($descriptionPost) : '';

    if (!preg_match('/^[0-9]{1,10}$/', $nouveauVoyageur['numero'])) {
        $erreursCreation[] = 'Numéro invalide (chiffres uniquement).';
    }
    if (!in_array($nouveauVoyageur['type_foyer'], $TYPES_FOYER, true)) {
        $erreursCreation[] = 'Type de foyer invalide.';
    }
    if (!in_array($nouveauVoyageur['civilite'], $CIVILITES, true)) {
        $erreursCreation[] = 'Civilité invalide.';
    }
    if ($nouveauVoyageur['nom_prenom'] === '' || mb_strlen($nouveauVoyageur['nom_prenom']) > 80) {
        $erreursCreation[] = 'Le nom et prénom sont obligatoires (80 caractères maximum).';
    }
    if ($nouveauVoyageur['adresse'] === '' || mb_strlen($nouveauVoyageur['adresse']) > 150) {
        $erreursCreation[] = 'L’adresse est obligatoire (150 caractères maximum).';
    }
    if (!preg_match('/^[0-9]{4,10}$/', $nouveauVoyageur['code_postal'])) {
        $erreursCreation[] = 'Code postal invalide.';
    }
    if (!in_array($nouveauVoyageur['pays'], $PAYS, true)) {
        $erreursCreation[] = 'Pays invalide.';
    }
    if (!is_string($descriptionPost) || mb_strlen($nouveauVoyageur['description']) > 500) {
        $erreursCreation[] = 'La description ne doit pas dépasser 500 caractères.';
    }

    $codesFilieres = array_column($filieres, 'code');
    if (!in_array($nouveauVoyageur['filiere_code'], $codesFilieres, true)) {
        $erreursCreation[] = 'Filière invalide.';
    }

    $transportPost = $_POST['transport'] ?? [];
    if (is_array($transportPost)) {
        foreach ($transportPost as $transportOption) {
            if (is_string($transportOption) && in_array($transportOption, array_column($transports, 'libelle'), true)) {
                $transportsCreation[] = $transportOption;
            } else {
                $erreursCreation[] = 'Moyen de transport invalide.';
            }
        }
    } else {
        $erreursCreation[] = 'Moyen de transport invalide.';
    }
    $transportsCreation = array_values(array_unique($transportsCreation));
    $transport = implode(', ', $transportsCreation);
    if (mb_strlen($transport) > 255) {
        $erreursCreation[] = 'La liste des moyens de transport est trop longue.';
    }

    $photo = null;
    if (!$erreursCreation) {
        [$photo, $erreurImage] = enregistrer_image($_FILES['photo'] ?? []);
        if ($erreurImage !== null) {
            $erreursCreation[] = $erreurImage;
        }
    }

    if (!$erreursCreation) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO voyageur
                    (numero, type_foyer, civilite, nom_prenom, adresse, code_postal,
                     pays, filiere_code, transport, description, photo)
                 VALUES
                    (:numero, :type_foyer, :civilite, :nom_prenom, :adresse, :code_postal,
                     :pays, :filiere_code, :transport, :description, :photo)'
            );
            $stmt->execute([
                ':numero' => $nouveauVoyageur['numero'],
                ':type_foyer' => $nouveauVoyageur['type_foyer'],
                ':civilite' => $nouveauVoyageur['civilite'],
                ':nom_prenom' => $nouveauVoyageur['nom_prenom'],
                ':adresse' => $nouveauVoyageur['adresse'],
                ':code_postal' => $nouveauVoyageur['code_postal'],
                ':pays' => $nouveauVoyageur['pays'],
                ':filiere_code' => $nouveauVoyageur['filiere_code'],
                ':transport' => $transport,
                ':description' => $nouveauVoyageur['description'],
                ':photo' => $photo,
            ]);
            $succesCreation = true;
        } catch (PDOException $e) {
            $erreursCreation[] = "Échec de l'enregistrement du voyageur. Vérifiez les données puis réessayez.";
        }
    }
}

$base = '../';
$titre = 'Recherche d’un voyageur';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" action="voyageurs.php">
    <label for="recherche-id">ID du voyageur :</label>
    <input type="text" name="id" id="recherche-id" inputmode="numeric" pattern="[1-9][0-9]*"
           value="<?= h($idRecherche) ?>" required>
    <button type="submit">Rechercher</button>
</form>

<?php if (!$modeRecherche): ?>
<?php if ($succesCreation): ?>
    <p class="ok">Le voyageur a été enregistré.</p>
<?php endif; ?>
<?php foreach ($erreursCreation as $erreur): ?>
    <p class="erreur"><?= h($erreur) ?></p>
<?php endforeach; ?>
<h2>Ajouter un voyageur</h2>
<form method="post" action="voyageurs.php" enctype="multipart/form-data">
    <input type="hidden" name="action" value="creer_voyageur">
    <table class="formulaire">
        <tr><td>Numéro :</td><td><input type="text" name="numero" maxlength="10" value="<?= h($nouveauVoyageur['numero']) ?>" required></td></tr>
        <tr>
            <td>Type de foyer :</td>
            <td><?php foreach ($TYPES_FOYER as $typeFoyerOption): ?>
                <label><input type="radio" name="type_foyer" value="<?= h($typeFoyerOption) ?>" <?= $nouveauVoyageur['type_foyer'] === $typeFoyerOption ? 'checked' : '' ?> required> <?= h($typeFoyerOption) ?></label>
            <?php endforeach; ?></td>
        </tr>
        <tr>
            <td>Civilité :</td>
            <td><?php foreach ($CIVILITES as $civiliteOption): ?>
                <label><input type="radio" name="civilite" value="<?= h($civiliteOption) ?>" <?= $nouveauVoyageur['civilite'] === $civiliteOption ? 'checked' : '' ?> required> <?= h($civiliteOption) ?></label>
            <?php endforeach; ?></td>
        </tr>
        <tr><td>Nom / Prénom :</td><td><input type="text" name="nom_prenom" maxlength="80" value="<?= h($nouveauVoyageur['nom_prenom']) ?>" required></td></tr>
        <tr><td>Adresse :</td><td><input type="text" name="adresse" maxlength="150" value="<?= h($nouveauVoyageur['adresse']) ?>" required></td></tr>
        <tr><td>Code postal :</td><td><input type="text" name="code_postal" maxlength="10" value="<?= h($nouveauVoyageur['code_postal']) ?>" required></td></tr>
        <tr>
            <td>Pays :</td>
            <td><select name="pays" required>
                <?php foreach ($PAYS as $paysOption): ?>
                    <option value="<?= h($paysOption) ?>" <?= $nouveauVoyageur['pays'] === $paysOption ? 'selected' : '' ?>><?= h($paysOption) ?></option>
                <?php endforeach; ?>
            </select></td>
        </tr>
        <tr>
            <td>Filière :</td>
            <td><select name="filiere_code" required>
                <option value="">— Choisir une filière —</option>
                <?php foreach ($filieres as $filiere): ?>
                    <option value="<?= h($filiere['code']) ?>" <?= $nouveauVoyageur['filiere_code'] === $filiere['code'] ? 'selected' : '' ?>><?= h($filiere['libelle']) ?></option>
                <?php endforeach; ?>
            </select></td>
        </tr>
        <tr>
            <td>Moyen de transport :</td>
            <td>
                <?php foreach ($transports as $transportOption): ?>
                    <label><input type="checkbox" name="transport[]" value="<?= h($transportOption['libelle']) ?>" <?= in_array($transportOption['libelle'], $transportsCreation, true) ? 'checked' : '' ?>> <?= h($transportOption['libelle']) ?> — <?= h($transportOption['description']) ?></label><br>
                <?php endforeach; ?>
                <?php if (!$transports): ?>
                    <span class="erreur">Aucun moyen de transport enregistré. Ajoutez-en un dans <a href="../transport/transport.php">la gestion des transports</a>.</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr><td>Description :</td><td><textarea name="description" maxlength="500" rows="4" cols="40"><?= h($nouveauVoyageur['description']) ?></textarea></td></tr>
        <tr><td>Photo :</td><td><input type="file" name="photo" accept="image/jpeg,image/png,image/gif"></td></tr>
    </table>
    <button type="submit">Enregistrer le voyageur</button>
</form>
<?php else: ?>
<p><a href="voyageurs.php">Créer un nouveau voyageur</a></p>
<?php endif; ?>

<?php if ($erreurRecherche !== null): ?>
    <p class="erreur"><?= h($erreurRecherche) ?></p>
<?php elseif ($idValide !== null && $voyageur === null): ?>
    <p>Aucun voyageur trouvé pour l’ID <?= h($idValide) ?>.</p>
<?php elseif ($voyageur !== null): ?>
    <?php if ($succes): ?>
        <p class="ok">Les informations du voyageur ID <?= h($idValide) ?> ont été modifiées.</p>
    <?php endif; ?>
    <?php foreach ($erreurs as $erreur): ?>
        <p class="erreur"><?= h($erreur) ?></p>
    <?php endforeach; ?>
    <h2>Informations du voyageur ID <?= h($voyageur['id']) ?></h2>
    <form method="post" action="voyageurs.php" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= h($idValide) ?>">
        <input type="hidden" name="action" value="modifier_voyageur">
        <table class="formulaire">
            <tr>
                <td>Pays :</td>
                <td><select name="pays" required>
                    <?php foreach ($PAYS as $paysOption): ?>
                        <option value="<?= h($paysOption) ?>" <?= $voyageur['pays'] === $paysOption ? 'selected' : '' ?>><?= h($paysOption) ?></option>
                    <?php endforeach; ?>
                </select></td>
            </tr>
            <tr><td>Nom / Prénom :</td><td><input type="text" name="nom_prenom" maxlength="80" value="<?= h($voyageur['nom_prenom']) ?>" required></td></tr>
            <tr><td>Adresse :</td><td><input type="text" name="adresse" maxlength="150" value="<?= h($voyageur['adresse']) ?>" required></td></tr>
            <tr>
                <td>Type de foyer :</td>
                <td><?php foreach ($TYPES_FOYER as $typeFoyerOption): ?>
                    <label><input type="radio" name="type_foyer" value="<?= h($typeFoyerOption) ?>" <?= $voyageur['type_foyer'] === $typeFoyerOption ? 'checked' : '' ?> required> <?= h($typeFoyerOption) ?></label>
                <?php endforeach; ?></td>
            </tr>
            <tr>
                <td>Filière :</td>
                <td><select name="filiere_code" required>
                    <option value="">— Choisir une filière —</option>
                    <?php foreach ($filieres as $filiere): ?>
                        <option value="<?= h($filiere['code']) ?>" <?= $voyageur['filiere_code'] === $filiere['code'] ? 'selected' : '' ?>><?= h($filiere['libelle']) ?></option>
                    <?php endforeach; ?>
                </select></td>
            </tr>
            <tr><td>Code postal :</td><td><input type="text" name="code_postal" maxlength="10" value="<?= h($voyageur['code_postal']) ?>" required></td></tr>
            <tr><td>Numéro :</td><td><input type="text" name="numero" maxlength="10" value="<?= h($voyageur['numero']) ?>" required></td></tr>
            <tr>
                <td>Civilité :</td>
                <td><?php foreach ($CIVILITES as $civiliteOption): ?>
                    <label><input type="radio" name="civilite" value="<?= h($civiliteOption) ?>" <?= $voyageur['civilite'] === $civiliteOption ? 'checked' : '' ?> required> <?= h($civiliteOption) ?></label>
                <?php endforeach; ?></td>
            </tr>
            <tr>
                <td>Moyen de transport :</td>
                <td>
                    <?php if ($transports): ?>
                        <?php foreach ($transports as $transportOption): ?>
                            <label>
                                <input type="checkbox" name="transport[]" value="<?= h($transportOption['libelle']) ?>" <?= in_array($transportOption['libelle'], $transportsSelectionnes, true) ? 'checked' : '' ?>>
                                <?= h($transportOption['libelle']) ?> — <?= h($transportOption['description']) ?>
                            </label><br>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="erreur">Aucun moyen de transport enregistré. Ajoutez-en un dans <a href="../transport/transport.php">la gestion des transports</a>.</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>Description :</td>
                <td><textarea name="description" maxlength="500" rows="4" cols="40"><?= h($voyageur['description'] ?? '') ?></textarea></td>
            </tr>
            <tr>
                <td>Photo :</td>
                <td>
                    <?php if ($voyageur['photo']): ?>
                        <img src="../uploads/<?= h($voyageur['photo']) ?>" width="100" alt="Photo de <?= h($voyageur['nom_prenom']) ?>"><br>
                    <?php else: ?>
                        Aucune photo enregistrée.<br>
                    <?php endif; ?>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif">
                    <span class="petit">Laissez vide pour conserver la photo actuelle.</span>
                </td>
            </tr>
        </table>
        <button type="submit" <?= !$transports ? 'disabled' : '' ?>>Enregistrer les modifications</button>
    </form>
<?php endif; ?>

<p><a href="voyageur_liste.php">Liste des voyageurs</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
