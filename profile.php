<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(400); exit('Identifiant invalide.'); }
$stmt = $pdo->prepare('SELECT * FROM etudiants WHERE id = :id');
$stmt->execute(['id' => $id]);
$student = $stmt->fetch();
if (!$student) { http_response_code(404); exit('Étudiant introuvable.'); }
$avg = $pdo->prepare('SELECT AVG(grade), COUNT(*) FROM notes WHERE student_id = :id');
$avg->execute(['id' => $id]);
[$average, $noteCount] = $avg->fetch(PDO::FETCH_NUM);
$attendance = $pdo->prepare('SELECT status, COUNT(*) AS total FROM attendance WHERE student_id = :id GROUP BY status');
$attendance->execute(['id' => $id]);
$attendanceStats = [];
foreach ($attendance as $row) $attendanceStats[$row['status']] = (int)$row['total'];
$pageTitle = 'Profil étudiant';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Retour à la liste</a><h1><?= e($student['prenom'] . ' ' . $student['nom']) ?></h1><p>Profil détaillé de l'étudiant.</p></div>
<section class="panel profile-grid"><div><h2>Informations personnelles</h2><p><strong>Code Apogée :</strong> <?= e($student['code_apogee']) ?></p><p><strong>Email :</strong> <?= e($student['email']) ?></p><p><strong>Filière :</strong> <?= e($student['filiere']) ?></p><p><strong>Niveau :</strong> <?= e($student['niveau']) ?></p><p><strong>Date de naissance :</strong> <?= e($student['date_naissance'] ?: 'Non renseignée') ?></p></div><div><h2>Résumé scolaire</h2><p><strong>Moyenne :</strong> <?= $average !== null ? number_format((float)$average, 2, ',', ' ') . '/20' : '—' ?></p><p><strong>Notes :</strong> <?= $noteCount ?></p><p><strong>Présences :</strong> <?= $attendanceStats['present'] ?? 0 ?></p><p><strong>Absences :</strong> <?= $attendanceStats['absent'] ?? 0 ?></p><p><strong>Retards :</strong> <?= $attendanceStats['late'] ?? 0 ?></p></div></section>
<div class="form-actions"><a class="btn btn-primary" href="edit.php?id=<?= $id ?>">Modifier le profil</a><a class="btn btn-light" href="academic_assignments.php?student_id=<?= $id ?>">Affectation académique</a><a class="btn btn-light" href="documents.php?student_id=<?= $id ?>">Documents</a><a class="btn btn-light" href="print.php?student_id=<?= $id ?>">Imprimer les résultats</a></div>
<?php require __DIR__ . '/includes/footer.php'; ?>
