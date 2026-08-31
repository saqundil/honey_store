<?php

declare(strict_types=1);

return [
    'dsn' => sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        getenv('SCHOOL_DB_HOST') ?: '127.0.0.1',
        getenv('SCHOOL_DB_PORT') ?: '3306',
        getenv('SCHOOL_DB_NAME') ?: 'student_assessment'
    ),
    'username' => getenv('SCHOOL_DB_USER') ?: 'root',
    'password' => getenv('SCHOOL_DB_PASS') ?: '',
];