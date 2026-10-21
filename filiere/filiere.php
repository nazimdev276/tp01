<?php
// filiere/filiere.php : recherche et gestion des filières
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions.php';

$erreurs = [];
$message = '';
$filiere = null;
$rechercheCode = '';
$codeOriginal = '';
$code = '';
$libelle = '';
$champPost = static function (string $nom): string {
    $valeur = $_POST[$nom] ?? '';
    return is_string($valeur) ? trim($valeur) : '';
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $champPost('action');
    $codeOriginal = strtoupper($champPost('code_original'));
    $code = strtoupper($champPost('code'));
    $libelle = strtoupper($champPost('libelle'));
    if ($action === 'supprimer') {
        if ($codeOriginal === '') {
            $erreurs[] = "Recherchez une filière avant de la supprimer.";
        } else {
            try {
                $stmt = $pdo->prepare('DELETE FROM filiere WHERE code = :code');
                $stmt->execute([':code' => $codeOriginal]);
                if ($stmt->rowCount() === 1) {
                    $message = 'Filière supprimée.';
                    $codeOriginal = '';
                    $code = '';
                    $libelle = '';
                } else {
                    $erreurs[] = "Aucune filière trouvée pour ce code.";
                }
            } catch (PDOException $e) {
                $erreurs[] = $e->getCode() === '23000'
                    ? "Cette filière est utilisée par des modules ou des personnes et ne peut pas être supprimée."
                    : "Erreur de base de données.";
            }
        }
        $rechercheCode = $codeOriginal;
    } elseif ($action === 'enregistrer' || $action === 'modifier') {
        if ($code === '' || mb_strlen($code) > 10) {
            $erreurs[] = "Le code est obligatoire (10 caractères maximum).";
        }
        if ($libelle === '' || mb_strlen($libelle) > 10) {
            $erreurs[] = "La filière est obligatoire (10 caractères maximum).";
        }
        if ($action === 'modifier' && $codeOriginal === '') {
            $erreurs[] = "Recherchez une filière avant de la modifier.";
        }
        if (!$erreurs) {
            try {
                if ($action === 'modifier') {
                    $stmt = $pdo->prepare('UPDATE filiere SET code = :nouveau_code, libelle = :libelle WHERE code = :code_original');
                    $stmt->execute([':nouveau_code' => $code, ':libelle' => $libelle, ':code_original' => $codeOriginal]);
                    if ($stmt->rowCount() === 0) {
                        $stmt = $pdo->prepare('SELECT code FROM filiere WHERE code = :code');
                        $stmt->execute([':code' => $codeOriginal]);
                        if (!$stmt->fetch()) {
                            $erreurs[] = "Aucune filière trouvée pour ce code.";
                        }
                    }
                    if (!$erreurs) {
                        $message = 'Modifications enregistrées.';
                        $codeOriginal = $code;
                    }
                } else {
                    $stmt = $pdo->prepare('INSERT INTO filiere (code, libelle) VALUES (:code, :libelle)');
                    $stmt->execute([':code' => $code, ':libelle' => $libelle]);
                    $message = 'Filière enregistrée.';
                }
            } catch (PDOException $e) {
                $erreurs[] = $e->getCode() === '23000' ? "Ce code de filière existe déjà ou est utilisé." : "Erreur de base de données.";
            }
        }
        $rechercheCode = $codeOriginal !== '' ? $codeOriginal : $code;
    }
} else {
    $rechercheCode = $_GET['code'] ?? '';
    if (!is_string($rechercheCode)) {
        $erreurs[] = "Code de filière invalide.";
        $rechercheCode = '';
    } else {
        $rechercheCode = strtoupper(trim($rechercheCode));
    }
}

if ($rechercheCode !== '') {
    $stmt = $pdo->prepare('SELECT code, libelle FROM filiere WHERE code = :code');
    $stmt->execute([':code' => $rechercheCode]);
    $filiere = $stmt->fetch() ?: null;
    if ($filiere) {
        $codeOriginal = $filiere['code'];
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$erreurs) {
            $code = $filiere['code'];
            $libelle = $filiere['libelle'];
        }
    } elseif ($message === '') {
        $code = $rechercheCode;
        $message = "Aucune filière trouvée. Vous pouvez l'enregistrer avec ce code.";
    }
}

$base  = '../';
$titre = 'Gestion des filières';
require __DIR__ . '/../includes/header.php';
?>
<form method="get" action="filiere.php">
    <label for="recherche-code">Rechercher par code :</label>
    <input type="text" name="code" id="recherche-code" maxlength="10" value="<?= h($rechercheCode) ?>" required>
    <button type="submit">Rechercher</button>
</form>
<?php if ($message !== ''): ?><p class="<?= $filiere || str_starts_with($message, 'Filière') || str_starts_with($message, 'Modifications') ? 'ok' : '' ?>"><?= h($message) ?></p><?php endif; ?>
<?php foreach ($erreurs as $erreur): ?><p class="erreur"><?= h($erreur) ?></p><?php endforeach; ?>
<form id="form-filiere" method="post" action="filiere.php">
    <?php if ($filiere): ?><input type="hidden" name="code_original" value="<?= h($codeOriginal) ?>"><?php endif; ?>
    <table class="formulaire">
        <tr><td>Code :</td><td><input type="text" name="code" maxlength="10" value="<?= h($code) ?>" required></td></tr>
        <tr><td>Filière :</td><td><input type="text" name="libelle" maxlength="10" value="<?= h($libelle) ?>" required></td></tr>
    </table>
    <?php if ($filiere): ?>
        <button type="submit" name="action" value="modifier">Modifier</button>
        <button type="submit" name="action" value="supprimer" formnovalidate onclick="return confirm('Supprimer cette filière ?');">Supprimer</button>
    <?php else: ?>
        <button type="submit" name="action" value="enregistrer">Enregistrer</button>
    <?php endif; ?>
</form>
<form method="get" action="liste.php"><button type="submit">Afficher liste</button></form>
<script src="<?= $base ?>js/script.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
