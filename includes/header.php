<?php
$pageTitle = $pageTitle ?? 'Gestion des étudiants';
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> · EST Manager</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="index.php"><img class="brand-logo" src="assets/esto-ump-logo.svg" alt="Logo officiel EST Oujda - UMP"><span>EST <b>Manager</b><small>Gestion des étudiants · Oujda</small></span></a>
    <button class="nav-toggle" type="button" aria-label="Ouvrir le menu" aria-expanded="false">☰</button>
    <nav class="main-nav"><?php $currentPage = basename($_SERVER['PHP_SELF']); ?><a class="<?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php"><?= e(t('dashboard')) ?></a><a class="<?= $currentPage === 'academic.php' ? 'active' : '' ?>" href="academic.php">Académique</a><a class="<?= $currentPage === 'workflow.php' ? 'active' : '' ?>" href="workflow.php">Workflow</a><a class="<?= $currentPage === 'student_import.php' ? 'active' : '' ?>" href="student_import.php">Import</a><a class="<?= $currentPage === 'notify.php' ? 'active' : '' ?>" href="notify.php">Email</a><a class="<?= $currentPage === 'attendance_report.php' ? 'active' : '' ?>" href="attendance_report.php">Présences</a><a class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="reports.php">Rapports</a><a href="?lang=<?= current_lang() === 'fr' ? 'ar' : 'fr' ?>"><?= current_lang() === 'fr' ? 'العربية' : 'Français' ?></a><?php if (($_SESSION['role'] ?? 'admin') === 'admin'): ?><a class="<?= $currentPage === 'audit.php' ? 'active' : '' ?>" href="audit.php">Journal</a><a class="<?= $currentPage === 'admin.php' ? 'active' : '' ?>" href="admin.php">Utilisateurs</a><a href="backup.php" class="btn btn-light btn-small">Sauvegarde</a><?php endif; ?><button class="theme-toggle btn btn-light btn-small" type="button" aria-label="Changer le thème">☾</button><a class="btn btn-primary btn-small" href="create.php">+ Ajouter</a><form action="logout.php" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="btn btn-light btn-small" type="submit"><?= e(t('logout')) ?></button></form></nav>
  </div>
</header>
<main class="container">
<?php if (!empty($_SESSION['flash'])): $flashMessage = $_SESSION['flash']; unset($_SESSION['flash']); ?>
<div class="alert <?= e($flashMessage['type']) ?>" role="status" data-auto-dismiss><?= e($flashMessage['message']) ?></div>
<?php endif; ?>
