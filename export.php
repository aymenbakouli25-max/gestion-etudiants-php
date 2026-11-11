<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();

$q = trim((string)($_GET['q'] ?? ''));
$filter = trim((string)($_GET['niveau'] ?? ''));
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(code_apogee LIKE :q1 OR nom LIKE :q2 OR prenom LIKE :q3 OR email LIKE :q4 OR filiere LIKE :q5)';
    foreach (['q1', 'q2', 'q3', 'q4', 'q5'] as $key) $params[$key] = '%' . $q . '%';
}
if (in_array($filter, ['1ère année', '2ème année', '3ème année'], true)) {
    $where[] = 'niveau = :niveau';
    $params['niveau'] = $filter;
}
$stmt = $pdo->prepare('SELECT code_apogee, nom, prenom, email, filiere, niveau, date_naissance FROM etudiants' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY nom, prenom');
$stmt->execute($params);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="etudiants.csv"');
$output = fopen('php://output', 'wb');
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['Code Apogée', 'Nom', 'Prénom', 'Email', 'Filière', 'Niveau', 'Date de naissance'], ';');
while ($student = $stmt->fetch()) {
    fputcsv($output, $student, ';');
}
fclose($output);
