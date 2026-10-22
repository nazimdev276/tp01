<?php
// personne/modifier.php : modifie les informations d'une personne existante
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

$id = $_GET['id'] ?? '';
if (!is_string($id) && !is_int($id)) {
    $id = '';
}
$id = trim((string)$id);
$idValide = filter_var($id, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 2147483647],
]);

$personne = null;
$erreurs = [];
$succes = false;

if ($idValide === false) {
    $erreurs[] = "Identifiant invalide.";
} else {
    $stmt = $pdo->prepare('SELECT * FROM personne WHERE id = :id');
    $stmt->execute([':id' => $idValide]);
    $personne = $stmt->fetch();
    if (!$personne) {
        $erreurs[] = "Aucune personne trouvée pour cet identifiant.";
    }
}

$stmt = $pdo->prepare('SELECT code, libelle FROM nationalite ORDER BY libelle');
$stmt->execute();
$nationalites = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT code, libelle FROM filiere ORDER BY code');
$stmt->execute();
$filieres = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT libelle FROM moyen_transport ORDER BY libelle');
$stmt->execute();
$transports = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT libelle FROM sport ORDER BY libelle');
$stmt->execute();
$sports = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($personne && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $champPost = static function (string $nom): string {
        $valeur = $_POST[$nom] ?? '';
        return is_string($valeur) ? trim($valeur) : '';
    };

    $numero = $champPost('numero');
    $typeFoyer = $champPost('type_foyer');
    $civilite = $champPost('civilite');
    $nomPrenom = $champPost('nom_prenom');
    $adresse = $champPost('adresse');
    $codePostal = $champPost('code_postal');
    $localite = $champPost('localite');
    $pays = $champPost('pays');
    $descriptionPost = $_POST['description'] ?? '';
    $description = is_string($descriptionPost) ? trim($descriptionPost) : '';
    $voitures = post_liste('voiture', $VOITURES);
    $transportPost = $_POST['transport'] ?? [];
    $groupeSanguin = $champPost('groupe_sanguin');
    $sport = $champPost('sport');
    $natCode = $champPost('nationalite');
    $filiereCode = $champPost('filiere');
    $plateformes = post_liste('plateformes', $PLATEFORMES);
    $modules = post_liste('modules', $MODULES);
    $applications = post_liste('applications', $APPLICATIONS);
    $activites = post_liste('activites', $ACTIVITES);

    if (!preg_match('/^[0-9]{1,10}$/', $numero)) {
        $erreurs[] = "Numéro invalide (chiffres uniquement).";
    }
    if (!in_array($typeFoyer, $TYPES_FOYER, true)) {
        $erreurs[] = "Type de foyer invalide.";
    }
    if (!in_array($civilite, $CIVILITES, true)) {
        $erreurs[] = "Civilité invalide.";
    }
    if ($nomPrenom === '' || mb_strlen($nomPrenom) > 80) {
        $erreurs[] = "Nom / Prénom obligatoire (80 caractères max).";
    }
    if ($adresse === '' || mb_strlen($adresse) > 150) {
        $erreurs[] = "Adresse obligatoire (150 caractères max).";
    }
    if (!preg_match('/^[0-9]{4,10}$/', $codePostal)) {
        $erreurs[] = "Code postal invalide.";
    }
    if ($localite === '' || mb_strlen($localite) > 60) {
        $erreurs[] = "Localité obligatoire (60 caractères max).";
    }
    if (!in_array($pays, $PAYS, true)) {
        $erreurs[] = "Pays invalide.";
    }
    if (!is_string($descriptionPost) || mb_strlen($description) > 500) {
        $erreurs[] = "La description ne doit pas dépasser 500 caractères.";
    }
    if (!in_array($filiereCode, array_column($filieres, 'code'), true)) {
        $erreurs[] = "Filière d’inscription invalide.";
    }
    $transportsSelectionnes = [];
    if (is_array($transportPost)) {
        foreach ($transportPost as $transportOption) {
            if (is_string($transportOption) && in_array($transportOption, $transports, true)) {
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
        $erreurs[] = "Moyen de transport invalide.";
    }
    if ($groupeSanguin !== '' && !in_array($groupeSanguin, $GROUPES_SANGUINS, true)) {
        $erreurs[] = "Groupe sanguin invalide.";
    }
    if ($sport !== '' && !in_array($sport, $sports, true)) {
        $erreurs[] = "Sport invalide.";
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM nationalite WHERE code = :code');
    $stmt->execute([':code' => $natCode]);
    if ($stmt->fetchColumn() == 0) {
        $erreurs[] = "Nationalité invalide.";
    }

    $photo = $personne['photo'];
    if (!$erreurs) {
        [$nouvellePhoto, $erreurImage] = enregistrer_image($_FILES['photo'] ?? []);
        if ($erreurImage) {
            $erreurs[] = $erreurImage;
        } elseif ($nouvellePhoto !== null) {
            $photo = $nouvellePhoto;
        }
    }

    if (!$erreurs) {
        try {
            $stmt = $pdo->prepare(
                'UPDATE personne
                 SET numero = :numero, type_foyer = :type_foyer, civilite = :civilite, nom_prenom = :nom,
                     adresse = :adresse, code_postal = :cp, localite = :localite,
                     pays = :pays, plateformes = :plateformes, modules = :modules, applications = :applications,
                     voiture = :voiture,
                     transport = :transport, description = :description, groupe_sanguin = :groupe_sanguin, sport = :sport, activites = :activites,
                     nationalite_code = :nat, filiere_code = :filiere, photo = :photo
                 WHERE id = :id'
            );
            $stmt->execute([
                ':numero' => $numero,
                ':type_foyer' => $typeFoyer,
                ':civilite' => $civilite,
                ':nom' => $nomPrenom,
                ':adresse' => $adresse,
                ':cp' => $codePostal,
                ':localite' => $localite,
                ':pays' => $pays,
                ':plateformes' => implode(', ', $plateformes),
                ':modules' => implode(', ', $modules),
                ':applications' => implode(', ', $applications),
                ':voiture' => implode(', ', $voitures),
                ':transport' => $transport,
                ':description' => $description,
                ':groupe_sanguin' => $groupeSanguin,
                ':sport' => $sport,
                ':activites' => implode(', ', $activites),
                ':nat' => $natCode,
                ':filiere' => $filiereCode,
                ':photo' => $photo,
                ':id' => $idValide,
            ]);
            $succes = true;
            $personne = [
                'id' => $idValide,
                'numero' => $numero,
                'type_foyer' => $typeFoyer,
                'civilite' => $civilite,
                'nom_prenom' => $nomPrenom,
                'adresse' => $adresse,
                'code_postal' => $codePostal,
                'localite' => $localite,
                'pays' => $pays,
                'plateformes' => implode(', ', $plateformes),
                'modules' => implode(', ', $modules),
                'applications' => implode(', ', $applications),
                'voiture' => implode(', ', $voitures),
                'transport' => $transport,
                'description' => $description,
                'groupe_sanguin' => $groupeSanguin,
                'sport' => $sport,
                'activites' => implode(', ', $activites),
                'nationalite_code' => $natCode,
                'filiere_code' => $filiereCode,
                'photo' => $photo,
            ];
        } catch (PDOException $e) {
            $erreurs[] = "Échec de l'enregistrement des modifications.";
        }
    }

    if (!$succes) {
        $personne['numero'] = $numero;
        $personne['type_foyer'] = $typeFoyer;
        $personne['civilite'] = $civilite;
        $personne['nom_prenom'] = $nomPrenom;
        $personne['adresse'] = $adresse;
        $personne['code_postal'] = $codePostal;
        $personne['localite'] = $localite;
        $personne['pays'] = $pays;
        $personne['plateformes'] = implode(', ', $plateformes);
        $personne['modules'] = implode(', ', $modules);
        $personne['applications'] = implode(', ', $applications);
        $personne['voiture'] = implode(', ', $voitures);
        $personne['transport'] = $transport;
        $personne['description'] = $description;
        $personne['groupe_sanguin'] = $groupeSanguin;
        $personne['sport'] = $sport;
        $personne['activites'] = implode(', ', $activites);
        $personne['nationalite_code'] = $natCode;
        $personne['filiere_code'] = $filiereCode;
    }
}

$plateformesSelectionnees = array_filter(array_map('trim', explode(',', $personne['plateformes'] ?? '')));
$modulesSelectionnes = array_filter(array_map('trim', explode(',', $personne['modules'] ?? '')));
$applicationsSelectionnees = array_filter(array_map('trim', explode(',', $personne['applications'] ?? '')));
$voituresSelectionnees = array_filter(array_map('trim', explode(',', $personne['voiture'] ?? '')));
$transportsSelectionnes = array_filter(array_map('trim', explode(',', $personne['transport'] ?? '')));
$activitesSelectionnees = array_filter(array_map('trim', explode(',', $personne['activites'] ?? '')));

$base = '../';
$titre = 'Modifier une personne';
require __DIR__ . '/../includes/header.php';
?>
<?php foreach ($erreurs as $erreur): ?>
    <p class="erreur"><?= h($erreur) ?></p>
<?php endforeach; ?>
<?php if ($succes): ?>
    <p class="ok">Les modifications de la personne ID <?= h($idValide) ?> ont été enregistrées.</p>
<?php endif; ?>
<?php if ($personne): ?>
    <form method="post" action="modifier.php?id=<?= h($idValide) ?>" enctype="multipart/form-data">
        <table class="formulaire">
            <tr><td>ID :</td><td><?= h($personne['id']) ?></td></tr>
            <tr><td>Numéro :</td><td><input type="text" name="numero" maxlength="10" value="<?= h($personne['numero']) ?>" required></td></tr>
            <tr>
                <td>Type de foyer :</td>
                <td><?php foreach ($TYPES_FOYER as $typeFoyerOption): ?>
                    <label><input type="radio" name="type_foyer" value="<?= h($typeFoyerOption) ?>" <?= ($personne['type_foyer'] ?? 'Individuelle') === $typeFoyerOption ? 'checked' : '' ?> required> <?= h($typeFoyerOption) ?></label>
                <?php endforeach; ?></td>
            </tr>
            <tr>
                <td>Civilité :</td>
                <td><?php foreach ($CIVILITES as $civiliteOption): ?>
                    <label><input type="radio" name="civilite" value="<?= h($civiliteOption) ?>" <?= $personne['civilite'] === $civiliteOption ? 'checked' : '' ?>> <?= h($civiliteOption) ?></label>
                <?php endforeach; ?></td>
            </tr>
            <tr><td>Nom / Prénom :</td><td><input type="text" name="nom_prenom" maxlength="80" value="<?= h($personne['nom_prenom']) ?>" required></td></tr>
            <tr><td>Adresse :</td><td><input type="text" name="adresse" maxlength="150" value="<?= h($personne['adresse']) ?>" required></td></tr>
            <tr>
                <td>No postal / Localité :</td>
                <td>
                    <input type="text" name="code_postal" maxlength="10" value="<?= h($personne['code_postal']) ?>" required>
                    <input type="text" name="localite" maxlength="60" value="<?= h($personne['localite']) ?>" required>
                </td>
            </tr>
            <tr>
                <td>Pays :</td>
                <td><select name="pays"><?php foreach ($PAYS as $paysOption): ?>
                    <option value="<?= h($paysOption) ?>" <?= $personne['pays'] === $paysOption ? 'selected' : '' ?>><?= h($paysOption) ?></option>
                <?php endforeach; ?></select></td>
            </tr>
            <tr>
                <td>Filière d’inscription :</td>
                <td>
                    <select name="filiere" required>
                        <option value="">— Choisir une filière —</option>
                        <?php foreach ($filieres as $filiere): ?>
                            <option value="<?= h($filiere['code']) ?>" <?= ($personne['filiere_code'] ?? '') === $filiere['code'] ? 'selected' : '' ?>><?= h($filiere['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Plateforme(s) :</td>
                <td><?php foreach ($PLATEFORMES as $plateforme): ?>
                    <label><input type="checkbox" name="plateformes[]" value="<?= h($plateforme) ?>" <?= in_array($plateforme, $plateformesSelectionnees, true) ? 'checked' : '' ?>> <?= h($plateforme) ?></label>
                <?php endforeach; ?></td>
            </tr>
            <tr>
                <td>Module(s) :</td>
                <td><?php foreach ($MODULES as $module): ?>
                    <label><input type="checkbox" name="modules[]" value="<?= h($module) ?>" <?= in_array($module, $modulesSelectionnes, true) ? 'checked' : '' ?>> <?= h($module) ?></label>
                <?php endforeach; ?></td>
            </tr>
            <tr>
                <td>Application(s) :</td>
                <td><?php foreach ($APPLICATIONS as $application): ?>
                    <label><input type="checkbox" name="applications[]" value="<?= h($application) ?>" <?= in_array($application, $applicationsSelectionnees, true) ? 'checked' : '' ?>> <?= h($application) ?></label><br>
                <?php endforeach; ?></td>
            </tr>
            <tr>
                <td>Voiture(s) :</td>
                <td><select name="voiture[]" multiple>
                    <?php foreach ($VOITURES as $voitureOption): ?>
                        <option value="<?= h($voitureOption) ?>" <?= in_array($voitureOption, $voituresSelectionnees, true) ? 'selected' : '' ?>><?= h($voitureOption) ?></option>
                    <?php endforeach; ?>
                </select></td>
            </tr>
            <tr>
                <td>Moyen de transport :</td>
                <td><?php foreach ($transports as $transportOption): ?>
                    <label><input type="checkbox" name="transport[]" value="<?= h($transportOption) ?>" <?= in_array($transportOption, $transportsSelectionnes, true) ? 'checked' : '' ?>> <?= h($transportOption) ?></label><br>
                <?php endforeach; ?></td>
            </tr>
            <tr><td>Description :</td><td><textarea name="description" maxlength="500" rows="4" cols="40"><?= h($personne['description'] ?? '') ?></textarea></td></tr>
            <tr>
                <td>Groupe sanguin :</td>
                <td><select name="groupe_sanguin">
                    <option value="">— Non renseigné —</option>
                    <?php foreach ($GROUPES_SANGUINS as $groupeSanguinOption): ?>
                        <option value="<?= h($groupeSanguinOption) ?>" <?= ($personne['groupe_sanguin'] ?? '') === $groupeSanguinOption ? 'selected' : '' ?>><?= h($groupeSanguinOption) ?></option>
                    <?php endforeach; ?>
                </select></td>
            </tr>
            <tr>
                <td>Sport :</td>
                <td><select name="sport">
                    <option value="">— Aucun —</option>
                    <?php foreach ($sports as $sportOption): ?>
                        <option value="<?= h($sportOption) ?>" <?= ($personne['sport'] ?? '') === $sportOption ? 'selected' : '' ?>><?= h($sportOption) ?></option>
                    <?php endforeach; ?>
                </select></td>
            </tr>
            <tr>
                <td>Activité(s) :</td>
                <td><?php foreach ($ACTIVITES as $activite): ?>
                    <label><input type="checkbox" name="activites[]" value="<?= h($activite) ?>" <?= in_array($activite, $activitesSelectionnees, true) ? 'checked' : '' ?>> <?= h($activite) ?></label><br>
                <?php endforeach; ?></td>
            </tr>
            <tr>
                <td>Nationalité :</td>
                <td><select name="nationalite"><?php foreach ($nationalites as $nationalite): ?>
                    <option value="<?= h($nationalite['code']) ?>" <?= $personne['nationalite_code'] === $nationalite['code'] ? 'selected' : '' ?>><?= h($nationalite['libelle']) ?></option>
                <?php endforeach; ?></select></td>
            </tr>
            <tr>
                <td>Photo actuelle :</td>
                <td>
                    <?php if ($personne['photo']): ?>
                        <img src="../uploads/<?= h($personne['photo']) ?>" width="80" alt="Photo actuelle"><br>
                    <?php else: ?>
                        Aucune photo
                    <?php endif; ?>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/gif">
                    <span class="petit">Laissez vide pour conserver la photo actuelle.</span>
                </td>
            </tr>
        </table>
        <button type="submit">Enregistrer les modifications</button>
        <button type="reset">Réinitialiser</button>
    </form>
<?php endif; ?>
<p><a href="liste.php">Retour à la liste</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
