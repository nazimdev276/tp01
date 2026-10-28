<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
$filiere = $_GET['filiere'] ?? '';
if (!is_string($filiere) || $filiere === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Filière invalide.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT code, designation
     FROM module
     WHERE filiere_code = :filiere
     ORDER BY designation'
);
$stmt->execute([':filiere' => $filiere]);
echo json_encode(['modules' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
