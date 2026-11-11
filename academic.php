<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_admin();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $type = (string)($_POST['type'] ?? '');
    try {
        if ($type === 'filiere') {
            $name = trim((string)($_POST['name'] ?? ''));
            if ($name === '' || mb_strlen($name) > 100) throw new InvalidArgumentException('Nom de filière invalide.');
            $stmt = $pdo->prepare('INSERT INTO filieres (name, description) VALUES (:name, :description)');
            $stmt->execute(['name' => $name, 'description' => trim((string)($_POST['description'] ?? '')) ?: null]);
        } elseif ($type === 'module') {
            $stmt = $pdo->prepare('INSERT INTO modules (filiere_id, name, code, coefficient) VALUES (:filiere_id, :name, :code, :coefficient)');
            $stmt->execute(['filiere_id' => (int)($_POST['filiere_id'] ?? 0) ?: null, 'name' => trim((string)$_POST['name']), 'code' => strtoupper(trim((string)$_POST['code'])), 'coefficient' => (float)($_POST['coefficient'] ?? 1)]);
        } elseif ($type === 'class') {
            $stmt = $pdo->prepare('INSERT INTO classes (name, academic_year, niveau) VALUES (:name, :academic_year, :niveau)');
            $stmt->execute(['name' => trim((string)$_POST['name']), 'academic_year' => trim((string)$_POST['academic_year']), 'niveau' => trim((string)$_POST['niveau'])]);
        }
        flash('Élément académique ajouté.');
        redirect('academic.php');
    } catch (InvalidArgumentException|PDOException $e) {
        $errors[] = $e instanceof PDOException && $e->getCode() === '23000' ? 'Cette valeur existe déjà.' : $e->getMessage();
    }
}
$filieres = $pdo->query('SELECT id, name, description FROM filieres ORDER BY name')->fetchAll();
$modules = $pdo->query('SELECT m.name, m.code, m.coefficient, f.name AS filiere FROM modules m LEFT JOIN filieres f ON f.id = m.filiere_id ORDER BY m.name')->fetchAll();
$classes = $pdo->query('SELECT name, academic_year, niveau FROM classes ORDER BY academic_year DESC, name')->fetchAll();
$totalCoefficient = array_sum(array_map(static fn (array $module): float => (float)$module['coefficient'], $modules));
$levelCounts = [];
foreach ($classes as $class) {
    $levelCounts[$class['niveau']] = ($levelCounts[$class['niveau']] ?? 0) + 1;
}
$pageTitle = 'Gestion académique';
require __DIR__ . '/includes/header.php';
?>
<div class="academic-hero">
    <div class="page-heading"><a href="index.php" class="back">← Dashboard</a><span class="eyebrow">ESPACE ADMINISTRATION</span><h1>Gestion académique</h1><p>Centralisez les filières, modules et classes de l’établissement.</p></div>
    <div class="academic-hero-mark" aria-hidden="true"><span>EST</span><small>OUJDA</small></div>
</div>
<?php if ($errors): ?><div class="alert error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="stats academic-stats">
    <div class="stat"><span>Filières</span><strong><?= count($filieres) ?></strong><small>Parcours disponibles <b class="trend-up">+ actif</b></small></div>
    <div class="stat"><span>Modules</span><strong><?= count($modules) ?></strong><small>Unités d’enseignement</small></div>
    <div class="stat"><span>Classes</span><strong><?= count($classes) ?></strong><small><?= count($levelCounts) ?> niveaux représentés</small></div>
    <div class="stat"><span>Coefficient global</span><strong><?= e(number_format($totalCoefficient, 2, ',', ' ')) ?></strong><small>Somme des coefficients</small></div>
