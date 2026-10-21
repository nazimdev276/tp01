<?php
// enseignant/enseignant.php : formulaire de saisie d'un enseignant
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';
$base  = '../';
$titre = 'Formulaire Enseignants';
require __DIR__ . '/../includes/header.php';
?>
<form id="form-enseignant" method="post" action="enregistrer.php">
    <table class="formulaire">
        <tr>
            <td>Id (facultatif, attribué automatiquement si vide) :</td>
            <td><input type="number" name="id" id="enseignant-id" min="1" step="1"></td>
        </tr>
        <tr>
            <td>Numéro :</td>
            <td><input type="text" name="numero" id="enseignant-numero" maxlength="10" required></td>
        </tr>
        <tr>
            <td>Civilité :</td>
            <td>
                <select name="civilite" id="enseignant-civilite" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ($CIVILITES as $civilite): ?>
                        <option value="<?= h($civilite) ?>"><?= h($civilite) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Nom / Prénom :</td>
            <td><input type="text" name="nom_prenom" id="enseignant-nom-prenom" maxlength="80" required></td>
        </tr>
        <tr>
            <td>Adresse :</td>
            <td><input type="text" name="adresse" id="enseignant-adresse" maxlength="150" required></td>
        </tr>
        <tr>
            <td>Date de naissance :</td>
            <td><input type="date" name="date_naissance" id="enseignant-date-naissance" required></td>
        </tr>
        <tr>
            <td>Lieu de naissance :</td>
            <td><input type="text" name="lieu_naissance" id="enseignant-lieu-naissance" maxlength="80" required></td>
        </tr>
        <tr>
            <td>Pays :</td>
            <td>
                <select name="pays" id="enseignant-pays" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ($PAYS as $pays): ?>
                        <option value="<?= h($pays) ?>"><?= h($pays) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Grade :</td>
            <td>
                <select name="grade" id="enseignant-grade" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ($GRADES_ENSEIGNANT as $grade): ?>
                        <option value="<?= h($grade) ?>"><?= h($grade) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>Spécialité :</td>
            <td>
                <select name="specialite" id="enseignant-specialite" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ($SPECIALITES_ENSEIGNANT as $specialite): ?>
                        <option value="<?= h($specialite) ?>"><?= h($specialite) ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
    <button type="submit">Enregistrer</button>
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
</form>
<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
