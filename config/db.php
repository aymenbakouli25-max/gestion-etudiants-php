<?php
declare(strict_types=1);

// XAMPP (par défaut) : root avec mot de passe vide.
// Modifiez les variables ci-dessous si nécessaire.
$host = getenv('DB_HOST') ?: '127.0.0.1';
$database = getenv('DB_NAME') ?: 'gestion_etudiants';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS etudiants (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code_apogee VARCHAR(20) NOT NULL UNIQUE,
            nom VARCHAR(80) NOT NULL,
            prenom VARCHAR(80) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            filiere VARCHAR(100) NOT NULL,
            niveau VARCHAR(20) NOT NULL,
            date_naissance DATE NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );
    try {
        $pdo->exec('ALTER TABLE etudiants ADD COLUMN status ENUM("active","graduated","suspended","withdrawn") NOT NULL DEFAULT "active"');
    } catch (PDOException $e) {
        if ($e->getCode() !== '42S21') {
            throw $e;
        }
    }
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM("admin","staff") NOT NULL DEFAULT "staff",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );
    try {
        $pdo->exec('ALTER TABLE users ADD COLUMN role ENUM("admin","staff") NOT NULL DEFAULT "staff"');
    } catch (PDOException $e) {
        if ($e->getCode() !== '42S21') {
            throw $e;
        }
    }
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS notes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id INT UNSIGNED NOT NULL,
            subject VARCHAR(100) NOT NULL,
            grade DECIMAL(5,2) NOT NULL,
            coefficient DECIMAL(4,2) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_notes_student FOREIGN KEY (student_id) REFERENCES etudiants(id) ON DELETE CASCADE
        ) ENGINE=InnoDB'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS attendance (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id INT UNSIGNED NOT NULL,
            attendance_date DATE NOT NULL,
            status ENUM("present","absent","late") NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_attendance (student_id, attendance_date),
            CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES etudiants(id) ON DELETE CASCADE
        ) ENGINE=InnoDB'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            action VARCHAR(100) NOT NULL,
            details VARCHAR(255) NOT NULL DEFAULT "",
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_audit_created_at (created_at),
            CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS filieres (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            description TEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS modules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            filiere_id INT UNSIGNED NULL,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(30) NOT NULL UNIQUE,
            coefficient DECIMAL(4,2) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_modules_filiere FOREIGN KEY (filiere_id) REFERENCES filieres(id) ON DELETE SET NULL
        ) ENGINE=InnoDB'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS classes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            academic_year VARCHAR(9) NOT NULL,
            niveau VARCHAR(20) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_class_year (name, academic_year)
        ) ENGINE=InnoDB'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS student_workflow (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id INT UNSIGNED NOT NULL,
            status ENUM("new","in_review","approved","rejected","completed") NOT NULL DEFAULT "new",
            note TEXT NULL,
            updated_by INT UNSIGNED NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_student_workflow (student_id),
            CONSTRAINT fk_workflow_student FOREIGN KEY (student_id) REFERENCES etudiants(id) ON DELETE CASCADE,
            CONSTRAINT fk_workflow_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS documents (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            student_id INT UNSIGNED NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(100) NOT NULL UNIQUE,
            mime_type VARCHAR(100) NOT NULL,
            file_size INT UNSIGNED NOT NULL,
            uploaded_by INT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_documents_student FOREIGN KEY (student_id) REFERENCES etudiants(id) ON DELETE CASCADE,
            CONSTRAINT fk_documents_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS api_tokens (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_api_token_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS api_rate_limits (
            token_hash CHAR(64) PRIMARY KEY,
            window_started_at DATETIME NOT NULL,
            request_count INT UNSIGNED NOT NULL DEFAULT 0
        ) ENGINE=InnoDB'
    );
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash, role)
         VALUES (:username, :password_hash, "admin")
         ON DUPLICATE KEY UPDATE username = VALUES(username), role = "admin"'
    );
    $stmt->execute([
        'username' => 'admin',
        'password_hash' => '$2y$12$T0OwhI2FoDd7rTk1vw4zke3cxiDRjOtMBCLbZ6JKO7VbAubsAY1CK',
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Connexion MySQL impossible. Démarrez MySQL dans XAMPP, importez database/gestion_etudiants.sql et vérifiez config/db.php.');
}
