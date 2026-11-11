<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
$minAverage = (float)($_GET['min_average'] ?? 0);
$maxAbsences = max(0, (int)($_GET['max_absences'] ?? 999999));
$stmt = $pdo->prepare('SELECT e.id, e.code_apogee, e.nom, e.prenom, e.filiere, COALESCE(AVG(n.grade), 0) AS average_grade, SUM(a.status = "absent") AS absences FROM etudiants e LEFT JOIN notes n ON n.student_id = e.id LEFT JOIN attendance a ON a.student_id = e.id GROUP BY e.id ORDER BY average_grade DESC');
$stmt->execute();
$rows = array_values(array_filter($stmt->fetchAll(), static fn (array $row): bool => (float)$row['average_grade'] >= $minAverage && (int)$row['absences'] <= $maxAbsences));
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="rapport_etudiants.csv"');
    $output = fopen('php://output', 'wb');
    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['Code', 'Nom', 'Prénom', 'Filière', 'Moyenne', 'Absences'], ';');
    foreach ($rows as $row) fputcsv($output, [$row['code_apogee'], $row['nom'], $row['prenom'], $row['filiere'], $row['average_grade'], $row['absences']], ';');
    fclose($output);
    exit;
}
$pageTitle = 'Rapports';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Retour à la liste</a><h1>Rapports étudiants</h1><p>Filtrez les étudiants par moyenne et nombre d’absences.</p></div>
<form method="get" class="filters"><label>Moyenne minimum<input type="number" name="min_average" min="0" max="20" step="0.01" value="<?= e((string)$minAverage) ?>"></label><label>Absences maximum<input type="number" name="max_absences" min="0" value="<?= e((string)$maxAbsences) ?>"></label><button class="btn btn-primary" type="submit">Filtrer</button><a class="btn btn-light" href="reports.php">Réinitialiser</a><button class="btn btn-light" type="submit" name="export" value="1">Exporter CSV</button></form>
<section class="panel"><div class="panel-title"><div><h2>Résultats</h2><p><?= count($rows) ?> étudiant(s)</p></div></div><div class="table-wrap"><table><thead><tr><th>Étudiant</th><th>Filière</th><th>Moyenne</th><th>Absences</th><th>Action</th></tr></thead><tbody><?php foreach ($rows as $row): ?><tr><td><?= e($row['prenom'] . ' ' . $row['nom']) ?><small><?= e($row['code_apogee']) ?></small></td><td><?= e($row['filiere']) ?></td><td><?= number_format((float)$row['average_grade'], 2, ',', ' ') ?>/20</td><td><?= (int)$row['absences'] ?></td><td><a class="btn btn-outline btn-small" href="profile.php?id=<?= (int)$row['id'] ?>">Profil</a></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
