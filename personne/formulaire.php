<?php
// personne/formulaire.php : formulaire principal
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

// Les nationalités sont lues dans MySQL (rien n'est écrit "en dur")
$stmt = $pdo->prepare('SELECT code, libelle FROM nationalite ORDER BY libelle');
$stmt->execute();
$nationalites = $stmt->fetchAll();

$base  = '../';
$titre = 'Formulaire Personne';
require __DIR__ . '/../includes/header.php';
?>
<!-- enctype="multipart/form-data" est obligatoire pour envoyer une image -->
<form id="form-personne" method="post" action="enregistrer.php" enctype="multipart/form-data">

    <div id="cadre-photo">
        <img id="apercu" alt="Aperçu de la photo">
    </div>

    <table class="formulaire">
        <tr>
            <td>Numéro :</td>
            <td><input type="text" name="numero" id="numero" size="6" maxlength="10" placeholder="15"></td>
        </tr>
        <tr>
            <td>Civilité :</td>
            <td>
                <?php foreach ($CIVILITES as $i => $c): ?>
                    <label><input type="radio" name="civilite" value="<?= h($c) ?>" <?= $i === 0 ? 'checked' : '' ?>> <?= h($c) ?></label>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td>Nom / Prénom :</td>
            <td><input type="text" name="nom_prenom" id="nom_prenom" size="40" maxlength="80" placeholder="Benali Farid"></td>
        </tr>
        <tr>
            <td>Adresse :</td>
            <td><input type="text" name="adresse" id="adresse" size="40" maxlength="150" placeholder="Rue des Roses - SBA"></td>
        </tr>
        <tr>
            <td>No postal / Localité :</td>
            <td>
                <input type="text" name="code_postal" id="code_postal" size="6" maxlength="10" placeholder="22000">
                <input type="text" name="localite" id="localite" size="25" maxlength="60" placeholder="Sidi Bel Abbès">
            </td>
        </tr>
        <tr>
            <td>Pays :</td>
            <td>
                <select name="pays" id="pays">
                    <?php foreach ($PAYS as $p): ?>
                        <option value="<?= h($p) ?>"><?= h($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Plateforme(s) :</td>
            <td>
                <?php foreach ($PLATEFORMES as $p): ?>
                    <label><input type="checkbox" name="plateformes[]" value="<?= h($p) ?>"> <?= h($p) ?></label>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td>Application(s) :</td>
            <td>
                <?php foreach ($APPLICATIONS as $a): ?>
                    <label><input type="checkbox" name="applications[]" value="<?= h($a) ?>"> <?= h($a) ?></label><br>
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
                            <option value="<?= h($n['code']) ?>"><?= h($n['libelle']) ?></option>
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
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
    <button type="submit" formaction="upload_image.php" formnovalidate>Insérer Image</button>
</form>

<!-- Zone remplie par JavaScript (bouton Affichage JavaScript) -->
<div id="resultat_js"></div>

<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
