<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$pdo = db();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(190) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
$applied = array_fill_keys($pdo->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN), true);
$files = glob(__DIR__ . '/migrations/*.sql') ?: [];
sort($files, SORT_STRING);

foreach ($files as $file) {
    $name = basename($file);
    if (isset($applied[$name])) {
        echo "Skipped {$name}\n";
        continue;
    }
    $statements = preg_split('/;\s*(?:\r?\n|$)/', (string) file_get_contents($file)) ?: [];
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
    $record = $pdo->prepare('INSERT INTO schema_migrations(migration) VALUES(?)');
    $record->execute([$name]);
    echo "Applied {$name}\n";
}