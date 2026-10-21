<?php
// index.php : page d'accueil
require_once __DIR__ . '/includes/fonctions.php';
$base  = '';
$titre = 'Accueil';
require __DIR__ . '/includes/header.php';
?>
<p class="home-intro">Application de saisie des personnes et des enseignants, avec gestion des nationalités, filières, transports et sports.</p>
<ol class="home-links">
    <li><a href="nationalite/nationalite.php">Gestion Nationalités</a> : ajouter une nationalité (ex. DZ / ALGERIE).</li>
    <li><a href="filiere/filiere.php">Gestion Filières</a> : ajouter une filière d’inscription (ex. M2RSSI / 4ING).</li>
    <li><a href="module/module.php">Gestion Modules</a> : enregistrer un module associé à une filière.</li>
    <li><a href="sport/sport.php">Gestion Sports</a> : ajouter un sport disponible dans le formulaire Personne.</li>
    <li><a href="enseignant/enseignant.php">Formulaire Enseignants</a> : enregistrer un enseignant et ses informations professionnelles.</li>
    <li><a href="personne/formulaire.php">Formulaire Personne</a> : les listes « Nationalité » et « Filière d’inscription » sont lues dans MySQL.</li>
    <li><a href="personne/liste.php">Liste Personnes</a> : voir les personnes enregistrées.</li>
    <li><a href="tests.php">Diagnostic du site</a> : vérifier automatiquement les pages et les opérations principales.</li>
</ol>
<?php require __DIR__ . '/includes/footer.php'; ?>
