<?php

declare(strict_types=1);

$GLOBALS['app_config'] = require dirname(__DIR__) . '/config/app.php';
date_default_timezone_set($GLOBALS['app_config']['timezone']);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($GLOBALS['app_config']['session_name']);
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

function db(): PDO
{
    static $pdo;
    if (!$pdo) {
        $settings = require dirname(__DIR__) . '/config/database.php';
        $pdo = new PDO($settings['dsn'], $settings['username'], $settings['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}