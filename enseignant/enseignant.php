<?php
// enseignant/enseignant.php : recherche et gestion des enseignants
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../includes/donnees.php';

$erreurs = [];
$message = '';
$enseignant = null;
$idRecherche = '';
$valeurs = [
    'id' => '',
    'numero' => '',
    'civilite' => '',
    'nom_prenom' => '',
    'adresse' => '',
    'date_naissance' => '',
    'lieu_naissance' => '',
    'pays' => '',
    'grade' => '',
    'specialite' => '',
];
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
                $stmt = $pdo->prepare('DELETE FROM enseignant WHERE id = :id');
                $stmt->execute([':id' => $idValide]);
                if ($stmt->rowCount() === 1) {
                    $message = 'Enseignant supprimé.';
                    $id = '';
                } else {
                    $erreurs[] = "Aucun enseignant trouvé pour cet identifiant.";
                }
            } catch (PDOException $e) {
                $erreurs[] = "Échec de la suppression de l'enseignant.";
            }
        }
        $idRecherche = $id;
    } elseif ($action === 'enregistrer' || $action === 'modifier') {
        $valeurs = [
            'id' => $id,
            'numero' => $champPost('numero'),
            'civilite' => $champPost('civilite'),
            'nom_prenom' => $champPost('nom_prenom'),
            'adresse' => $champPost('adresse'),
            'date_naissance' => $champPost('date_naissance'),
            'lieu_naissance' => $champPost('lieu_naissance'),
            'pays' => $champPost('pays'),
            'grade' => $champPost('grade'),
            'specialite' => $champPost('specialite'),
        ];
        if ($id !== '' && $idValide === false) {
            $erreurs[] = "L'ID doit être un entier positif.";
        }
        if (!preg_match('/^[0-9]{1,10}$/', $valeurs['numero'])) {
            $erreurs[] = "Le numéro est obligatoire (10 chiffres maximum).";
        }
        if (!in_array($valeurs['civilite'], $CIVILITES, true)) {
            $erreurs[] = "La civilité est invalide.";
        }
        if ($valeurs['nom_prenom'] === '' || mb_strlen($valeurs['nom_prenom']) > 80) {
            $erreurs[] = "Le nom / prénom est obligatoire (80 caractères maximum).";
        }
        if ($valeurs['adresse'] === '' || mb_strlen($valeurs['adresse']) > 150) {
            $erreurs[] = "L'adresse est obligatoire (150 caractères maximum).";
        }
        $dateValide = DateTimeImmutable::createFromFormat('!Y-m-d', $valeurs['date_naissance']);
        $erreursDate = DateTimeImmutable::getLastErrors();
        if (!$dateValide || $dateValide->format('Y-m-d') !== $valeurs['date_naissance'] || ($erreursDate !== false && ($erreursDate['warning_count'] > 0 || $erreursDate['error_count'] > 0))) {
            $erreurs[] = "La date de naissance est invalide.";
        }
        if ($valeurs['lieu_naissance'] === '' || mb_strlen($valeurs['lieu_naissance']) > 80) {
            $erreurs[] = "Le lieu de naissance est obligatoire (80 caractères maximum).";
        }
        if (!in_array($valeurs['pays'], $PAYS, true)) {
            $erreurs[] = "Le pays est invalide.";
        }
        if (!in_array($valeurs['grade'], $GRADES_ENSEIGNANT, true)) {
            $erreurs[] = "Le grade est invalide.";
        }
        if (!in_array($valeurs['specialite'], $SPECIALITES_ENSEIGNANT, true)) {
            $erreurs[] = "La spécialité est invalide.";
        }
        if ($action === 'modifier' && $idValide === false) {
            $erreurs[] = "Recherchez un enseignant avant de le modifier.";
        }
        if (!$erreurs) {
            try {
                $params = [
                    ':numero' => $valeurs['numero'],
                    ':civilite' => $valeurs['civilite'],
                    ':nom_prenom' => $valeurs['nom_prenom'],
                    ':adresse' => $valeurs['adresse'],
                    ':date_naissance' => $valeurs['date_naissance'],
                    ':lieu_naissance' => $valeurs['lieu_naissance'],
                    ':pays' => $valeurs['pays'],
                    ':grade' => $valeurs['grade'],
                    ':specialite' => $valeurs['specialite'],
                ];
                if ($action === 'modifier') {
                    $params[':id'] = (int)$idValide;
                    $stmt = $pdo->prepare(
                        'UPDATE enseignant SET numero = :numero, civilite = :civilite, nom_prenom = :nom_prenom,
                         adresse = :adresse, date_naissance = :date_naissance, lieu_naissance = :lieu_naissance,
                         pays = :pays, grade = :grade, specialite = :specialite WHERE id = :id'
                    );
                    $stmt->execute($params);
                    if ($stmt->rowCount() === 0) {
                        $stmt = $pdo->prepare('SELECT id FROM enseignant WHERE id = :id');
                        $stmt->execute([':id' => (int)$idValide]);
                        if (!$stmt->fetch()) {
                            $erreurs[] = "Aucun enseignant trouvé pour cet identifiant.";
                        }
                    }
                    if (!$erreurs) {
                        $message = 'Modifications enregistrées.';
                    }
                } else {
                    if ($id !== '') {
                        $params[':id'] = (int)$idValide;
                        $stmt = $pdo->prepare(
                            'INSERT INTO enseignant (id, numero, civilite, nom_prenom, adresse, date_naissance, lieu_naissance, pays, grade, specialite)
                             VALUES (:id, :numero, :civilite, :nom_prenom, :adresse, :date_naissance, :lieu_naissance, :pays, :grade, :specialite)'
                        );
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO enseignant (numero, civilite, nom_prenom, adresse, date_naissance, lieu_naissance, pays, grade, specialite)
                             VALUES (:numero, :civilite, :nom_prenom, :adresse, :date_naissance, :lieu_naissance, :pays, :grade, :specialite)'
                        );
                    }
                    $stmt->execute($params);
                    $id = $id !== '' ? $id : (string)$pdo->lastInsertId();
                    $valeurs['id'] = $id;
                    $message = 'Enseignant enregistré.';
                }
            } catch (PDOException $e) {
                $erreurs[] = $e->getCode() === '23000' ? "Cet ID existe déjà." : "Erreur de base de données.";
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
        $stmt = $pdo->prepare('SELECT * FROM enseignant WHERE id = :id');
        $stmt->execute([':id' => $idValide]);
        $enseignant = $stmt->fetch() ?: null;
        if ($enseignant) {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$erreurs) {
                $valeurs = $enseignant;
            }
        } elseif ($message === '') {
            $message = "Aucun enseignant trouvé. Vous pouvez l'enregistrer avec cet ID.";
            $valeurs['id'] = (string)$idValide;
        }
    }
}

