<?php
// transport/transport.php : formulaire de saisie d'un moyen de transport
require_once __DIR__ . '/../includes/fonctions.php';
$base = '../';
$titre = 'Gestion des moyens de transport';
require __DIR__ . '/../includes/header.php';
?>
<form id="form-transport" method="post" action="enregistrer.php">
    <table class="formulaire">
        <tr>
            <td>Moyen de transport</td>
            <td><input type="text" name="libelle" id="transport-libelle" maxlength="20" size="30" required></td>
        </tr>
    </table>
    <button type="submit">Enregistrer</button>
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
</form>
<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
