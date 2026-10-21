<?php
// module/module.php : recherche et gestion des modules
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$stmt = $pdo->prepare('SELECT code, libelle FROM filiere ORDER BY code');
$stmt->execute();
$filieres = $stmt->fetchAll();
$erreurs = [];
$message = '';
$module = null;
$idRecherche = '';
$valeurs = ['id' => '', 'code' => '', 'designation' => '', 'coefficient' => '', 'volume_horaire' => '', 'filiere_code' => ''];
$champPost = static function (string $nom): string {
    $valeur = $_POST[$nom] ?? '';
    return is_string($valeur) ? trim($valeur) : '';
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $champPost('action');
    $id = $champPost('id');
    $idValide = $id !== '' ? filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]) : false;
    if ($action === 'supprimer') {
        if ($idValide === false) {
            $erreurs[] = "Identifiant invalide.";
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM module WHERE id = :id');
                $stmt->execute([':id' => $idValide]);
                if ($stmt->rowCount() === 1) {
                    $message = 'Module supprimé.';
                    $id = '';
                } else {
                    $erreurs[] = "Aucun module trouvé pour cet identifiant.";
                }
            } catch (PDOException $e) {
                $erreurs[] = "Échec de la suppression du module.";
            }
        }
        $idRecherche = $id;
    } elseif ($action === 'enregistrer' || $action === 'modifier') {
        $valeurs = [
            'id' => $id,
            'code' => strtoupper($champPost('code')),
            'designation' => $champPost('designation'),
            'coefficient' => $champPost('coefficient'),
            'volume_horaire' => $champPost('volume_horaire'),
            'filiere_code' => $champPost('filiere'),
        ];
        if ($id !== '' && $idValide === false) {
            $erreurs[] = "L'ID doit être un entier positif.";
        }
        if ($valeurs['code'] === '' || mb_strlen($valeurs['code']) > 20) {
            $erreurs[] = "Le code module est obligatoire (20 caractères maximum).";
        }
        if ($valeurs['designation'] === '' || mb_strlen($valeurs['designation']) > 100) {
            $erreurs[] = "La désignation est obligatoire (100 caractères maximum).";
        }
        if (!preg_match('/^(?:0|[1-9][0-9]{0,2})(?:\.[0-9]{1,2})?$/', $valeurs['coefficient']) || (float)$valeurs['coefficient'] <= 0 || (float)$valeurs['coefficient'] > 999.99) {
            $erreurs[] = "Le coefficient doit être supérieur à 0 et inférieur ou égal à 999,99.";
        }
        if (!preg_match('/^(0|[1-9][0-9]{0,4})$/', $valeurs['volume_horaire']) || (int)$valeurs['volume_horaire'] > 65535) {
            $erreurs[] = "Le volume horaire doit être un entier compris entre 0 et 65535.";
        }
        if (!in_array($valeurs['filiere_code'], array_column($filieres, 'code'), true)) {
            $erreurs[] = "La filière sélectionnée est invalide.";
        }
        if ($action === 'modifier' && $idValide === false) {
            $erreurs[] = "Recherchez un module avant de le modifier.";
        }
        if (!$erreurs) {
            try {
                $params = [
                    ':code' => $valeurs['code'],
                    ':designation' => $valeurs['designation'],
                    ':coefficient' => $valeurs['coefficient'],
                    ':volume_horaire' => (int)$valeurs['volume_horaire'],
                    ':filiere' => $valeurs['filiere_code'],
                ];
                if ($action === 'modifier') {
                    $params[':id'] = (int)$idValide;
                    $stmt = $pdo->prepare(
                        'UPDATE module SET code = :code, designation = :designation, coefficient = :coefficient,
                         volume_horaire = :volume_horaire, filiere_code = :filiere WHERE id = :id'
                    );
                    $stmt->execute($params);
                    if ($stmt->rowCount() === 0) {
                        $stmt = $pdo->prepare('SELECT id FROM module WHERE id = :id');
                        $stmt->execute([':id' => (int)$idValide]);
                        if (!$stmt->fetch()) {
                            $erreurs[] = "Aucun module trouvé pour cet identifiant.";
                        }
                    }
                    if (!$erreurs) {
                        $message = 'Modifications enregistrées.';
                    }
                } else {
                    if ($id !== '') {
                        $params[':id'] = (int)$idValide;
                        $stmt = $pdo->prepare(
                            'INSERT INTO module (id, code, designation, coefficient, volume_horaire, filiere_code)
                             VALUES (:id, :code, :designation, :coefficient, :volume_horaire, :filiere)'
                        );
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO module (code, designation, coefficient, volume_horaire, filiere_code)
                             VALUES (:code, :designation, :coefficient, :volume_horaire, :filiere)'
                        );
                    }
                    $stmt->execute($params);
                    $id = $id !== '' ? $id : (string)$pdo->lastInsertId();
                    $valeurs['id'] = $id;
                    $message = 'Module enregistré.';
                }
            } catch (PDOException $e) {
                $erreurs[] = $e->getCode() === '23000' ? "Cet ID ou ce code module existe déjà." : "Erreur de base de données.";
            }
        }
        $idRecherche = $id;
    }
} else {
    $idRecherche = $_GET['id'] ?? '';
    if (!is_string($idRecherche) && !is_int($idRecherche)) {
        $erreurs[] = "Identifiant invalide.";
        $idRecherche = '';
    } else {
        $idRecherche = trim((string)$idRecherche);
    }
}

