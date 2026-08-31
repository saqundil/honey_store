<?php

declare(strict_types=1);

$baseUrl = rtrim(getenv('TEST_BASE_URL') ?: 'http://127.0.0.1:8080', '/');

function requestStatus(string $url, array $headers = []): int
{
    $context = stream_context_create(['http' => [
        'method' => 'POST',
        'header' => implode("\r\n", array_merge(['Content-Type: application/json'], $headers)),
        'content' => '{}',
        'ignore_errors' => true,
        'follow_location' => 0,
    ]]);
    file_get_contents($url, false, $context);
    preg_match('/\s(\d{3})\s/', $http_response_header[0] ?? '', $matches);
    return (int) ($matches[1] ?? 0);
}

assert(requestStatus($baseUrl . '/api/gradebook/values.php') === 302);

session_name('student_assessment_admin');
session_id('phase7-' . bin2hex(random_bytes(12)));
session_start();
$_SESSION['user'] = ['id' => 1, 'name' => 'Phase 7', 'role' => 'teacher', 'must_change_password' => false];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$cookie = session_name() . '=' . session_id();
session_write_close();

assert(requestStatus($baseUrl . '/api/gradebook/values.php', ["Cookie: {$cookie}"]) === 419);
assert(requestStatus($baseUrl . '/api/gradebook/status.php', ["Cookie: {$cookie}", 'X-CSRF-Token: invalid']) === 419);

echo "Phase 7 HTTP tests passed.\n";