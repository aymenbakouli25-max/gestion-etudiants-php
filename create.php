<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
$student = [];
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    [$student, $errors] = validate_student($_POST);
    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO etudiants (code_apogee,nom,prenom,email,filiere,niveau,date_naissance) VALUES (:code_apogee,:nom,:prenom,:email,:filiere,:niveau,:date_naissance)');
            $stmt->execute($student);
            log_action($pdo, 'student_created', $student['code_apogee']);
            flash('Étudiant ajouté avec succès.');
            redirect('index.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') $errors[] = 'Code Apogée ou email déjà utilisé.';
            else throw $e;
        }
    }
}
$pageTitle = 'Ajouter un étudiant';
$submitLabel = 'Enregistrer l’étudiant';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Retour à la liste</a><h1>Ajouter un étudiant</h1><p>Remplissez les informations ci-dessous pour créer une nouvelle fiche.</p></div>
<?php require __DIR__ . '/includes/student_form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