if ($idRecherche !== '') {
    $idValide = filter_var($idRecherche, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    if ($idValide === false) {
        if (!$erreurs) {
            $erreurs[] = "Identifiant invalide.";
        }
    } else {
        $stmt = $pdo->prepare('SELECT * FROM module WHERE id = :id');
        $stmt->execute([':id' => $idValide]);
        $module = $stmt->fetch() ?: null;
        if ($module) {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$erreurs) {
                $valeurs = $module;
            }
        } elseif ($message === '') {
            $message = "Aucun module trouvé. Vous pouvez l'enregistrer avec cet ID.";
            $valeurs['id'] = (string)$idValide;
        }
    }
}

$base  = '../';
$titre = 'Gestion des modules';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" action="module.php">
    <label for="recherche-id">Rechercher par ID :</label>
    <input type="number" name="id" id="recherche-id" min="1" step="1" value="<?= h($idRecherche) ?>" required>
    <button type="submit">Rechercher</button>
</form>
<?php if ($message !== ''): ?><p class="<?= $module || str_starts_with($message, 'Module') || str_starts_with($message, 'Modifications') ? 'ok' : '' ?>"><?= h($message) ?></p><?php endif; ?>
<?php foreach ($erreurs as $erreur): ?><p class="erreur"><?= h($erreur) ?></p><?php endforeach; ?>
<form id="form-module" method="post" action="module.php">
    <input type="hidden" name="id" value="<?= h($valeurs['id']) ?>">
    <table class="formulaire">
        <tr><td>Id :</td><td><input type="text" value="<?= h($valeurs['id']) ?>" readonly></td></tr>
        <tr><td>Code module :</td><td><input type="text" name="code" maxlength="20" value="<?= h($valeurs['code']) ?>" required></td></tr>
        <tr><td>Désignation module :</td><td><input type="text" name="designation" maxlength="100" value="<?= h($valeurs['designation']) ?>" required></td></tr>
        <tr><td>Coefficient :</td><td><input type="number" name="coefficient" min="0.01" max="999.99" step="0.01" value="<?= h($valeurs['coefficient']) ?>" required></td></tr>
        <tr><td>Volume horaire :</td><td><input type="number" name="volume_horaire" min="0" max="65535" step="1" value="<?= h($valeurs['volume_horaire']) ?>" required></td></tr>
        <tr>
            <td>Filière :</td>
            <td>
                <?php if (!$filieres): ?>
                    <span class="erreur">Aucune filière enregistrée. <a href="../filiere/filiere.php">En ajouter une</a></span>
                <?php else: ?>
                    <select name="filiere" required><option value="">— Choisir une filière —</option>
                        <?php foreach ($filieres as $filiere): ?>
                            <option value="<?= h($filiere['code']) ?>" <?= ($valeurs['filiere_code'] ?? '') === $filiere['code'] ? 'selected' : '' ?>><?= h($filiere['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php if ($module): ?>
        <button type="submit" name="action" value="modifier">Modifier</button>
        <button type="submit" name="action" value="supprimer" formnovalidate onclick="return confirm('Supprimer ce module ?');">Supprimer</button>
    <?php else: ?>
        <button type="submit" name="action" value="enregistrer">Enregistrer</button>
    <?php endif; ?>
</form>
<form method="get" action="liste.php"><button type="submit">Afficher liste</button></form>
<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
