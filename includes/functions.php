<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'ar'], true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

function current_lang(): string {
    return $_SESSION['lang'] ?? 'fr';
}

function t(string $key): string {
    $messages = [
        'fr' => ['dashboard' => 'Dashboard', 'students' => 'Étudiants', 'logout' => 'Déconnexion'],
        'ar' => ['dashboard' => 'لوحة التحكم', 'students' => 'الطلبة', 'logout' => 'تسجيل الخروج'],
    ];
    return $messages[current_lang()][$key] ?? $key;
}

function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function check_csrf(): void {
    if (!hash_equals(csrf_token(), (string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        exit('Jeton de sécurité invalide. Rechargez la page et réessayez.');
    }
}

function require_auth(): void {
    if (empty($_SESSION['user_id'])) {
        redirect('login.php');
    }
}

function require_admin(): void {
    require_auth();
    if (($_SESSION['role'] ?? 'admin') !== 'admin') {
        http_response_code(403);
        exit('Accès réservé aux administrateurs.');
    }
}

function login_user(int $userId, string $username): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function log_action(PDO $pdo, string $action, string $details = ''): void {
    if (empty($_SESSION['user_id'])) {
        return;
    }

    function json_response(array $payload, int $status = 200): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    function request_json(): array {
        $payload = json_decode((string)file_get_contents('php://input'), true);
        return is_array($payload) ? $payload : [];
    }
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details) VALUES (:user_id, :action, :details)');
    $stmt->execute([
        'user_id' => (int)$_SESSION['user_id'],
        'action' => $action,
        'details' => $details,
    ]);
}

function flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function redirect(string $url): never {
    header('Location: ' . $url, true, 303);
    exit;
}

function validate_student(array $data): array {
    $student = [
        'code_apogee' => trim((string)($data['code_apogee'] ?? '')),
        'nom' => trim((string)($data['nom'] ?? '')),
        'prenom' => trim((string)($data['prenom'] ?? '')),
        'email' => trim((string)($data['email'] ?? '')),
        'filiere' => trim((string)($data['filiere'] ?? '')),
        'niveau' => trim((string)($data['niveau'] ?? '')),
        'date_naissance' => trim((string)($data['date_naissance'] ?? '')),
    ];
    $errors = [];
    foreach (['code_apogee' => 20, 'nom' => 80, 'prenom' => 80, 'filiere' => 100] as $field => $max) {
        if ($student[$field] === '') {
            $errors[] = "Le champ {$field} est obligatoire.";
        } elseif (mb_strlen($student[$field]) > $max) {
            $errors[] = "Le champ {$field} dépasse {$max} caractères.";
        }
    }
    if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL) || strlen($student['email']) > 150) {
        $errors[] = 'Adresse e-mail invalide.';
    }
    if (!in_array($student['niveau'], ['1ère année', '2ème année', '3ème année', 'Bachelor'], true)) {
        $errors[] = 'Choisissez un niveau valide.';
    }
    if ($student['date_naissance'] !== '') {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $student['date_naissance']);
        if (!$date || $date->format('Y-m-d') !== $student['date_naissance'] || $date > new DateTimeImmutable('today')) {
            $errors[] = 'Date de naissance invalide.';
        }
    }
    $student['date_naissance'] = $student['date_naissance'] ?: null;
    return [$student, $errors];
}
