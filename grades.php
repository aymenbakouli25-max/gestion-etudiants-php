<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();

$studentId = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$studentId) { http_response_code(400); exit('Étudiant invalide.'); }
$studentStmt = $pdo->prepare('SELECT id, nom, prenom, code_apogee FROM etudiants WHERE id = :id');
$studentStmt->execute(['id' => $studentId]);
$student = $studentStmt->fetch();
if (!$student) { http_response_code(404); exit('Étudiant introuvable.'); }
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (($_POST['action'] ?? '') === 'delete') {
        $deleteStmt = $pdo->prepare('DELETE FROM notes WHERE id = :id AND student_id = :student_id');
        $deleteStmt->execute(['id' => (int)$_POST['id'], 'student_id' => $studentId]);
        log_action($pdo, 'grade_deleted', 'student_id=' . $studentId);
        flash('Note supprimée.');
        redirect('grades.php?student_id=' . $studentId);
    }
    if (($_POST['action'] ?? '') === 'update') {
        $gradeId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $grade = filter_var($_POST['grade'] ?? null, FILTER_VALIDATE_FLOAT);
        $coefficient = filter_var($_POST['coefficient'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($gradeId && $grade !== false && $grade >= 0 && $grade <= 20 && $coefficient !== false && $coefficient > 0 && $coefficient <= 20) {
            $update = $pdo->prepare('UPDATE notes SET grade = :grade, coefficient = :coefficient WHERE id = :id AND student_id = :student_id');
            $update->execute(['grade' => $grade, 'coefficient' => $coefficient, 'id' => $gradeId, 'student_id' => $studentId]);
            log_action($pdo, 'grade_updated', 'student_id=' . $studentId);
            flash('Note modifiée avec succès.');
        }
        redirect('grades.php?student_id=' . $studentId);
    }
    $subject = trim((string)($_POST['subject'] ?? ''));
    $grade = filter_var($_POST['grade'] ?? null, FILTER_VALIDATE_FLOAT);
    $coefficient = filter_var($_POST['coefficient'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($subject === '' || strlen($subject) > 100) $errors[] = 'La matière est obligatoire.';
    if ($grade === false || $grade < 0 || $grade > 20) $errors[] = 'La note doit être comprise entre 0 et 20.';
    if ($coefficient === false || $coefficient <= 0 || $coefficient > 20) $errors[] = 'Le coefficient est invalide.';
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO notes (student_id, subject, grade, coefficient) VALUES (:student_id, :subject, :grade, :coefficient)');
        $stmt->execute(['student_id' => $studentId, 'subject' => $subject, 'grade' => $grade, 'coefficient' => $coefficient]);
        log_action($pdo, 'grade_created', 'student_id=' . $studentId);
        flash('Note ajoutée avec succès.');
        redirect('grades.php?student_id=' . $studentId);
    }
}
$stmt = $pdo->prepare('SELECT id, subject, grade, coefficient, (grade * coefficient) AS weighted FROM notes WHERE student_id = :student_id ORDER BY subject');
$stmt->execute(['student_id' => $studentId]);
$grades = $stmt->fetchAll();
$averageStmt = $pdo->prepare('SELECT SUM(grade * coefficient) / NULLIF(SUM(coefficient), 0) FROM notes WHERE student_id = :student_id');
$averageStmt->execute(['student_id' => $studentId]);
$average = $averageStmt->fetchColumn();
$pageTitle = 'Notes de ' . $student['prenom'] . ' ' . $student['nom'];
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Retour à la liste</a><h1>Notes de <?= e($student['prenom'] . ' ' . $student['nom']) ?></h1><p><?= e($student['code_apogee']) ?> · Moyenne : <strong><?= $average !== false && $average !== null ? number_format((float)$average, 2, ',', ' ') : '—' ?>/20</strong> <a class="btn btn-light btn-small" href="print.php?student_id=<?= $studentId ?>">Imprimer</a></p></div>
<form method="post" class="form-card">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php if ($errors): ?><div class="alert error" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
  <div class="form-grid"><label>Matière<input type="text" name="subject" maxlength="100" required></label><label>Note /20<input type="number" name="grade" min="0" max="20" step="0.01" required></label><label>Coefficient<input type="number" name="coefficient" min="0.01" max="20" step="0.01" value="1" required></label></div>
  <div class="form-actions"><button class="btn btn-primary" type="submit">Ajouter la note</button></div>
</form>
<section class="panel"><div class="panel-title"><div><h2>Résultats</h2><p><?= count($grades) ?> note(s)</p></div></div><div class="table-wrap"><table><thead><tr><th>Matière</th><th>Note</th><th>Coefficient</th><th>Points pondérés</th><th>Statut</th><th>Action</th></tr></thead><tbody><?php foreach ($grades as $grade): ?><tr><td><?= e($grade['subject']) ?></td><td><form method="post" class="inline-form"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$grade['id'] ?>"><input class="compact-input" type="number" name="grade" min="0" max="20" step="0.01" value="<?= e((string)$grade['grade']) ?>"></td><td><input class="compact-input" type="number" name="coefficient" min="0.01" max="20" step="0.01" value="<?= e((string)$grade['coefficient']) ?>"></td><td><?= e((string)$grade['weighted']) ?></td><td><span class="pill"><?= (float)$grade['grade'] >= 10 ? 'Réussi' : 'À améliorer' ?></span></td><td><button class="btn btn-outline btn-small" type="submit">Modifier</button></form><form method="post" class="inline-form" onsubmit="return confirm('Supprimer cette note ?');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$grade['id'] ?>"><button class="btn btn-danger btn-small" type="submit">Supprimer</button></form></td></tr><?php endforeach; ?><?php if (!$grades): ?><tr><td colspan="6" class="empty">Aucune note enregistrée.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
