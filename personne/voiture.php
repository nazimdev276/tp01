<?php
// personne/voiture.php : recherche un étudiant et affiche ses informations personnelles
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$idRecherche = $_GET['id'] ?? '';
$erreurRecherche = null;
$personne = null;
$transports = [];
$idValide = null;

if (!is_string($idRecherche) && !is_int($idRecherche)) {
    $erreurRecherche = 'Identifiant invalide.';
    $idRecherche = '';
} elseif ($idRecherche !== '') {
    $idRecherche = trim((string)$idRecherche);
    $idValide = filter_var($idRecherche, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 2147483647],
    ]);
    if ($idValide === false) {
        $erreurRecherche = 'Identifiant invalide.';
        $idValide = null;
    } else {
        $stmt = $pdo->prepare(
            'SELECT pays, nom_prenom, code_postal, numero, adresse, civilite, photo
             FROM personne
             WHERE id = :id'
        );
        $stmt->execute([':id' => $idValide]);
        $personne = $stmt->fetch() ?: null;
        if ($personne !== null) {
            $stmt = $pdo->prepare('SELECT libelle, description FROM moyen_transport ORDER BY libelle');
            $stmt->execute();
            $transports = $stmt->fetchAll();
        }
    }
}

$base = '../';
$titre = 'Recherche par ID';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" action="voiture.php">
    <label for="recherche-id">ID de l’étudiant :</label>
    <input type="text" name="id" id="recherche-id" inputmode="numeric" pattern="[1-9][0-9]*"
           value="<?= h($idRecherche) ?>" required>
    <button type="submit">Rechercher</button>
</form>

<?php if ($erreurRecherche !== null): ?>
    <p class="erreur"><?= h($erreurRecherche) ?></p>
<?php elseif ($idValide !== null && $personne === null): ?>
    <p>Aucun étudiant trouvé pour l’ID <?= h($idValide) ?>.</p>
<?php elseif ($personne !== null): ?>
    <p class="ok">Informations personnelles de l’étudiant ID <?= h($idValide) ?> :</p>
    <div class="table-scroll">
        <table class="liste">
            <thead>
                <tr>
                    <th>Pays</th>
                    <th>Nom / Prénom</th>
                    <th>Code postal</th>
                    <th>Numéro</th>
                    <th>Adresse</th>
                    <th>Civilité</th>
                    <th>Photo</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= h($personne['pays']) ?></td>
                    <td><?= h($personne['nom_prenom']) ?></td>
                    <td><?= h($personne['code_postal']) ?></td>
                    <td><?= h($personne['numero']) ?></td>
                    <td><?= h($personne['adresse']) ?></td>
                    <td><?= h($personne['civilite']) ?></td>
                    <td>
                        <?php if ($personne['photo']): ?>
                            <img src="../uploads/<?= h($personne['photo']) ?>" width="80" alt="Photo de <?= h($personne['nom_prenom']) ?>">
                        <?php else: ?>
                            Aucune photo
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <h2>Moyens de transport disponibles</h2>
    <?php if ($transports): ?>
        <div class="table-scroll">
            <table class="liste">
                <thead>
                    <tr><th>Moyen de transport</th><th>Description</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($transports as $transport): ?>
                        <tr>
                            <td><?= h($transport['libelle']) ?></td>
                            <td><?= h($transport['description']) ?></td>
                            <td>
                                <form method="post" action="../transport/supprimer.php" onsubmit="return confirm('Supprimer ce moyen de transport ?');">
                                    <input type="hidden" name="libelle" value="<?= h($transport['libelle']) ?>">
                                    <input type="hidden" name="retour" value="voyageur">
                                    <input type="hidden" name="id" value="<?= h($idValide) ?>">
                                    <button type="submit">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>Aucun moyen de transport n’est enregistré.</p>
    <?php endif; ?>
    <h2>Ajouter un moyen de transport</h2>
    <form method="post" action="../transport/enregistrer.php">
        <input type="hidden" name="retour" value="voyageur">
        <input type="hidden" name="id" value="<?= h($idValide) ?>">
        <label for="transport-libelle">Moyen de transport :</label>
        <input type="text" name="libelle" id="transport-libelle" maxlength="20" required>
        <label for="transport-description">Description :</label>
        <textarea name="description" id="transport-description" maxlength="500" rows="4" cols="40"></textarea>
        <button type="submit">Ajouter</button>
    </form>
<?php endif; ?>

<p><a href="formulaire.php">Retour au formulaire étudiant</a></p>
<?php require __DIR__ . '/../includes/footer.php'; ?>
