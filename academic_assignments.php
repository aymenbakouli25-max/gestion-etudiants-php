<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_admin();
$studentId = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$studentId) { http_response_code(400); exit('Étudiant invalide.'); }
$student = $pdo->prepare('SELECT id, prenom, nom, filiere FROM etudiants WHERE id = :id');
$student->execute(['id' => $studentId]);
$student = $student->fetch();
if (!$student) { http_response_code(404); exit('Étudiant introuvable.'); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $filiereId = filter_var($_POST['filiere_id'] ?? null, FILTER_VALIDATE_INT);
    $classId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
    if ($filiereId && $classId) {
        $stmt = $pdo->prepare('UPDATE etudiants SET filiere = (SELECT name FROM filieres WHERE id = :filiere_id) WHERE id = :student_id');
        $stmt->execute(['filiere_id' => $filiereId, 'student_id' => $studentId]);
        $pdo->prepare('INSERT INTO student_workflow (student_id, status, note, updated_by) VALUES (:student_id, "in_review", :note, :user_id) ON DUPLICATE KEY UPDATE note = VALUES(note), updated_by = VALUES(updated_by)')->execute(['student_id' => $studentId, 'note' => 'Classe ID: ' . $classId, 'user_id' => (int)$_SESSION['user_id']]);
        flash('Affectation académique enregistrée.');
    }
    redirect('academic_assignments.php?student_id=' . $studentId);
}
$filieres = $pdo->query('SELECT id, name FROM filieres ORDER BY name')->fetchAll();
$classes = $pdo->query('SELECT id, name, academic_year, niveau FROM classes ORDER BY academic_year DESC, name')->fetchAll();
$pageTitle = 'Affectation académique';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="profile.php?id=<?= $studentId ?>" class="back">← Profil</a><h1>Affectation académique</h1><p><?= e($student['prenom'] . ' ' . $student['nom']) ?> · <?= e($student['filiere']) ?></p></div>
<form method="post" class="form-card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Filière<select name="filiere_id" required><?php foreach ($filieres as $f): ?><option value="<?= (int)$f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?></select></label><label>Classe<select name="class_id" required><?php foreach ($classes as $class): ?><option value="<?= (int)$class['id'] ?>"><?= e($class['name'] . ' · ' . $class['academic_year'] . ' · ' . $class['niveau']) ?></option><?php endforeach; ?></select></label><div class="form-actions"><button class="btn btn-primary" type="submit">Enregistrer</button></div></form>
<?php require __DIR__ . '/includes/footer.php'; ?>
