<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
$date = trim((string)($_GET['date'] ?? date('Y-m-d')));
$validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
if (!$validDate || $validDate->format('Y-m-d') !== $date) $date = date('Y-m-d');
$stmt = $pdo->prepare('SELECT e.id, e.code_apogee, e.nom, e.prenom, e.filiere, COALESCE(a.status, "unrecorded") AS status FROM etudiants e LEFT JOIN attendance a ON a.student_id = e.id AND a.attendance_date = :date ORDER BY e.nom, e.prenom');
$stmt->execute(['date' => $date]);
$records = $stmt->fetchAll();
$pageTitle = 'Rapport de présence';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Retour à la liste</a><h1>Rapport de présence</h1><p>Suivi de tous les étudiants pour une date donnée.</p></div>
<form method="get" class="filters"><input type="date" name="date" value="<?= e($date) ?>" required><button class="btn btn-primary" type="submit">Afficher</button></form>
<section class="panel"><div class="panel-title"><div><h2>Présence du <?= e($date) ?></h2><p><?= count($records) ?> étudiant(s)</p></div></div><div class="table-wrap"><table><thead><tr><th>Étudiant</th><th>Code</th><th>Filière</th><th>Statut</th></tr></thead><tbody><?php foreach ($records as $record): ?><tr><td><?= e($record['prenom'] . ' ' . $record['nom']) ?></td><td><?= e($record['code_apogee']) ?></td><td><?= e($record['filiere']) ?></td><td><span class="pill"><?= e(['present' => 'Présent', 'absent' => 'Absent', 'late' => 'En retard', 'unrecorded' => 'Non enregistré'][$record['status']]) ?></span></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
