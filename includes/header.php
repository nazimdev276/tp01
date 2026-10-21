<?php
// includes/header.php
// Avant l'inclusion, la page doit définir : $base (chemin vers la racine) et $titre
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TP01 - <?= h($titre) ?></title>
    <link rel="stylesheet" href="<?= $base ?>css/style.css?v=<?= filemtime(__DIR__ . '/../css/style.css') ?>">
</head>
<body>
<div id="page">
    <?php
    $pageScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $pagePath = $_SERVER['SCRIPT_NAME'] ?? '';
    $etudiantsActif = str_contains($pagePath, '/personne/');
    $enseignantsActif = str_contains($pagePath, '/enseignant/');
    $modulesActif = str_contains($pagePath, '/module/');
    $diagnosticActif = $pageScript === 'tests.php';
    $tablesActives = in_array($pageScript, ['nationalite.php', 'sport.php', 'filiere.php'], true);
    ?>
    <header class="site-header">
        <a class="site-brand" href="<?= $base ?>index.php">
            <span class="brand-mark" aria-hidden="true">TP</span>
            <span><strong>TP01</strong><small>PAW · Gestion universitaire</small></span>
        </a>
    </header>
    <nav class="navbar" aria-label="Navigation principale">
        <ul class="navbar-links">
            <li><a href="<?= $base ?>index.php" <?= $pageScript === 'index.php' ? 'aria-current="page"' : '' ?>>Accueil</a></li>
            <li><a href="<?= $base ?>personne/formulaire.php" <?= $etudiantsActif ? 'aria-current="page"' : '' ?>>Étudiants</a></li>
            <li><a href="<?= $base ?>enseignant/enseignant.php" <?= $enseignantsActif ? 'aria-current="page"' : '' ?>>Enseignants</a></li>
            <li><a href="<?= $base ?>module/module.php" <?= $modulesActif ? 'aria-current="page"' : '' ?>>Modules</a></li>
            <li><a href="<?= $base ?>tests.php" <?= $diagnosticActif ? 'aria-current="page"' : '' ?>>Diagnostic</a></li>
            <li>
                <details class="nav-dropdown<?= $tablesActives ? ' nav-active' : '' ?>">
                    <summary>Tables</summary>
                    <ul class="submenu">
                        <li><a href="<?= $base ?>nationalite/nationalite.php">Nationalités</a></li>
                        <li><a href="<?= $base ?>sport/sport.php">Sports</a></li>
                        <li><a href="<?= $base ?>filiere/filiere.php">Filières</a></li>
                    </ul>
                </details>
            </li>
        </ul>
    </nav>
    <main>
        <h1 class="page-title"><?= h($titre) ?></h1>
