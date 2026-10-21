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
        <a href="<?= $base ?>filiere/filiere.php">Gestion Filières</a> |
        <a href="<?= $base ?>filiere/liste.php">Liste Filières</a> |
        <a href="<?= $base ?>module/module.php">Gestion Modules</a> |
        <a href="<?= $base ?>module/liste.php">Liste Modules</a> |
        <a href="<?= $base ?>transport/transport.php">Gestion Transports</a> |
        <a href="<?= $base ?>transport/liste.php">Liste Transports</a> |
        <a href="<?= $base ?>sport/sport.php">Gestion Sports</a> |
        <a href="<?= $base ?>sport/liste.php">Liste Sports</a> |
        <a href="<?= $base ?>enseignant/enseignant.php">Gestion Enseignants</a> |
        <a href="<?= $base ?>enseignant/liste.php">Liste Enseignants</a> |
        <a href="<?= $base ?>personne/liste.php">Liste Personnes</a>
    </div>
    <hr>
    <h2><?= h($titre) ?></h2>
