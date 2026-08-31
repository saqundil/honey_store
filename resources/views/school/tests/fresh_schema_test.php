<?php

declare(strict_types=1);

$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$database = 'student_assessment_test_' . bin2hex(random_bytes(6));
$pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

try {
    $schema = (string) file_get_contents(dirname(__DIR__) . '/database/schema.sql');
    $schema = preg_replace('/\A(?:CREATE DATABASE[^;]+;\s*USE\s+student_assessment;)/i', "CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE `{$database}`;", $schema, 1, $replacements);
    assert($replacements === 1);

    $statements = preg_split('/;\s*(?:\r?\n|$)/', $schema) ?: [];
    foreach ($statements as $statement) {
        if (trim($statement) !== '') $pdo->exec($statement);
    }

    $pdo->exec("USE `{$database}`");
    assert((int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='{$database}'")->fetchColumn() >= 25);
    assert((int) $pdo->query("SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA='{$database}'")->fetchColumn() === 8);
    $migrationFiles = array_map('basename', glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: []);
    $recordedMigrations = $pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    sort($migrationFiles);
    sort($recordedMigrations);
    assert($recordedMigrations === $migrationFiles);
    echo "Fresh schema test passed.\n";
} finally {
    $pdo->exec('USE information_schema');
    $pdo->exec("DROP DATABASE IF EXISTS `{$database}`");
}