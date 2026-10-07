<?php
// config/database.php
// Connexion unique à MySQL (PDO). Toutes les autres pages font require_once de ce fichier.
// Si besoin, modifiez UNIQUEMENT ces 4 valeurs.
$host   = 'localhost';
$dbname = 'tp01_paw';
$user   = 'root';   // XAMPP : root
$pass   = '';       // XAMPP : mot de passe vide par défaut

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // erreurs = exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // résultats en tableaux associatifs
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion à la base de données. Vérifiez que MySQL est démarré et que database.sql est importé.');
}
