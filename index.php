<?php
// index.php : page d'accueil
require_once __DIR__ . '/includes/fonctions.php';
$base  = '';
$titre = 'Accueil';
require __DIR__ . '/includes/header.php';
?>
<p>Application de saisie des personnes avec gestion des nationalités.</p>
<ol>
    <li><a href="nationalite/nationalite.php">Gestion Nationalités</a> : ajouter une nationalité (ex. DZ / ALGERIE).</li>
    <li><a href="personne/formulaire.php">Formulaire Personne</a> : la liste « Nationalité » est lue dans MySQL.</li>
    <li><a href="personne/liste.php">Liste Personnes</a> : voir les personnes enregistrées.</li>
</ol>
<?php require __DIR__ . '/includes/footer.php'; ?>
