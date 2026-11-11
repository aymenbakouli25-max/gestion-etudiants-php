<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Choisissez un fichier CSV valide.';
    } elseif (strtolower(pathinfo($_FILES['csv']['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $errors[] = 'Le fichier doit avoir l’extension CSV.';
    } else {
        $handle = fopen($_FILES['csv']['tmp_name'], 'rb');
        $insert = $pdo->prepare('INSERT INTO notes (student_id, subject, grade, coefficient) SELECT id, :subject, :grade, :coefficient FROM etudiants WHERE code_apogee = :code');
        $count = 0;
        if ($handle) {
            fgetcsv($handle, 0, ';');
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if (count($row) < 4) continue;
                [$code, $subject, $grade, $coefficient] = array_map('trim', array_slice($row, 0, 4));
                if ($subject !== '' && is_numeric($grade) && is_numeric($coefficient) && (float)$grade >= 0 && (float)$grade <= 20 && (float)$coefficient > 0) {
                    $insert->execute(['code' => $code, 'subject' => $subject, 'grade' => $grade, 'coefficient' => $coefficient]);
                    if ($insert->rowCount()) $count++;
                }
            }
            fclose($handle);
        }
        log_action($pdo, 'grades_imported', 'count=' . $count);
        flash($count . ' note(s) importée(s).');
        redirect('bulk_grades.php');
    }
}
$pageTitle = 'Import des notes';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Retour à la liste</a><h1>Importer les notes</h1><p>CSV séparé par des points-virgules : code_apogee;matiere;note;coefficient.</p></div>
<form method="post" enctype="multipart/form-data" class="form-card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><?php if ($errors): ?><div class="alert error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><label>Fichier CSV<input type="file" name="csv" accept=".csv,text/csv" required></label><div class="form-actions"><button class="btn btn-primary" type="submit">Importer</button></div></form>
<?php require __DIR__ . '/includes/footer.php'; ?>
