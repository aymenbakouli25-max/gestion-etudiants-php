<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_admin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = (string)($_POST['action'] ?? 'create');
    if ($action === 'change_password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $check = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
        $check->execute(['id' => (int)$_SESSION['user_id']]);
        $account = $check->fetch();
        if (!$account || !password_verify($current, $account['password_hash'])) $errors[] = 'Mot de passe actuel incorrect.';
        if (strlen($new) < 8) $errors[] = 'Le nouveau mot de passe doit contenir au moins 8 caractères.';
        if (!$errors) {
            $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
            $update->execute(['password_hash' => password_hash($new, PASSWORD_DEFAULT), 'id' => (int)$_SESSION['user_id']]);
            flash('Mot de passe modifié avec succès.');
            redirect('admin.php');
        }
    }
    elseif ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id && $id !== (int)$_SESSION['user_id']) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);
            flash('Utilisateur supprimé.');
        }
        redirect('admin.php');
    }
    else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($username === '' || !preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) $errors[] = 'Nom d’utilisateur invalide (3 à 50 caractères).';
        if (strlen($password) < 8) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        if (!$errors) {
            try {
                $role = in_array($_POST['role'] ?? '', ['admin', 'staff'], true) ? $_POST['role'] : 'staff';
                $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)');
                $stmt->execute(['username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
                flash('Utilisateur ajouté avec succès.');
                redirect('admin.php');
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') $errors[] = 'Ce nom d’utilisateur existe déjà.';
                else throw $e;
            }
        }
    }
}
$users = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY username')->fetchAll();
$newApiToken = $_SESSION['new_api_token'] ?? null;
unset($_SESSION['new_api_token']);
$pageTitle = 'Gestion des utilisateurs';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Retour à la liste</a><h1>Gestion des utilisateurs</h1><p>Ajoutez les comptes autorisés à administrer l’application.</p></div>
<?php if ($newApiToken): ?><div class="alert">Token API (à conserver) : <code><?= e($newApiToken) ?></code></div><?php endif; ?>
<form method="post" action="api_token.php" class="form-card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><h2>Intégration API</h2><p>Créez un token pour les intégrations externes.</p><button class="btn btn-outline" type="submit">Générer un token API</button></form>
<form method="post" class="form-card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="change_password"><h2>Modifier mon mot de passe</h2><div class="form-grid"><label>Mot de passe actuel<input type="password" name="current_password" required></label><label>Nouveau mot de passe<input type="password" name="new_password" minlength="8" required></label></div><div class="form-actions"><button class="btn btn-outline" type="submit">Modifier le mot de passe</button></div></form>
<form method="post" class="form-card">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php if ($errors): ?><div class="alert error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <div class="form-grid"><label>Nom d’utilisateur<input type="text" name="username" required maxlength="50"></label><label>Mot de passe<input type="password" name="password" required minlength="8"></label><label>Rôle<select name="role"><option value="staff">Employé</option><option value="admin">Administrateur</option></select></label></div>
  <div class="form-actions"><button class="btn btn-primary" type="submit">Ajouter l’utilisateur</button></div>
</form>
<section class="panel"><div class="panel-title"><div><h2>Comptes existants</h2><p><?= count($users) ?> utilisateur(s)</p></div></div><div class="table-wrap"><table><thead><tr><th>Utilisateur</th><th>Rôle</th><th>Créé le</th><th>Action</th></tr></thead><tbody><?php foreach ($users as $user): ?><tr><td><?= e($user['username']) ?></td><td><?= $user['role'] === 'admin' ? 'Administrateur' : 'Employé' ?></td><td><?= e($user['created_at']) ?></td><td><?php if ((int)$user['id'] !== (int)$_SESSION['user_id']): ?><form method="post" onsubmit="return confirm('Supprimer cet utilisateur ?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$user['id'] ?>"><button class="btn btn-danger btn-small" type="submit">Supprimer</button></form><?php else: ?>Compte actuel<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
