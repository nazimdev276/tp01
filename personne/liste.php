<?php
// personne/liste.php : bouton "Affichage Liste" - affiche les personnes enregistrées
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$idRecherche = $_GET['id'] ?? '';
$erreurRecherche = false;
$personnes = [];

if (!is_string($idRecherche) && !is_int($idRecherche)) {
    $erreurRecherche = true;
    $idRecherche = '';
} else {
    $idRecherche = trim((string)$idRecherche);
    if ($idRecherche !== '') {
        $idValide = filter_var($idRecherche, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 2147483647],
        ]);
        if ($idValide === false) {
            $erreurRecherche = true;
        }
    }
}

// JOIN : on récupère aussi le libellé de la nationalité
if (!$erreurRecherche) {
    $sql = 'SELECT p.*, n.libelle AS nationalite
            FROM personne p
            LEFT JOIN nationalite n ON n.code = p.nationalite_code';
    if ($idRecherche !== '') {
        $sql .= ' WHERE p.id = :id';
    }
    $sql .= ' ORDER BY p.id';

    $stmt = $pdo->prepare($sql);
    if ($idRecherche !== '') {
        $stmt->bindValue(':id', (int)$idRecherche, PDO::PARAM_INT);
    }
    $stmt->execute();
    $personnes = $stmt->fetchAll();
}

$base  = '../';
$titre = 'Liste des personnes';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($erreurRecherche): ?>
    <p class="erreur">Veuillez saisir un ID valide (nombre entier positif).</p>
<?php elseif (!$personnes && $idRecherche !== ''): ?>
    <p>Aucune personne trouvée pour l'ID <?= h($idRecherche) ?>.</p>
<?php elseif (!$personnes): ?>
    <p>Aucune personne enregistrée.</p>
<?php else: ?>
    <table class="liste">
        <tr>
            <th>ID</th><th>N°</th><th>Civilité</th><th>Nom / Prénom</th><th>Adresse</th><th>CP / Localité</th>
            <th>Pays</th><th>Plateformes</th><th>Applications</th><th>Transport</th><th>Activités</th><th>Nationalité</th><th>Photo</th><th>Action</th>
        </tr>
        <?php foreach ($personnes as $p): ?>
            <tr>
                <td><?= h($p['id']) ?></td>
                <td><?= h($p['numero']) ?></td>
                <td><?= h($p['civilite']) ?></td>
                <td><?= h($p['nom_prenom']) ?></td>
                <td><?= h($p['adresse']) ?></td>
                <td><?= h($p['code_postal']) ?> <?= h($p['localite']) ?></td>
                <td><?= h($p['pays']) ?></td>
                <td><?= h($p['plateformes']) ?></td>
                <td><?= h($p['applications']) ?></td>
                <td><?= h($p['transport']) ?></td>
                <td><?= h($p['activites']) ?></td>
                <td><?= h($p['nationalite']) ?></td>
                <td>
                    <?php if ($p['photo']): ?>
                        <img src="../uploads/<?= h($p['photo']) ?>" width="60" alt="photo">
                    <?php endif; ?>
                </td>
                <td>
                    <a href="modifier.php?id=<?= h($p['id']) ?>">Modifier</a>
                    <form method="post" action="supprimer.php" onsubmit="return confirm('Supprimer cette personne définitivement ?');">
                        <input type="hidden" name="id" value="<?= h($p['id']) ?>">
                        <button type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>
<p><a href="formulaire.php">Nouvelle personne</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
