<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $studentId = (int)($_POST['student_id'] ?? 0);
    $status = (string)($_POST['status'] ?? '');
    if ($studentId > 0 && in_array($status, ['new','in_review','approved','rejected','completed'], true)) {
        $stmt = $pdo->prepare('INSERT INTO student_workflow (student_id, status, note, updated_by) VALUES (:student_id, :status, :note, :updated_by) ON DUPLICATE KEY UPDATE status = VALUES(status), note = VALUES(note), updated_by = VALUES(updated_by)');
        $stmt->execute(['student_id' => $studentId, 'status' => $status, 'note' => trim((string)($_POST['note'] ?? '')) ?: null, 'updated_by' => (int)$_SESSION['user_id']]);
        log_action($pdo, 'workflow_updated', 'student_id=' . $studentId . ';status=' . $status);
    }
    redirect('workflow.php');
}
$students = $pdo->query('SELECT e.id, e.code_apogee, e.nom, e.prenom, COALESCE(w.status, "new") AS workflow_status, w.note FROM etudiants e LEFT JOIN student_workflow w ON w.student_id = e.id ORDER BY e.nom, e.prenom')->fetchAll();
$pageTitle = 'Workflow étudiants';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Dashboard</a><h1>Workflow étudiants</h1><p>Suivez le traitement administratif de chaque dossier.</p></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Étudiant</th><th>État</th><th>Note interne</th><th>Action</th></tr></thead><tbody><?php foreach ($students as $student): ?><tr><td><?= e($student['prenom'] . ' ' . $student['nom']) ?><small><?= e($student['code_apogee']) ?></small></td><td><form method="post" class="inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="student_id" value="<?= (int)$student['id'] ?>"><select name="status"><?php foreach (['new'=>'Nouveau','in_review'=>'En examen','approved'=>'Approuvé','rejected'=>'Rejeté','completed'=>'Terminé'] as $key => $label): ?><option value="<?= $key ?>" <?= $student['workflow_status'] === $key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></td><td><input name="note" value="<?= e($student['note'] ?? '') ?>" maxlength="255"></td><td><button class="btn btn-outline btn-small" type="submit">Enregistrer</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
