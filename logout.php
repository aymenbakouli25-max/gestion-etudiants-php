<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Méthode non autorisée.');
}

check_csrf();
logout_user();
header('Location: login.php', true, 303);
exit;
