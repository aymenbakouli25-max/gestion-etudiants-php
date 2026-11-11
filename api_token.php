<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Méthode non autorisée.'); }
check_csrf();
$plain = bin2hex(random_bytes(32));
$stmt = $pdo->prepare('INSERT INTO api_tokens (user_id, token_hash) VALUES (:user_id, :token_hash)');
$stmt->execute(['user_id' => (int)$_SESSION['user_id'], 'token_hash' => hash('sha256', $plain)]);
flash('Token API créé : copiez-le maintenant, il ne sera plus affiché.');
$_SESSION['new_api_token'] = $plain;
redirect('admin.php');
