<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';
require_admin();
$tables = ['users', 'etudiants', 'notes', 'attendance', 'audit_logs'];
$output = "-- Sauvegarde EST Manager\nSET FOREIGN_KEY_CHECKS=0;\n\n";
foreach ($tables as $table) {
    $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
    $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
    $output .= $create['Create Table'] . ";\n";
    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_NUM);
    foreach ($rows as $row) {
        $values = array_map(static fn ($value): string => $value === null ? 'NULL' : $pdo->quote((string)$value), $row);
        $output .= "INSERT INTO `{$table}` VALUES (" . implode(',', $values) . ");\n";
    }
    $output .= "\n";
}
$output .= "SET FOREIGN_KEY_CHECKS=1;\n";
header('Content-Type: application/sql; charset=UTF-8');
header('Content-Disposition: attachment; filename="gestion_etudiants_backup_' . date('Y-m-d_H-i-s') . '.sql"');
echo $output;
