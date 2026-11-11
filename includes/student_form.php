<form method="post" class="form-card">
  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
  <?php if ($errors): ?>
    <div class="alert error" role="alert"><strong>Corrigez les erreurs :</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
  <?php endif; ?>
  <div class="form-grid">
    <label>Code Apogée <span>*</span><input type="text" name="code_apogee" maxlength="20" required value="<?= e($student['code_apogee'] ?? '') ?>" placeholder="Ex : EST1004"></label>
    <label>Filière <span>*</span><input type="text" name="filiere" maxlength="100" required value="<?= e($student['filiere'] ?? '') ?>" placeholder="Ex : Développement Informatique"></label>
    <label>Nom <span>*</span><input type="text" name="nom" maxlength="80" required value="<?= e($student['nom'] ?? '') ?>" placeholder="Nom de famille"></label>
    <label>Prénom <span>*</span><input type="text" name="prenom" maxlength="80" required value="<?= e($student['prenom'] ?? '') ?>" placeholder="Prénom"></label>
    <label>Email <span>*</span><input type="email" name="email" maxlength="150" required value="<?= e($student['email'] ?? '') ?>" placeholder="nom@example.com"></label>
    <label>Niveau <span>*</span><select name="niveau" required><option value="">Choisir un niveau</option><?php foreach (['1ère année','2ème année','3ème année','Bachelor'] as $level): ?><option value="<?= e($level) ?>" <?= ($student['niveau'] ?? '') === $level ? 'selected' : '' ?>><?= e($level) ?></option><?php endforeach; ?></select></label>
    <label>Date de naissance<input type="date" name="date_naissance" max="<?= date('Y-m-d') ?>" value="<?= e($student['date_naissance'] ?? '') ?>"></label>
  </div>
  <div class="form-actions"><a class="btn btn-light" href="index.php">Annuler</a><button class="btn btn-primary" type="submit"><?= e($submitLabel) ?></button></div>
</form>
