<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();

$q = trim((string)($_GET['q'] ?? ''));
$filter = trim((string)($_GET['niveau'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$sort = (string)($_GET['sort'] ?? 'id');
$direction = strtoupper((string)($_GET['direction'] ?? 'DESC'));
$sortable = ['id', 'nom', 'prenom', 'code_apogee', 'filiere', 'niveau'];
if (!in_array($sort, $sortable, true)) $sort = 'id';
if (!in_array($direction, ['ASC', 'DESC'], true)) $direction = 'DESC';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(code_apogee LIKE :q1 OR nom LIKE :q2 OR prenom LIKE :q3 OR email LIKE :q4 OR filiere LIKE :q5)';
    foreach (['q1','q2','q3','q4','q5'] as $key) $params[$key] = '%' . $q . '%';
}
if (in_array($filter, ['1ère année','2ème année','3ème année','Bachelor'], true)) {
    $where[] = 'niveau = :niveau';
    $params['niveau'] = $filter;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM etudiants' . $whereSql);
$countStmt->execute($params);
$filteredTotal = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($filteredTotal / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$params['limit'] = $perPage;
$params['offset'] = $offset;
$sql = 'SELECT * FROM etudiants' . $whereSql . " ORDER BY {$sort} {$direction} LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$students = $stmt->fetchAll();
$total = (int)$pdo->query('SELECT COUNT(*) FROM etudiants')->fetchColumn();
$firstYear = (int)$pdo->query("SELECT COUNT(*) FROM etudiants WHERE niveau = '1ère année'")->fetchColumn();
$secondYear = (int)$pdo->query("SELECT COUNT(*) FROM etudiants WHERE niveau = '2ème année'")->fetchColumn();
$thirdYear = (int)$pdo->query("SELECT COUNT(*) FROM etudiants WHERE niveau = '3ème année'")->fetchColumn();
$bachelor = (int)$pdo->query("SELECT COUNT(*) FROM etudiants WHERE niveau = 'Bachelor'")->fetchColumn();
$presentCount = (int)$pdo->query("SELECT COUNT(*) FROM attendance WHERE status = 'present'")->fetchColumn();
$absentCount = (int)$pdo->query("SELECT COUNT(*) FROM attendance WHERE status = 'absent'")->fetchColumn();
$averageGrade = $pdo->query('SELECT AVG(grade) FROM notes')->fetchColumn();
$alertsStmt = $pdo->query('
    SELECT e.id, e.prenom, e.nom,
           COALESCE(AVG(n.grade), 0) AS average_grade,
           SUM(a.status = "absent") AS absences
    FROM etudiants e
    LEFT JOIN notes n ON n.student_id = e.id
    LEFT JOIN attendance a ON a.student_id = e.id
    GROUP BY e.id
    HAVING (COUNT(n.id) > 0 AND AVG(n.grade) < 10) OR SUM(a.status = "absent") >= 3
    ORDER BY absences DESC, average_grade ASC
    LIMIT 10
');
$alerts = $alertsStmt->fetchAll();
$workflowPending = (int)$pdo->query("SELECT COUNT(*) FROM student_workflow WHERE status IN ('new','in_review')")->fetchColumn();
$pageTitle = 'Liste des étudiants';
require __DIR__ . '/includes/header.php';
?>
<section class="hero"><div><div class="eyebrow">TABLEAU DE BORD</div><h1>Gestion des étudiants</h1><p>Plateforme institutionnelle de suivi académique et administratif.</p></div><div class="hero-actions"><a class="btn btn-light" href="analytics.php">Analytics</a><a class="btn btn-primary" href="create.php">+ Nouvel étudiant</a></div></section>
<section class="stats"><div class="stat"><span>Total étudiants</span><strong><?= $total ?></strong><small>Enregistrés dans la base</small></div><div class="stat"><span>1ère année</span><strong><?= $firstYear ?></strong><small>Étudiants inscrits</small></div><div class="stat"><span>2ème année</span><strong><?= $secondYear ?></strong><small>Étudiants inscrits</small></div><div class="stat"><span>3ème année</span><strong><?= $thirdYear ?></strong><small>Étudiants inscrits</small></div><div class="stat"><span>Bachelor</span><strong><?= $bachelor ?></strong><small>Étudiants inscrits</small></div><div class="stat"><span>Présences</span><strong><?= $presentCount ?></strong><small><?= $absentCount ?> absence(s)</small></div><div class="stat"><span>Dossiers en cours</span><strong><?= $workflowPending ?></strong><small><a href="workflow.php">Voir le workflow</a></small></div><div class="stat"><span>Moyenne générale</span><strong><?= $averageGrade !== null ? number_format((float)$averageGrade, 2, ',', ' ') : '—' ?></strong><small>Sur 20</small></div></section>
<?php if ($alerts): ?><section class="panel alerts-panel"><div class="panel-title"><div><h2>Alertes à traiter</h2><p><?= count($alerts) ?> étudiant(s) nécessitent un suivi</p></div></div><div class="table-wrap"><table><thead><tr><th>Étudiant</th><th>Moyenne</th><th>Absences</th><th>Action</th></tr></thead><tbody><?php foreach ($alerts as $alert): ?><tr><td><?= e($alert['prenom'] . ' ' . $alert['nom']) ?></td><td><?= number_format((float)$alert['average_grade'], 2, ',', ' ') ?>/20</td><td><?= (int)$alert['absences'] ?></td><td><a class="btn btn-outline btn-small" href="profile.php?id=<?= (int)$alert['id'] ?>">Voir le profil</a></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
<section class="panel"><div class="panel-title"><div><h2>Liste des étudiants</h2><p><?= count($students) ?> résultat(s) affiché(s)</p></div></div>
<form method="get" class="filters"><input aria-label="Rechercher un étudiant" type="search" name="q" placeholder="Rechercher par nom, code, filière, email..." value="<?= e($q) ?>"><select name="niveau" aria-label="Filtrer par niveau"><option value="">Tous les niveaux</option><?php foreach (['1ère année','2ème année','3ème année','Bachelor'] as $level): ?><option value="<?= e($level) ?>" <?= $filter === $level ? 'selected' : '' ?>><?= e($level) ?></option><?php endforeach; ?></select><button class="btn btn-primary" type="submit">Rechercher</button><a class="btn btn-light" href="export.php?<?= e(http_build_query(['q' => $q, 'niveau' => $filter])) ?>">Exporter CSV</a><a class="btn btn-light" href="index.php">Effacer</a></form>
<div class="table-wrap"><table><thead><tr><th><a href="?<?= e(http_build_query(['q' => $q, 'niveau' => $filter, 'sort' => 'nom', 'direction' => $sort === 'nom' && $direction === 'ASC' ? 'DESC' : 'ASC'])) ?>">Étudiant</a></th><th>Code Apogée</th><th>Filière</th><th>Niveau</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($students as $s): ?>
<tr><td><div class="person"><span class="avatar"><?= e(mb_strtoupper(mb_substr($s['prenom'], 0, 1) . mb_substr($s['nom'], 0, 1))) ?></span><span><b><?= e($s['prenom'] . ' ' . $s['nom']) ?></b><small><?= e($s['email']) ?></small></span></div></td><td><span class="mono"><?= e($s['code_apogee']) ?></span></td><td><?= e($s['filiere']) ?></td><td><span class="pill"><?= e($s['niveau']) ?></span></td><td><div class="actions"><a class="btn btn-outline btn-small" href="profile.php?id=<?= (int)$s['id'] ?>">Profil</a><a class="btn btn-outline btn-small" href="grades.php?student_id=<?= (int)$s['id'] ?>">Notes</a><a class="btn btn-outline btn-small" href="attendance.php?student_id=<?= (int)$s['id'] ?>">Présence</a><a class="btn btn-outline btn-small" href="edit.php?id=<?= (int)$s['id'] ?>">Modifier</a><form action="delete.php" method="post" data-loading data-confirm="Supprimer cet étudiant ?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button type="submit" class="btn btn-danger btn-small">Supprimer</button></form></div></td></tr>
<?php endforeach; ?>
<?php if (!$students): ?><tr><td colspan="5" class="empty">Aucun étudiant trouvé. <a href="create.php">Ajouter un étudiant</a>.</td></tr><?php endif; ?>
</tbody></table></div></section>
<?php if ($totalPages > 1): ?><nav class="pagination" aria-label="Pagination"><?php for ($p = 1; $p <= $totalPages; $p++): ?><a class="<?= $p === $page ? 'current' : '' ?>" href="?<?= e(http_build_query(['q' => $q, 'niveau' => $filter, 'sort' => $sort, 'direction' => $direction, 'page' => $p])) ?>"><?= $p ?></a><?php endfor; ?></nav><?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
