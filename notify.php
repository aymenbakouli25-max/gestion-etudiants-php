<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT);
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    $stmt = $pdo->prepare('SELECT email FROM etudiants WHERE id = :id');
    $stmt->execute(['id' => $studentId]);
    $student = $stmt->fetch();
    if (!$student || $subject === '' || $message === '') $errors[] = 'Tous les champs sont obligatoires.';
    elseif (!mail($student['email'], $subject, $message, 'From: ' . (getenv('MAIL_FROM') ?: 'no-reply@example.com'))) $errors[] = 'Échec de l’envoi. Configurez le SMTP de PHP/XAMPP.';
    else { log_action($pdo, 'email_sent', 'student_id=' . $studentId); flash('Email envoyé.'); redirect('notify.php'); }
}
$students = $pdo->query('SELECT id, prenom, nom, email FROM etudiants ORDER BY nom, prenom')->fetchAll();
$pageTitle = 'Notifications email';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Dashboard</a><h1>Notification email</h1><p>Envoyez un message à un étudiant.</p></div>
<form method="post" class="form-card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><?php if ($errors): ?><div class="alert error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><label>Étudiant<select name="student_id" required><?php foreach ($students as $student): ?><option value="<?= (int)$student['id'] ?>"><?= e($student['prenom'] . ' ' . $student['nom'] . ' — ' . $student['email']) ?></option><?php endforeach; ?></select></label><label>Objet<input name="subject" maxlength="150" required></label><label>Message<textarea name="message" rows="7" required></textarea></label><div class="form-actions"><button class="btn btn-primary" type="submit">Envoyer</button></div></form>
<?php require __DIR__ . '/includes/footer.php'; ?>
