<?php
// personne/voyageur_liste.php : affiche les voyageurs enregistrés
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare(
    'SELECT v.id, v.numero, v.type_foyer, v.civilite, v.nom_prenom, v.adresse,
            v.code_postal, v.pays, f.libelle AS filiere, v.transport,
            v.description, v.photo
     FROM voyageur v
     LEFT JOIN filiere f ON f.code = v.filiere_code
     ORDER BY v.id'
);
$stmt->execute();
$voyageurs = $stmt->fetchAll();

$base = '../';
$titre = 'Liste des voyageurs';
require __DIR__ . '/../includes/header.php';
?>
<?php if (!$voyageurs): ?>
    <p>Aucun voyageur enregistré.</p>
<?php else: ?>
    <div class="table-scroll">
        <table class="liste">
            <thead>
                <tr>
                    <th>ID</th><th>Numéro</th><th>Type de foyer</th><th>Civilité</th><th>Nom / Prénom</th>
                    <th>Adresse</th><th>No postal</th><th>Pays</th><th>Filière d’inscription</th>
                    <th>Moyen de transport</th><th>Description</th><th>Photo</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($voyageurs as $voyageur): ?>
                    <tr>
                        <td><?= h($voyageur['id']) ?></td>
                        <td><?= h($voyageur['numero']) ?></td>
                        <td><?= h($voyageur['type_foyer']) ?></td>
                        <td><?= h($voyageur['civilite']) ?></td>
                        <td><?= h($voyageur['nom_prenom']) ?></td>
                        <td><?= h($voyageur['adresse']) ?></td>
                        <td><?= h($voyageur['code_postal']) ?></td>
                        <td><?= h($voyageur['pays']) ?></td>
                        <td><?= h($voyageur['filiere'] ?? '') ?></td>
                        <td><?= h($voyageur['transport']) ?></td>
                        <td><?= h($voyageur['description']) ?></td>
                        <td>
                            <?php if ($voyageur['photo']): ?>
                                <img src="../uploads/<?= h($voyageur['photo']) ?>" width="60" alt="Photo de <?= h($voyageur['nom_prenom']) ?>">
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="voyageurs.php?id=<?= h($voyageur['id']) ?>">Modifier</a>
                            <form method="post" action="voyageur_supprimer.php" onsubmit="return confirm('Supprimer ce voyageur définitivement ?');">
                                <input type="hidden" name="id" value="<?= h($voyageur['id']) ?>">
                                <button type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<p><a href="voyageurs.php">Retour à la gestion des voyageurs</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
