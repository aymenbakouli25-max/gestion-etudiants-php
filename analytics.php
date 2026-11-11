<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
$levels = $pdo->query('SELECT niveau, COUNT(*) AS total FROM etudiants GROUP BY niveau ORDER BY niveau')->fetchAll();
$statuses = $pdo->query('SELECT status, COUNT(*) AS total FROM student_workflow GROUP BY status')->fetchAll();
$maxLevel = max(1, ...array_map(static fn (array $row): int => (int)$row['total'], $levels));
$workflowTotal = max(1, array_sum(array_map(static fn (array $row): int => (int)$row['total'], $statuses)));
$pageTitle = 'Analytics';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Dashboard</a><h1>Analytics institutionnel</h1><p>Visualisez les tendances principales de votre établissement.</p></div>
<div class="analytics-grid"><section class="panel chart-card"><h2>Répartition par niveau</h2><?php foreach ($levels as $row): ?><div class="bar-row"><span><?= e($row['niveau']) ?></span><div class="bar"><i style="width:<?= ((int)$row['total'] / $maxLevel) * 100 ?>%"></i></div><strong><?= (int)$row['total'] ?></strong></div><?php endforeach; ?></section><section class="panel chart-card"><h2>Workflow des dossiers</h2><?php foreach ($statuses as $row): ?><div class="bar-row"><span><?= e($row['status']) ?></span><div class="bar"><i style="width:<?= ((int)$row['total'] / $workflowTotal) * 100 ?>%"></i></div><strong><?= (int)$row['total'] ?></strong></div><?php endforeach; ?></section></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
