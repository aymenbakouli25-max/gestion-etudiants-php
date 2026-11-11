<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
$studentId = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$studentId) { http_response_code(400); exit('Étudiant invalide.'); }
$stmt = $pdo->prepare('SELECT id, nom, prenom, code_apogee FROM etudiants WHERE id = :id');
$stmt->execute(['id' => $studentId]);
$student = $stmt->fetch();
if (!$student) { http_response_code(404); exit('Étudiant introuvable.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $date = (string)($_POST['attendance_date'] ?? '');
    $status = (string)($_POST['status'] ?? '');
    if (DateTimeImmutable::createFromFormat('!Y-m-d', $date) && in_array($status, ['present', 'absent', 'late'], true)) {
        $save = $pdo->prepare('INSERT INTO attendance (student_id, attendance_date, status) VALUES (:student_id, :date, :status) ON DUPLICATE KEY UPDATE status = VALUES(status)');
        $save->execute(['student_id' => $studentId, 'date' => $date, 'status' => $status]);
        flash('Présence enregistrée.');
    }
    redirect('attendance.php?student_id=' . $studentId);
}
$recordsStmt = $pdo->prepare('SELECT attendance_date, status FROM attendance WHERE student_id = :student_id ORDER BY attendance_date DESC');
$recordsStmt->execute(['student_id' => $studentId]);
$records = $recordsStmt->fetchAll();
$pageTitle = 'Présence de ' . $student['prenom'] . ' ' . $student['nom'];
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Retour à la liste</a><h1>Présence de <?= e($student['prenom'] . ' ' . $student['nom']) ?></h1><p><?= e($student['code_apogee']) ?></p></div>
<form method="post" class="form-card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><div class="form-grid"><label>Date<input type="date" name="attendance_date" value="<?= date('Y-m-d') ?>" required></label><label>Statut<select name="status"><option value="present">Présent</option><option value="absent">Absent</option><option value="late">En retard</option></select></label></div><div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer</button></div></form>
<section class="panel"><div class="panel-title"><div><h2>Historique</h2><p><?= count($records) ?> enregistrement(s)</p></div></div><div class="table-wrap"><table><thead><tr><th>Date</th><th>Statut</th></tr></thead><tbody><?php foreach ($records as $record): ?><tr><td><?= e($record['attendance_date']) ?></td><td><?= e(['present' => 'Présent', 'absent' => 'Absent', 'late' => 'En retard'][$record['status']] ?? $record['status']) ?></td></tr><?php endforeach; ?><?php if (!$records): ?><tr><td colspan="2" class="empty">Aucun enregistrement.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
