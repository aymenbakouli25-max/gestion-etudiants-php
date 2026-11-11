<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_auth();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Choisissez un fichier CSV valide.';
    } else {
        $handle = fopen($_FILES['csv']['tmp_name'], 'rb');
        $insert = $pdo->prepare('INSERT INTO etudiants (code_apogee, nom, prenom, email, filiere, niveau, date_naissance) VALUES (:code, :nom, :prenom, :email, :filiere, :niveau, :date_naissance)');
        $count = 0;
        if ($handle) {
            fgetcsv($handle, 0, ';');
            while (($row = fgetcsv($handle, 0, ';')) !== false) {
                if (count($row) < 7) continue;
                [$code, $nom, $prenom, $email, $filiere, $niveau, $birth] = array_map('trim', array_slice($row, 0, 7));
                if ($code === '' || $nom === '' || $prenom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($niveau, ['1ère année', '2ème année', '3ème année', 'Bachelor'], true)) continue;
                try {
                    $insert->execute(['code' => $code, 'nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'filiere' => $filiere, 'niveau' => $niveau, 'date_naissance' => $birth ?: null]);
                    $count++;
                } catch (PDOException $e) {
                    if ($e->getCode() !== '23000') throw $e;
                }
            }
            fclose($handle);
        }
        log_action($pdo, 'students_imported', 'count=' . $count);
        flash($count . ' étudiant(s) importé(s).');
        redirect('student_import.php');
    }
}
$pageTitle = 'Importer les étudiants';
require __DIR__ . '/includes/header.php';
?>
<div class="page-heading"><a href="index.php" class="back">← Retour à la liste</a><h1>Importer les étudiants</h1><p>CSV séparé par des points-virgules : code;nom;prenom;email;filiere;niveau;date_naissance.</p></div>
<form method="post" enctype="multipart/form-data" class="form-card"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><?php if ($errors): ?><div class="alert error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?><label>Fichier CSV<input type="file" name="csv" accept=".csv,text/csv" required></label><div class="form-actions"><button class="btn btn-primary" type="submit">Importer</button></div></form>
<?php require __DIR__ . '/includes/footer.php'; ?>
