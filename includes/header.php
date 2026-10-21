<?php
// includes/header.php
// Avant l'inclusion, la page doit définir : $base (chemin vers la racine) et $titre
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TP01 - <?= h($titre) ?></title>
    <link rel="stylesheet" href="<?= $base ?>css/style.css">
</head>
<body>
<div id="page">
    <header class="site-header">
        <a class="site-brand" href="<?= $base ?>index.php">
            <span class="brand-mark" aria-hidden="true">TP</span>
            <span><strong>TP01</strong><small>PAW · Gestion universitaire</small></span>
        </a>
    </header>
    <nav class="navbar" aria-label="Navigation principale">
        <ul class="navbar-links">
            <li><a href="<?= $base ?>index.php">Accueil</a></li>
            <li><a href="<?= $base ?>personne/formulaire.php">Étudiants</a></li>
            <li><a href="<?= $base ?>enseignant/enseignant.php">Enseignants</a></li>
            <li><a href="<?= $base ?>module/module.php">Modules</a></li>
            <li>
                <details class="nav-dropdown">
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
