<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';

if (!empty($_SESSION['user_id'])) {
    redirect('index.php');
}

$errors = [];
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if ($username === '' || $password === '') {
        $errors[] = 'Saisissez votre nom d’utilisateur et votre mot de passe.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            login_user((int)$user['id'], $user['username']);
            $_SESSION['role'] = $user['role'];
            redirect('index.php');
        }
        $errors[] = 'Identifiants incorrects.';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion · EST Manager</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="auth-container">
  <section class="form-card auth-card">
    <div class="brand auth-brand"><img class="brand-logo" src="assets/esto-ump-logo.svg" alt="Logo officiel EST Oujda - UMP"><span>EST <b>Manager</b><small>Gestion des étudiants · Oujda</small></span></div>
    <h1>Connexion administrateur</h1>
    <p class="auth-description">Connectez-vous pour accéder à la gestion des étudiants.</p>
    <?php if ($errors): ?>
      <div class="alert error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <div class="auth-fields">
        <label>Nom d’utilisateur<input type="text" name="username" required autocomplete="username" value="<?= e($username) ?>"></label>
        <label>Mot de passe<input type="password" name="password" required autocomplete="current-password"></label>
      </div>
      <button class="btn btn-primary auth-submit" type="submit">Se connecter</button>
    </form>
  </section>
</main>
</body>
</html>
