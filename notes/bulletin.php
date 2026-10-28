<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$idEtudiant = '';
$etudiant = null;
$modules = [];
$moduleSelectionne = '';
$codeModule = '';
$coefficient = '';
$note = '';
$noteExiste = false;
$message = '';
$messageType = 'erreur';
$action = 'rechercher';

$stmt = $pdo->prepare('SELECT code, designation FROM module ORDER BY designation');
$stmt->execute();
$modules = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['enregistrer']) ? 'enregistrer' : (isset($_POST['modifier']) ? 'modifier' : (isset($_POST['supprimer']) ? 'supprimer' : ($_POST['action'] ?? '')));
    $idPost = $_POST['id'] ?? '';
    $idEtudiant = is_string($idPost) ? trim($idPost) : '';
    $idValide = filter_var($idEtudiant, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idValide === false && $idEtudiant === '' && isset($_POST['numero']) && is_string($_POST['numero'])) {
        $stmt = $pdo->prepare('SELECT id FROM personne WHERE numero = :numero LIMIT 1');
        $stmt->execute([':numero' => trim($_POST['numero'])]);
        $idValide = $stmt->fetchColumn();
        if ($idValide !== false) {
            $idEtudiant = (string)$idValide;
        }
    }
    $modulePost = $_POST['module'] ?? '';
    $moduleSelectionne = is_string($modulePost) ? trim($modulePost) : '';
    $notePost = $_POST['note'] ?? '';
    $note = is_string($notePost) ? trim($notePost) : '';
    if (!in_array($action, ['enregistrer', 'modifier'], true)) {
        $note = '';
    }

    if (in_array($action, ['rechercher', 'enregistrer', 'modifier', 'supprimer'], true)) {
        if ($idValide === false) {
            $message = 'Etudiant Inéxistant';
        } else {
            $stmt = $pdo->prepare(
                'SELECT p.civilite, p.nom_prenom, p.photo, f.libelle AS filiere, p.filiere_code
                 FROM personne p
                 LEFT JOIN filiere f ON f.code = p.filiere_code
                 WHERE p.id = :id'
            );
            $stmt->execute([':id' => $idValide]);
            $etudiant = $stmt->fetch() ?: null;
        }

        if ($etudiant) {
            if (in_array($action, ['enregistrer', 'modifier', 'supprimer'], true) && $moduleSelectionne === '') {
                $message = 'Veuillez choisir un module.';
            }

            if ($moduleSelectionne !== '') {
                $stmt = $pdo->prepare(
                    'SELECT m.code, m.designation, m.coefficient, f.libelle AS filiere
                     FROM module m
                     JOIN filiere f ON f.code = m.filiere_code
                     WHERE m.code = :code'
                );
                $stmt->execute([':code' => $moduleSelectionne]);
                $moduleCourant = $stmt->fetch();

                if ($moduleCourant) {
                    $codeModule = $moduleCourant['code'];
                    $coefficient = $moduleCourant['coefficient'];

                    if ($action === 'supprimer') {
                        $stmt = $pdo->prepare(
                            'SELECT code_module, coefficient, `Note`
                             FROM Notes
                             WHERE Num_Etudiant = :id_etudiant AND Nom_module = :nom_module'
                        );
                        $stmt->execute([
                            ':id_etudiant' => $idValide,
                            ':nom_module' => $moduleCourant['designation'],
                        ]);
                        $noteExistante = $stmt->fetch();

                        if (!$noteExistante) {
                            $message = 'Note Inéxistante pour ce module';
                        } else {
                            $noteExiste = true;
                            $codeModule = $noteExistante['code_module'];
                            $coefficient = $noteExistante['coefficient'];
                            $note = $noteExistante['Note'];
                            try {
                                $stmt = $pdo->prepare(
                                    'DELETE FROM Notes
                                     WHERE Num_Etudiant = :id_etudiant AND Nom_module = :nom_module'
                                );
                                $stmt->execute([
                                    ':id_etudiant' => $idValide,
                                    ':nom_module' => $moduleCourant['designation'],
                                ]);
                                $message = 'Note supprimée avec succès.';
                                $messageType = 'ok';
                                $noteExiste = false;
                            } catch (PDOException $e) {
                                $message = 'Erreur lors de la suppression de la note.';
                            }
                        }
                    } elseif (in_array($action, ['enregistrer', 'modifier'], true)) {
                        $noteExistante = null;
                        if ($action === 'modifier') {
                            $stmt = $pdo->prepare(
                                'SELECT 1 FROM Notes
                                 WHERE Num_Etudiant = :id_etudiant AND Nom_module = :nom_module'
                            );
                            $stmt->execute([
                                ':id_etudiant' => $idValide,
                                ':nom_module' => $moduleCourant['designation'],
                            ]);
                            $noteExistante = $stmt->fetchColumn();
                            $noteExiste = (bool)$noteExistante;
                        }

                        if ($action === 'modifier' && !$noteExistante) {
                            $message = "Aucune note n'existe pour cet étudiant et ce module : rien à modifier.";
                            $messageType = 'petit';
                        } elseif (!preg_match('/^-?(?:0|[1-9][0-9]{0,2})(?:\.[0-9]{1,2})?$/', $note)) {
                            $message = 'Veuillez saisir une note valide (maximum 2 décimales).';
                        } elseif ($action === 'modifier') {
                            try {
                                $stmt = $pdo->prepare(
                                    'UPDATE Notes
                                     SET Filiere = :filiere, code_module = :code_module,
                                         coefficient = :coefficient, `Note` = :note
                                     WHERE Num_Etudiant = :id_etudiant AND Nom_module = :nom_module'
                                );
                                $stmt->execute([
                                    ':filiere' => $moduleCourant['filiere'],
                                    ':code_module' => $moduleCourant['code'],
                                    ':coefficient' => $moduleCourant['coefficient'],
                                    ':note' => $note,
                                    ':id_etudiant' => $idValide,
                                    ':nom_module' => $moduleCourant['designation'],
                                ]);
                                $codeModule = $moduleCourant['code'];
                                $coefficient = $moduleCourant['coefficient'];
                                $message = 'Note et informations du module modifiées avec succès.';
                                $messageType = 'ok';
                                $noteExiste = true;
                            } catch (PDOException $e) {
                                $message = 'Erreur lors de la modification de la note.';
                            }
                        } else {
                            try {
                                $stmt = $pdo->prepare(
                                    'INSERT INTO Notes
                                     (Num_Etudiant, Filiere, Nom_module, code_module, coefficient, `Note`)
                                     VALUES (:id_etudiant, :filiere, :nom_module, :code_module, :coefficient, :note)'
                                );
                                $stmt->execute([
                                    ':id_etudiant' => $idValide,
                                    ':filiere' => $moduleCourant['filiere'],
                                    ':nom_module' => $moduleCourant['designation'],
                                    ':code_module' => $moduleCourant['code'],
                                    ':coefficient' => $moduleCourant['coefficient'],
                                    ':note' => $note,
                                ]);
                                $message = 'Note enregistrée avec succès.';
                                $messageType = 'ok';
                                $noteExiste = true;
                            } catch (PDOException $e) {
                                if ($e->getCode() === '23000' && (int)($e->errorInfo[1] ?? 0) === 1062) {
                                    $message = 'Une note existe déjà pour cet étudiant et ce module.';
                                    $noteExiste = true;
                                } else {
                                    $message = "Erreur lors de l'enregistrement de la note.";
                                }
                            }
                        }
                    } else {
                        $stmt = $pdo->prepare(
                            'SELECT code_module, coefficient, `Note`
                             FROM Notes
                             WHERE Num_Etudiant = :id_etudiant AND Nom_module = :nom_module'
                        );
                        $stmt->execute([
                            ':id_etudiant' => $idValide,
                            ':nom_module' => $moduleCourant['designation'],
                        ]);
                        $noteExistante = $stmt->fetch();

                        if ($noteExistante) {
                            $noteExiste = true;
                            $codeModule = $noteExistante['code_module'];
                            $coefficient = $noteExistante['coefficient'];
                            $note = $noteExistante['Note'];
                        } else {
                            $message = "Aucune note n'existe pour ce module. Saisissez-la puis enregistrez.";
                            $messageType = 'petit';
                        }
                    }
                } else {
                    $moduleSelectionne = '';
                    $note = '';
                    if (in_array($action, ['enregistrer', 'modifier', 'supprimer'], true)) {
                        $message = 'Veuillez choisir un module valide pour cette filière.';
                    }
                }
            }
        } else {
            $message = 'Etudiant Inéxistant';
            $moduleSelectionne = '';
            $note = '';
        }
    }
}

