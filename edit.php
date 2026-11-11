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
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    [$student, $errors] = validate_student($_POST);
    if (!$errors) {
        try {
            $stmt = $pdo->prepare('UPDATE etudiants SET code_apogee=:code_apogee,nom=:nom,prenom=:prenom,email=:email,filiere=:filiere,niveau=:niveau,date_naissance=:date_naissance WHERE id=:id');
            $stmt->execute(array_merge($student, ['id' => $id]));
            log_action($pdo, 'student_updated', $student['code_apogee']);
            flash('Étudiant modifié avec succès.');
            redirect('index.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') $errors[] = 'Code Apogée ou email déjà utilisé.';
            else throw $e;
        }
    }
}
$pageTitle = 'Modifier un étudiant';
$submitLabel = 'Enregistrer les modifications';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Retour à la liste</a><h1>Modifier un étudiant</h1><p>Mettez à jour les informations de la fiche étudiant.</p></div>
<?php require __DIR__ . '/includes/student_form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
