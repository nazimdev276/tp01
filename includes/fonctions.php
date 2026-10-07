<?php
// includes/fonctions.php : petites fonctions utilitaires

// Échappe le texte avant de l'afficher en HTML (protège contre XSS)
function h($texte): string
{
    return htmlspecialchars((string)$texte, ENT_QUOTES, 'UTF-8');
}

// Récupère une liste de cases cochées (checkbox) en gardant seulement les valeurs autorisées
function post_liste(string $nom, array $autorises): array
{
    $recu = $_POST[$nom] ?? [];
    if (!is_array($recu)) {
        return [];
    }
    return array_values(array_intersect($autorises, $recu));
}

// Gère l'upload d'une image.
// Retourne [nom_du_fichier_ou_null, message_erreur_ou_null]
// - Aucun fichier envoyé  => [null, null]
function enregistrer_image(array $fichier): array
{
    if (!isset($fichier['error']) || $fichier['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        return [null, "Erreur pendant l'envoi de l'image."];
    }
    // Taille maximale : 2 Mo
    if ($fichier['size'] > 2 * 1024 * 1024) {
        return [null, "Image trop grande (2 Mo maximum)."];
    }
    // On vérifie le VRAI type du fichier (pas seulement son extension)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($fichier['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
    if (!isset($extensions[$mime]) || getimagesize($fichier['tmp_name']) === false) {
        return [null, "Type d'image refusé (JPG, PNG ou GIF uniquement)."];
    }
    // Nom aléatoire + extension choisie par nous : impossible d'envoyer un fichier .php
    $nom = bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    $destination = __DIR__ . '/../uploads/' . $nom;
    if (!move_uploaded_file($fichier['tmp_name'], $destination)) {
        return [null, "Impossible d'enregistrer l'image (dossier uploads/ accessible en écriture ?)."];
    }
    return [$nom, null];
}
