<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
$provided = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? '');
if (!preg_match('/^Bearer\s+(.+)$/i', $provided, $matches)) json_response(['error' => 'Token requis.'], 401);
$tokenHash = hash('sha256', $matches[1]);
$auth = $pdo->prepare('SELECT u.id FROM api_tokens t JOIN users u ON u.id = t.user_id WHERE t.token_hash = :token');
$auth->execute(['token' => $tokenHash]);
if (!$auth->fetch()) json_response(['error' => 'Token invalide.'], 401);
$rate = $pdo->prepare('SELECT window_started_at, request_count FROM api_rate_limits WHERE token_hash = :token');
$rate->execute(['token' => $tokenHash]);
$rateRow = $rate->fetch();
$now = new DateTimeImmutable();
if (!$rateRow || $now->getTimestamp() - strtotime($rateRow['window_started_at']) >= 60) {
    $pdo->prepare('REPLACE INTO api_rate_limits (token_hash, window_started_at, request_count) VALUES (:token, NOW(), 1)')->execute(['token' => $tokenHash]);
} elseif ((int)$rateRow['request_count'] >= 60) {
    json_response(['error' => 'Trop de requêtes. Réessayez dans une minute.'], 429);
} else {
    $pdo->prepare('UPDATE api_rate_limits SET request_count = request_count + 1 WHERE token_hash = :token')->execute(['token' => $tokenHash]);
}
$method = $_SERVER['REQUEST_METHOD'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($method === 'GET') {
    $stmt = $id ? $pdo->prepare('SELECT id, code_apogee, nom, prenom, email, filiere, niveau, status FROM etudiants WHERE id = :id') : $pdo->query('SELECT id, code_apogee, nom, prenom, email, filiere, niveau, status FROM etudiants ORDER BY nom, prenom');
    if ($id) $stmt->execute(['id' => $id]);
    $data = $stmt->fetchAll();
    json_response(['data' => $data]);
}
$payload = request_json();
if ($method === 'POST') {
    $required = ['code_apogee', 'nom', 'prenom', 'email', 'filiere', 'niveau'];
    foreach ($required as $field) if (trim((string)($payload[$field] ?? '')) === '') json_response(['error' => "Champ requis: {$field}"], 422);
    $stmt = $pdo->prepare('INSERT INTO etudiants (code_apogee, nom, prenom, email, filiere, niveau, date_naissance) VALUES (:code_apogee, :nom, :prenom, :email, :filiere, :niveau, :date_naissance)');
    try {
        $stmt->execute(array_merge(array_intersect_key($payload, array_flip($required)), ['date_naissance' => $payload['date_naissance'] ?? null]));
    } catch (PDOException $e) {
        json_response(['error' => $e->getCode() === '23000' ? 'Code ou email déjà utilisé.' : 'Erreur base de données.'], 422);
    }
    json_response(['id' => (int)$pdo->lastInsertId()], 201);
}
if (in_array($method, ['PUT', 'PATCH'], true)) {
    if (!$id) json_response(['error' => 'ID requis.'], 422);
    $allowed = array_intersect_key($payload, array_flip(['nom', 'prenom', 'email', 'filiere', 'niveau', 'date_naissance', 'status']));
    if (!$allowed) json_response(['error' => 'Aucune donnée à modifier.'], 422);
    if (isset($allowed['email']) && !filter_var($allowed['email'], FILTER_VALIDATE_EMAIL)) json_response(['error' => 'Email invalide.'], 422);
    if (isset($allowed['niveau']) && !in_array($allowed['niveau'], ['1ère année', '2ème année', '3ème année', 'Bachelor'], true)) json_response(['error' => 'Niveau invalide.'], 422);
    if (isset($allowed['status']) && !in_array($allowed['status'], ['active', 'graduated', 'suspended', 'withdrawn'], true)) json_response(['error' => 'Statut invalide.'], 422);
    $sets = [];
    foreach (array_keys($allowed) as $field) $sets[] = "{$field} = :{$field}";
    $allowed['id'] = $id;
    $stmt = $pdo->prepare('UPDATE etudiants SET ' . implode(', ', $sets) . ' WHERE id = :id');
    $stmt->execute($allowed);
    json_response(['updated' => $stmt->rowCount()]);
}
if ($method === 'DELETE') {
    if (!$id) json_response(['error' => 'ID requis.'], 422);
    $stmt = $pdo->prepare('DELETE FROM etudiants WHERE id = :id');
    $stmt->execute(['id' => $id]);
    json_response(['deleted' => $stmt->rowCount()]);
}
json_response(['error' => 'Méthode non autorisée.'], 405);
