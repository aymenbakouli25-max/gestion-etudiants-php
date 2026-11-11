<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
$date = (string)($_GET['date'] ?? date('Y-m-d'));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $date = (string)($_POST['attendance_date'] ?? date('Y-m-d'));
    $statuses = $_POST['status'] ?? [];
    $save = $pdo->prepare('INSERT INTO attendance (student_id, attendance_date, status) VALUES (:student_id, :date, :status) ON DUPLICATE KEY UPDATE status = VALUES(status)');
    $count = 0;
    foreach ($statuses as $studentId => $status) {
        if (in_array($status, ['present', 'absent', 'late'], true)) {
            $save->execute(['student_id' => (int)$studentId, 'date' => $date, 'status' => $status]);
            $count++;
        }
    }
    log_action($pdo, 'bulk_attendance_saved', 'date=' . $date . ';count=' . $count);
    flash($count . ' présence(s) enregistrée(s).');
    redirect('bulk_attendance.php?date=' . urlencode($date));
}
$students = $pdo->query('SELECT id, code_apogee, nom, prenom, filiere FROM etudiants ORDER BY nom, prenom')->fetchAll();
$existing = $pdo->prepare('SELECT student_id, status FROM attendance WHERE attendance_date = :date');
$existing->execute(['date' => $date]);
$statuses = [];
foreach ($existing as $row) $statuses[(int)$row['student_id']] = $row['status'];
$pageTitle = 'Présence collective';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Retour à la liste</a><h1>Présence collective</h1><p>Enregistrez le statut de tous les étudiants pour une date.</p></div>
<form method="post" class="form-card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Date<input type="date" name="attendance_date" value="<?= e($date) ?>" required></label><div class="table-wrap"><table><thead><tr><th>Étudiant</th><th>Filière</th><th>Statut</th></tr></thead><tbody><?php foreach ($students as $student): ?><tr><td><?= e($student['prenom'] . ' ' . $student['nom'] . ' (' . $student['code_apogee'] . ')') ?></td><td><?= e($student['filiere']) ?></td><td><select name="status[<?= (int)$student['id'] ?>]"><option value="present" <?= ($statuses[$student['id']] ?? '') === 'present' ? 'selected' : '' ?>>Présent</option><option value="absent" <?= ($statuses[$student['id']] ?? '') === 'absent' ? 'selected' : '' ?>>Absent</option><option value="late" <?= ($statuses[$student['id']] ?? '') === 'late' ? 'selected' : '' ?>>En retard</option></select></td></tr><?php endforeach; ?></tbody></table></div><div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer tout</button></div></form>
<?php require __DIR__ . '/includes/footer.php'; ?>
