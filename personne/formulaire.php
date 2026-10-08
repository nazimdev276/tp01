<?php
// personne/formulaire.php : formulaire principal
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

// Les nationalités sont lues dans MySQL (rien n'est écrit "en dur")
$stmt = $pdo->prepare('SELECT code, libelle FROM nationalite ORDER BY libelle');
$stmt->execute();
$nationalites = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT libelle FROM moyen_transport ORDER BY libelle');
$stmt->execute();
$transports = $stmt->fetchAll(PDO::FETCH_COLUMN);

$idRecherche = $_GET['id'] ?? '';
$erreurRecherche = null;
$personne = null;
$idRechercheValide = null;
if ($idRecherche !== '') {
    if (!is_string($idRecherche) && !is_int($idRecherche)) {
        $erreurRecherche = "Identifiant invalide.";
        $idRecherche = '';
    } else {
        $idRecherche = trim((string)$idRecherche);
        $idRechercheValide = filter_var($idRecherche, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 2147483647],
        ]);
        if ($idRechercheValide === false) {
            $erreurRecherche = "Identifiant invalide.";
            $idRechercheValide = null;
        } else {
            $stmt = $pdo->prepare('SELECT * FROM personne WHERE id = :id');
            $stmt->execute([':id' => $idRechercheValide]);
            $personne = $stmt->fetch() ?: null;
        }
    }
}

$plateformesSelectionnees = array_filter(array_map('trim', explode(',', $personne['plateformes'] ?? '')));
$applicationsSelectionnees = array_filter(array_map('trim', explode(',', $personne['applications'] ?? '')));
$activitesSelectionnees = array_filter(array_map('trim', explode(',', $personne['activites'] ?? '')));

$base  = '../';
$titre = 'Formulaire Personne';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" action="formulaire.php">
    <label for="recherche-id">Rechercher ou créer par ID :</label>
    <input type="text" name="id" id="recherche-id" inputmode="numeric" pattern="[1-9][0-9]*"
           value="<?= h($idRecherche) ?>" required>
    <button type="submit">Rechercher</button>
</form>
<?php if ($erreurRecherche): ?>
    <p class="erreur"><?= h($erreurRecherche) ?></p>
<?php elseif ($idRechercheValide !== null && $personne): ?>
    <p class="ok">Personne trouvée : modifiez les champs puis enregistrez.</p>
<?php elseif ($idRechercheValide !== null): ?>
    <p>Aucune personne trouvée pour l’ID <?= h($idRechercheValide) ?>. Remplissez les champs pour la créer avec cet ID.</p>
<?php endif; ?>

<!-- enctype="multipart/form-data" est obligatoire pour envoyer une image -->
<form id="form-personne" method="post" action="enregistrer.php" enctype="multipart/form-data">
    <?php if ($idRechercheValide !== null): ?>
        <input type="hidden" name="id" value="<?= h($idRechercheValide) ?>">
    <?php endif; ?>

    <div id="cadre-photo">
        <img id="apercu" alt="Aperçu de la photo">
    </div>

    <table class="formulaire">
        <tr>
            <td>Numéro :</td>
            <td><input type="text" name="numero" id="numero" size="6" maxlength="10" placeholder="15" value="<?= h($personne['numero'] ?? '') ?>"></td>
        </tr>
        <tr>
            <td>Civilité :</td>
            <td>
                <?php foreach ($CIVILITES as $i => $c): ?>
                    <label><input type="radio" name="civilite" value="<?= h($c) ?>" <?= isset($personne['civilite']) ? ($personne['civilite'] === $c ? 'checked' : '') : ($i === 0 ? 'checked' : '') ?>> <?= h($c) ?></label>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td>Nom / Prénom :</td>
            <td><input type="text" name="nom_prenom" id="nom_prenom" size="40" maxlength="80" placeholder="Benali Farid" value="<?= h($personne['nom_prenom'] ?? '') ?>"></td>
        </tr>
        <tr>
            <td>Adresse :</td>
            <td><input type="text" name="adresse" id="adresse" size="40" maxlength="150" placeholder="Rue des Roses - SBA" value="<?= h($personne['adresse'] ?? '') ?>"></td>
        </tr>
        <tr>
            <td>No postal / Localité :</td>
            <td>
                <input type="text" name="code_postal" id="code_postal" size="6" maxlength="10" placeholder="22000" value="<?= h($personne['code_postal'] ?? '') ?>">
                <input type="text" name="localite" id="localite" size="25" maxlength="60" placeholder="Sidi Bel Abbès" value="<?= h($personne['localite'] ?? '') ?>">
            </td>
        </tr>
        <tr>
            <td>Pays :</td>
            <td>
                <select name="pays" id="pays">
                    <?php foreach ($PAYS as $p): ?>
                        <option value="<?= h($p) ?>" <?= ($personne['pays'] ?? '') === $p ? 'selected' : '' ?>><?= h($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Plateforme(s) :</td>
            <td>
                <?php foreach ($PLATEFORMES as $p): ?>
                    <label><input type="checkbox" name="plateformes[]" value="<?= h($p) ?>" <?= in_array($p, $plateformesSelectionnees, true) ? 'checked' : '' ?>> <?= h($p) ?></label>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td>Application(s) :</td>
            <td>
                <?php foreach ($APPLICATIONS as $a): ?>
                    <label><input type="checkbox" name="applications[]" value="<?= h($a) ?>" <?= in_array($a, $applicationsSelectionnees, true) ? 'checked' : '' ?>> <?= h($a) ?></label><br>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td>Moyen de transport :</td>
            <td>
                <select name="transport" id="transport">
                    <?php foreach ($transports as $transport): ?>
                        <option value="<?= h($transport) ?>" <?= ($personne['transport'] ?? '') === $transport ? 'selected' : '' ?>><?= h($transport) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!$transports): ?>
                    <span class="erreur">Aucun moyen de transport. <a href="../transport/transport.php">En ajouter un</a></span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Activité(s) :</td>
            <td>
                <?php foreach ($ACTIVITES as $activite): ?>
                    <label><input type="checkbox" name="activites[]" value="<?= h($activite) ?>" <?= in_array($activite, $activitesSelectionnees, true) ? 'checked' : '' ?>> <?= h($activite) ?></label><br>
                <?php endforeach; ?>
            </td>
        </tr>
        <!-- NATIONALITE : placée APRES Application(s), options générées depuis MySQL -->
        <tr>
            <td>Nationalité :</td>
            <td>
                <?php if (!$nationalites): ?>
                    <span class="erreur">Aucune nationalité. <a href="../nationalite/nationalite.php">En ajouter une</a></span>
                <?php else: ?>
                    <select name="nationalite" id="nationalite">
                        <?php foreach ($nationalites as $n): ?>
                            <option value="<?= h($n['code']) ?>" <?= ($personne['nationalite_code'] ?? '') === $n['code'] ? 'selected' : '' ?>><?= h($n['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td>Photo :</td>
            <td><input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/gif"></td>
        </tr>
    </table>

    <!-- Chaque bouton peut avoir son propre formaction (page de destination) -->
    <button type="submit" formaction="afficher_php.php" formnovalidate>Affichage PHP</button>
    <button type="button" id="btn-affichage-js">Affichage JavaScript</button>
    <button type="submit">Enregistrer</button>
    <button type="reset">Réinitialiser</button>
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
    <button type="submit" formaction="upload_image.php" formnovalidate>Insérer Image</button>
</form>

<!-- Zone remplie par JavaScript (bouton Affichage JavaScript) -->
<div id="resultat_js"></div>

<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
