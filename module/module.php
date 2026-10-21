<?php
// module/module.php : formulaire de saisie d'un module
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare('SELECT code, libelle FROM filiere ORDER BY code');
$stmt->execute();
$filieres = $stmt->fetchAll();

$base  = '../';
$titre = 'Gestion des modules';
require __DIR__ . '/../includes/header.php';
?>
<form id="form-module" method="post" action="enregistrer.php">
    <table class="formulaire">
        <tr>
            <td>Id (facultatif, attribué automatiquement si vide) :</td>
            <td><input type="number" name="id" id="module-id" min="1" step="1"></td>
        </tr>
        <tr>
            <td>Code module :</td>
            <td><input type="text" name="code" id="module-code" maxlength="20" required></td>
        </tr>
        <tr>
            <td>Désignation module :</td>
            <td><input type="text" name="designation" id="module-designation" maxlength="100" required></td>
        </tr>
        <tr>
            <td>Coefficient :</td>
            <td><input type="number" name="coefficient" id="module-coefficient" min="0.01" max="999.99" step="0.01" required></td>
        </tr>
        <tr>
            <td>Volume horaire :</td>
            <td><input type="number" name="volume_horaire" id="module-volume-horaire" min="0" max="65535" step="1" required></td>
        </tr>
        <tr>
            <td>Filière :</td>
            <td>
                <?php if (!$filieres): ?>
                    <span class="erreur">Aucune filière enregistrée. <a href="../filiere/filiere.php">En ajouter une</a></span>
                <?php else: ?>
                    <select name="filiere" id="module-filiere" required>
                        <option value="">— Choisir une filière —</option>
                        <?php foreach ($filieres as $filiere): ?>
                            <option value="<?= h($filiere['code']) ?>"><?= h($filiere['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <button type="submit">Enregistrer</button>
    <button type="submit" formaction="liste.php" formmethod="get" formnovalidate>Affichage Liste</button>
</form>
<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
