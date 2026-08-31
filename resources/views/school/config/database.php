<?php

declare(strict_types=1);

$environment = static function (string $key, string $default = ''): string {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    return $value === false || $value === null ? $default : (string) $value;
};

return [
    'dsn' => sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $environment('SCHOOL_DB_HOST', '127.0.0.1'),
        $environment('SCHOOL_DB_PORT', '3306'),
        $environment('SCHOOL_DB_NAME', 'student_assessment')
    ),
    'username' => $environment('SCHOOL_DB_USER', 'root'),
    'password' => $environment('SCHOOL_DB_PASS'),
];