$base  = '../';
$titre = 'Gestion des enseignants';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" action="enseignant.php">
    <label for="recherche-id">Rechercher par ID :</label>
    <input type="number" name="id" id="recherche-id" min="1" step="1" value="<?= h($idRecherche) ?>" required>
    <button type="submit">Rechercher</button>
</form>
<?php if ($message !== ''): ?><p class="<?= $enseignant || str_starts_with($message, 'Enseignant') || str_starts_with($message, 'Modifications') ? 'ok' : '' ?>"><?= h($message) ?></p><?php endif; ?>
<?php foreach ($erreurs as $erreur): ?><p class="erreur"><?= h($erreur) ?></p><?php endforeach; ?>
<form id="form-enseignant" method="post" action="enseignant.php">
    <input type="hidden" name="id" value="<?= h($valeurs['id']) ?>">
    <table class="formulaire">
        <tr><td>Id :</td><td><input type="text" value="<?= h($valeurs['id']) ?>" readonly></td></tr>
        <tr><td>Numéro :</td><td><input type="text" name="numero" maxlength="10" value="<?= h($valeurs['numero']) ?>" required></td></tr>
        <tr>
            <td>Civilité :</td>
            <td><select name="civilite" required><option value="">— Choisir —</option>
                <?php foreach ($CIVILITES as $civilite): ?><option value="<?= h($civilite) ?>" <?= $valeurs['civilite'] === $civilite ? 'selected' : '' ?>><?= h($civilite) ?></option><?php endforeach; ?>
            </select></td>
        </tr>
        <tr><td>Nom / Prénom :</td><td><input type="text" name="nom_prenom" maxlength="80" value="<?= h($valeurs['nom_prenom']) ?>" required></td></tr>
        <tr><td>Adresse :</td><td><input type="text" name="adresse" maxlength="150" value="<?= h($valeurs['adresse']) ?>" required></td></tr>
        <tr><td>Date de naissance :</td><td><input type="date" name="date_naissance" value="<?= h($valeurs['date_naissance']) ?>" required></td></tr>
        <tr><td>Lieu de naissance :</td><td><input type="text" name="lieu_naissance" maxlength="80" value="<?= h($valeurs['lieu_naissance']) ?>" required></td></tr>
        <tr>
            <td>Pays :</td>
            <td><select name="pays" required><option value="">— Choisir —</option>
                <?php foreach ($PAYS as $pays): ?><option value="<?= h($pays) ?>" <?= $valeurs['pays'] === $pays ? 'selected' : '' ?>><?= h($pays) ?></option><?php endforeach; ?>
            </select></td>
        </tr>
        <tr>
            <td>Grade :</td>
            <td><select name="grade" required><option value="">— Choisir —</option>
                <?php foreach ($GRADES_ENSEIGNANT as $grade): ?><option value="<?= h($grade) ?>" <?= $valeurs['grade'] === $grade ? 'selected' : '' ?>><?= h($grade) ?></option><?php endforeach; ?>
            </select></td>
        </tr>
        <tr>
            <td>Spécialité :</td>
            <td><select name="specialite" required><option value="">— Choisir —</option>
                <?php foreach ($SPECIALITES_ENSEIGNANT as $specialite): ?><option value="<?= h($specialite) ?>" <?= $valeurs['specialite'] === $specialite ? 'selected' : '' ?>><?= h($specialite) ?></option><?php endforeach; ?>
            </select></td>
        </tr>
    </table>
    <?php if ($enseignant): ?>
        <button type="submit" name="action" value="modifier">Modifier</button>
        <button type="submit" name="action" value="supprimer" formnovalidate onclick="return confirm('Supprimer cet enseignant ?');">Supprimer</button>
    <?php else: ?>
        <button type="submit" name="action" value="enregistrer">Enregistrer</button>
    <?php endif; ?>
</form>
<form method="get" action="liste.php"><button type="submit">Afficher liste</button></form>
<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
