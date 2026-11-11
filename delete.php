<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Méthode non autorisée.'); }
check_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { flash('Identifiant invalide.', 'error'); redirect('index.php'); }
$stmt = $pdo->prepare('DELETE FROM etudiants WHERE id = :id');
$stmt->execute(['id' => $id]);
if ($stmt->rowCount()) {
    log_action($pdo, 'student_deleted', 'id=' . $id);
}
flash($stmt->rowCount() ? 'Étudiant supprimé avec succès.' : 'Étudiant introuvable.', $stmt->rowCount() ? 'success' : 'error');
redirect('index.php');
