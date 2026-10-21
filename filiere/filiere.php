<?php
// filiere/filiere.php : formulaire de saisie d'une filière
require_once __DIR__ . '/../includes/fonctions.php';
$base  = '../';
$titre = 'Gestion des filières';
require __DIR__ . '/../includes/header.php';
?>
<form id="form-filiere" method="post" action="enregistrer.php">
    <table class="formulaire">
        <tr>
            <td>CODE</td>
            <td><input type="text" name="code" id="code" maxlength="10" size="12"></td>
        </tr>
        <tr>
            <td>FILIERE</td>
            <td><input type="text" name="libelle" id="libelle" maxlength="10" size="12"></td>
        </tr>
    </table>
    <button type="submit">Enregistrer</button>
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
</form>
<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
