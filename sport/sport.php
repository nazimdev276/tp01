<?php
// sport/sport.php : formulaire de saisie d'un sport
require_once __DIR__ . '/../includes/fonctions.php';
$base = '../';
$titre = 'Gestion des sports';
require __DIR__ . '/../includes/header.php';
?>
<form id="form-sport" method="post" action="enregistrer.php">
    <table class="formulaire">
        <tr>
            <td>Sport</td>
            <td><input type="text" name="libelle" id="sport-libelle" maxlength="40" size="30" required></td>
        </tr>
    </table>
    <button type="submit">Enregistrer</button>
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
</form>
<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
