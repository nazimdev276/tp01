<?php
// nationalite/nationalite.php : formulaire de saisie d'une nationalité
require_once __DIR__ . '/../includes/fonctions.php';
$base  = '../';
$titre = 'Gestion des nationalités';
require __DIR__ . '/../includes/header.php';
?>
<!-- Le formulaire envoie les données vers enregistrer.php -->
<form id="form-nationalite" method="post" action="enregistrer.php">
    <table class="formulaire">
        <tr>
            <td>CODE</td>
            <td><input type="text" name="code" id="code" maxlength="3" size="5"></td>
        </tr>
        <tr>
            <td>NATIONALITE</td>
            <td><input type="text" name="libelle" id="libelle" maxlength="40" size="40"></td>
        </tr>
    </table>
    <button type="submit">Enregistrer</button>
    <!-- formnovalidate + formaction : ce bouton va directement vers la liste -->
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
</form>
<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
