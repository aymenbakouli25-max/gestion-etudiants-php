<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_admin();
$logs = $pdo->query('SELECT a.action, a.details, a.created_at, COALESCE(u.username, "système") AS username FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 200')->fetchAll();
$pageTitle = 'Journal des opérations';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Retour à la liste</a><h1>Journal des opérations</h1><p>Dernières actions effectuées dans l’application.</p></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Détails</th></tr></thead><tbody><?php foreach ($logs as $log): ?><tr><td><?= e($log['created_at']) ?></td><td><?= e($log['username']) ?></td><td><?= e($log['action']) ?></td><td><?= e($log['details']) ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