$base = '../';
$titre = 'Saisie Bulletin de Notes';
require __DIR__ . '/../includes/header.php';
?>
<?php if ($message !== ''): ?><p class="<?= h($messageType) ?>"><?= h($message) ?></p><?php endif; ?>
<form method="get" action="liste.php">
    <button type="submit">Voir toutes les notes</button>
</form>
<form class="bulletin-form" method="post" action="bulletin.php">
    <input type="hidden" name="action" value="rechercher">
    <div class="bulletin-layout">
        <table class="formulaire">
            <tr>
                <td><label for="id">ID étudiant :</label></td>
                <td>
                    <input type="number" name="id" id="id" min="1" step="1" value="<?= h($idEtudiant) ?>" required>
                    <button type="submit">Rechercher</button>
                </td>
            </tr>
            <tr>
                <td>Civilité :</td>
                <td>
                    <label><input type="radio" name="civilite" value="Monsieur" <?= ($etudiant['civilite'] ?? '') === 'Monsieur' ? 'checked' : '' ?>> Monsieur</label>
                    <label><input type="radio" name="civilite" value="Madame" <?= ($etudiant['civilite'] ?? '') === 'Madame' ? 'checked' : '' ?>> Madame</label>
                    <label><input type="radio" name="civilite" value="Mademoiselle" <?= ($etudiant['civilite'] ?? '') === 'Mademoiselle' ? 'checked' : '' ?>> Mademoiselle</label>
                </td>
            </tr>
            <tr>
                <td><label for="nom-prenom">Nom / Prénom :</label></td>
                <td><input type="text" name="nom_prenom" id="nom-prenom" value="<?= h($etudiant['nom_prenom'] ?? '') ?>"></td>
            </tr>
            <tr>
                <td><label for="filiere">Filière :</label></td>
                <td><input type="text" name="filiere" id="filiere" value="<?= h($etudiant['filiere'] ?? '') ?>"></td>
            </tr>
            <tr>
                <td><label for="module">Module :</label></td>
                <td>
                    <select name="module" id="module" onchange="this.form.submit()">
                        <option value="">— Choisir un module —</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?= h($module['code']) ?>" <?= $moduleSelectionne === $module['code'] ? 'selected' : '' ?>><?= h($module['designation']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><label for="code-module">Code Module :</label></td>
                <td><input type="text" name="code_module" id="code-module" value="<?= h($codeModule) ?>"></td>
            </tr>
            <tr>
                <td><label for="coefficient">Coefficient :</label></td>
                <td><input type="number" name="coefficient" id="coefficient" step="0.01" value="<?= h($coefficient) ?>"></td>
            </tr>
            <tr>
                <td><label for="note">Note :</label></td>
                <td><input type="number" name="note" id="note" step="0.01" value="<?= h($note) ?>"></td>
            </tr>
        </table>
        <aside class="bulletin-photo" aria-label="Zone photo de l’étudiant">
            <?php if (!empty($etudiant['photo'])): ?>
                <img src="../uploads/<?= h($etudiant['photo']) ?>" alt="Photo de <?= h($etudiant['nom_prenom']) ?>">
            <?php else: ?>
                Photo de l’étudiant
            <?php endif; ?>
        </aside>
    </div>
    <div class="bulletin-actions">
        <button type="submit" name="enregistrer" value="1">Enregistrer</button>
        <button type="submit" name="modifier" value="1">Modifier</button>
        <button type="submit" name="supprimer" value="1" onclick="return confirm('Supprimer cette note ?');">Supprimer</button>
    </div>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
