<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$stmt = $pdo->prepare('SELECT original_name, stored_name, mime_type FROM documents WHERE id = :id');
$stmt->execute(['id' => $id]);
$document = $stmt->fetch();
if (!$document) { http_response_code(404); exit('Document introuvable.'); }
$path = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . $document['stored_name'];
if (!is_file($path)) { http_response_code(404); exit('Fichier introuvable.'); }
header('Content-Type: ' . $document['mime_type']);
header('Content-Disposition: inline; filename="' . str_replace('"', '', $document['original_name']) . '"');
readfile($path);