</div>
<div class="management-grid">
<form method="post" class="form-card management-card"><div class="management-card-icon">01</div><h2>Nouvelle filière</h2><p class="form-hint">Créez un parcours de formation.</p><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="type" value="filiere"><label>Nom<input name="name" maxlength="100" placeholder="Ex. Génie informatique" required></label><label>Description<textarea name="description" rows="3" placeholder="Objectif ou spécialité..."></textarea></label><button class="btn btn-primary" type="submit">+ Ajouter la filière</button></form>
<form method="post" class="form-card management-card"><div class="management-card-icon">02</div><h2>Nouveau module</h2><p class="form-hint">Ajoutez une unité d’enseignement.</p><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="type" value="module"><label>Nom<input name="name" maxlength="100" placeholder="Ex. Programmation web" required></label><div class="form-grid"><label>Code<input name="code" maxlength="30" placeholder="WEB101" required></label><label>Coefficient<input type="number" name="coefficient" min=".01" max="20" step=".01" value="1" required></label></div><label>Filière<select name="filiere_id"><option value="">Toutes les filières</option><?php foreach ($filieres as $f): ?><option value="<?= (int)$f['id'] ?>"><?= e($f['name']) ?></option><?php endforeach; ?></select></label><button class="btn btn-primary" type="submit">+ Ajouter le module</button></form>
<form method="post" class="form-card management-card"><div class="management-card-icon">03</div><h2>Nouvelle classe</h2><p class="form-hint">Structurez vos groupes académiques.</p><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="type" value="class"><label>Nom<input name="name" placeholder="Ex. GI-1A" required></label><label>Année académique<input name="academic_year" placeholder="2026-2027" pattern="\d{4}-\d{4}" required></label><label>Niveau<select name="niveau"><?php foreach (['1ère année','2ème année','3ème année','Bachelor'] as $level): ?><option><?= e($level) ?></option><?php endforeach; ?></select></label><button class="btn btn-primary" type="submit">+ Ajouter la classe</button></form>
</div>
<div class="academic-content-grid">
<section class="panel"><div class="panel-title"><div><span class="eyebrow">CATALOGUE</span><h2>Filières</h2><p><?= count($filieres) ?> parcours configuré(s)</p></div><label class="compact-search"><span>Rechercher</span><input type="search" placeholder="Nom de filière..." data-academic-filter="filieres"></label></div><div class="academic-list" data-academic-list="filieres"><?php if (!$filieres): ?><div class="empty">Aucune filière. Utilisez le formulaire ci-dessus pour commencer.</div><?php else: ?><?php foreach ($filieres as $f): ?><article class="academic-list-item" data-search-text="<?= e(strtolower((string)$f['name'] . ' ' . (string)$f['description'])) ?>"><div class="list-icon"><?= e(mb_strtoupper(mb_substr((string)$f['name'], 0, 1))) ?></div><div><h3><?= e($f['name']) ?></h3><p><?= e($f['description'] ?: 'Aucune description renseignée.') ?></p></div></article><?php endforeach; ?><div class="filter-empty empty" hidden>Aucun résultat.</div><?php endif; ?></div></section>
<section class="panel"><div class="panel-title"><div><span class="eyebrow">ORGANISATION</span><h2>Classes</h2><p><?= count($classes) ?> classe(s) configurée(s)</p></div><label class="compact-search"><span>Filtrer</span><select data-academic-filter="classes"><option value="">Tous les niveaux</option><?php foreach (array_keys($levelCounts) as $level): ?><option value="<?= e(strtolower((string)$level)) ?>"><?= e($level) ?> (<?= (int)$levelCounts[$level] ?>)</option><?php endforeach; ?></select></label></div><div class="academic-list" data-academic-list="classes"><?php if (!$classes): ?><div class="empty">Aucune classe enregistrée.</div><?php else: ?><?php foreach ($classes as $class): ?><article class="academic-list-item" data-search-text="<?= e(strtolower((string)$class['niveau'])) ?>"><div class="list-icon class-icon">C</div><div><h3><?= e($class['name']) ?></h3><p><?= e($class['niveau']) ?> <span class="list-separator">•</span> <?= e($class['academic_year']) ?></p></div><span class="pill"><?= e($class['academic_year']) ?></span></article><?php endforeach; ?><div class="filter-empty empty" hidden>Aucun résultat.</div><?php endif; ?></div></section>
</div>
<section class="panel"><div class="panel-title"><div><span class="eyebrow">UNITÉS D’ENSEIGNEMENT</span><h2>Modules</h2><p><?= count($modules) ?> module(s) disponible(s)</p></div><label class="compact-search"><span>Rechercher</span><input type="search" placeholder="Code ou nom..." data-academic-filter="modules"></label></div><div class="table-wrap"><table><thead><tr><th>Code</th><th>Module</th><th>Filière</th><th>Coefficient</th></tr></thead><tbody data-academic-list="modules"><?php if (!$modules): ?><tr><td colspan="4" class="empty">Aucun module enregistré.</td></tr><?php else: ?><?php foreach ($modules as $m): ?><tr data-search-text="<?= e(strtolower((string)$m['code'] . ' ' . (string)$m['name'] . ' ' . (string)($m['filiere'] ?? ''))) ?>"><td><span class="module-code"><?= e($m['code']) ?></span></td><td><b><?= e($m['name']) ?></b></td><td><?= e($m['filiere'] ?? 'Toutes') ?></td><td><span class="coefficient">× <?= e((string)$m['coefficient']) ?></span></td></tr><?php endforeach; ?><tr class="filter-empty" hidden><td colspan="4" class="empty">Aucun résultat.</td></tr><?php endif; ?></tbody></table></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
