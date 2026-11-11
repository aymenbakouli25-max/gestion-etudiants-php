<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
$studentId = filter_input(INPUT_GET, 'student_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$stmt = $pdo->prepare('SELECT id, nom, prenom, code_apogee, filiere, niveau FROM etudiants WHERE id = :id');
$stmt->execute(['id' => $studentId]);
$student = $stmt->fetch();
if (!$student) { http_response_code(404); exit('Étudiant introuvable.'); }
$notes = $pdo->prepare('SELECT subject, grade, coefficient FROM notes WHERE student_id = :id ORDER BY subject');
$notes->execute(['id' => $studentId]);
?><!doctype html><html lang="fr"><head><meta charset="UTF-8"><title>Résultats - <?= e($student['prenom'] . ' ' . $student['nom']) ?></title><style>body{font-family:Arial;max-width:800px;margin:40px auto;color:#222}table{width:100%;border-collapse:collapse;margin-top:30px}th,td{border:1px solid #ccc;padding:10px;text-align:left}button{padding:10px 18px}@media print{button{display:none}}</style></head><body><button onclick="window.print()">Imprimer / PDF</button><h1>Relevé de notes</h1><p><strong><?= e($student['prenom'] . ' ' . $student['nom']) ?></strong><br><?= e($student['code_apogee']) ?> · <?= e($student['filiere']) ?> · <?= e($student['niveau']) ?></p><table><thead><tr><th>Matière</th><th>Note /20</th><th>Coefficient</th></tr></thead><tbody><?php while ($note = $notes->fetch()): ?><tr><td><?= e($note['subject']) ?></td><td><?= e((string)$note['grade']) ?></td><td><?= e((string)$note['coefficient']) ?></td></tr><?php endwhile; ?></tbody></table></body></html>
