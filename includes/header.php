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
    <h1>TP01 – PAW</h1>
    <nav id="menu" aria-label="Menu principal">
        <ul class="menu-bar">
            <li><a href="<?= $base ?>index.php">Accueil</a></li>
            <li><a href="<?= $base ?>personne/formulaire.php">Etudiants</a></li>
            <li><a href="<?= $base ?>enseignant/enseignant.php">Enseignants</a></li>
            <li><a href="<?= $base ?>module/module.php">Modules</a></li>
            <li class="dropdown">
                <span class="dropdown-label">Tables</span>
                <ul class="submenu">
                    <li><a href="<?= $base ?>nationalite/nationalite.php">Nationalité</a></li>
                    <li><a href="<?= $base ?>sport/sport.php">Sports</a></li>
                    <li><a href="<?= $base ?>filiere/filiere.php">Filières</a></li>
                </ul>
            </li>
        </ul>
    </nav>
    <hr>
    <h2><?= h($titre) ?></h2>
