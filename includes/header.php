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
    <div id="menu">
        <a href="<?= $base ?>index.php">Accueil</a> |
        <a href="<?= $base ?>personne/formulaire.php">Formulaire Personne</a> |
        <a href="<?= $base ?>nationalite/nationalite.php">Gestion Nationalités</a> |
        <a href="<?= $base ?>nationalite/liste.php">Liste Nationalités</a> |
        <a href="<?= $base ?>personne/liste.php">Liste Personnes</a>
    </div>
    <hr>
    <h2><?= h($titre) ?></h2>
