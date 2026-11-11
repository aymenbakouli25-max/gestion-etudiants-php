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
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $file = $_FILES['document'] ?? null;
    $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) $errors[] = 'Fichier invalide.';
    elseif ($file['size'] > 5 * 1024 * 1024) $errors[] = 'Le fichier ne doit pas dépasser 5 Mo.';
    elseif (!in_array((new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']), $allowed, true)) $errors[] = 'Format accepté : PDF, JPG ou PNG.';
    if (!$errors) {
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0750, true)) $errors[] = 'Impossible de préparer le stockage.';
        if (!$errors) {
            $stored = bin2hex(random_bytes(20));
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if (!move_uploaded_file($file['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $stored)) $errors[] = 'Échec du téléchargement.';
            else {
                $stmt = $pdo->prepare('INSERT INTO documents (student_id, original_name, stored_name, mime_type, file_size, uploaded_by) VALUES (:student_id, :original_name, :stored_name, :mime_type, :file_size, :uploaded_by)');
                $stmt->execute(['student_id' => $studentId, 'original_name' => basename((string)$file['name']), 'stored_name' => $stored, 'mime_type' => $mime, 'file_size' => (int)$file['size'], 'uploaded_by' => (int)$_SESSION['user_id']]);
                log_action($pdo, 'document_uploaded', 'student_id=' . $studentId);
                flash('Document ajouté.');
                redirect('documents.php?student_id=' . $studentId);
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
                check_csrf();
                $delete = $pdo->prepare('SELECT stored_name FROM documents WHERE id = :id AND student_id = :student_id');
                $delete->execute(['id' => (int)$_POST['delete'], 'student_id' => $studentId]);
                $stored = $delete->fetchColumn();
                if ($stored) {
                    $pdo->prepare('DELETE FROM documents WHERE id = :id AND student_id = :student_id')->execute(['id' => (int)$_POST['delete'], 'student_id' => $studentId]);
                    @unlink($uploadDir . DIRECTORY_SEPARATOR . $stored);
                    log_action($pdo, 'document_deleted', 'student_id=' . $studentId);
                }
                redirect('documents.php?student_id=' . $studentId);
            }
        }
    }
}
$docs = $pdo->prepare('SELECT id, original_name, file_size, created_at FROM documents WHERE student_id = :id ORDER BY id DESC');
$docs->execute(['id' => $studentId]);
$pageTitle = 'Documents étudiant';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="profile.php?id=<?= $studentId ?>" class="back">← Profil</a><h1>Documents de <?= e($student['prenom'] . ' ' . $student['nom']) ?></h1></div>
<form method="post" enctype="multipart/form-data" class="form-card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><?php if ($errors): ?><div class="alert error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><label>Document<input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required></label><div class="form-actions"><button class="btn btn-primary" type="submit">Télécharger</button></div></form>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Nom</th><th>Taille</th><th>Date</th><th>Actions</th></tr></thead><tbody><?php foreach ($docs as $doc): ?><tr><td><?= e($doc['original_name']) ?></td><td><?= number_format((int)$doc['file_size'] / 1024, 1, ',', ' ') ?> Ko</td><td><?= e($doc['created_at']) ?></td><td><a class="btn btn-outline btn-small" href="document.php?id=<?= (int)$doc['id'] ?>">Ouvrir</a><form method="post" class="inline-form" onsubmit="return confirm('Supprimer ce document ?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="delete" value="<?= (int)$doc['id'] ?>"><button class="btn btn-danger btn-small" type="submit">Supprimer</button></form></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
