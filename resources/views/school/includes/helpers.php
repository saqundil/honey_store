<?php

declare(strict_types=1);

function school_config(string $key, mixed $default = null): mixed
{
    $segments = explode('.', $key);
    $value = $GLOBALS['app_config'] ?? [];
    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

function school_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function school_url(string $path = ''): string
{
    if (str_starts_with($path, 'assets/')) {
        $baseUrl = school_config('base_url');
        $schoolPath = parse_url($baseUrl, PHP_URL_PATH) ?: '/school';

        return substr($baseUrl, 0, -strlen($schoolPath)).'/school-assets/'.substr($path, strlen('assets/'));
    }

    return school_config('base_url') . ($path === '' ? '' : '/' . ltrim($path, '/'));
}

function school_redirect(string $path): never
{
    header('Location: ' . school_url($path));
    exit;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_json(): array
{
    $payload = json_decode((string) file_get_contents('php://input'), true);
    return is_array($payload) ? $payload : [];
}

/**
 * Short Arabic relative time for card timestamps ("قبل دقيقة"، "قبل 3 أيام"، ...).
 * Falls back to a Y-m-d date when older than a month.
 */
function ar_relative_time(?string $timestamp): string
{
    if (!$timestamp) return 'لا يوجد نشاط بعد';
    $ts = strtotime($timestamp);
    if (!$ts) return '';
    $diff = time() - $ts;
    if ($diff < 60)      return 'قبل لحظات';
    if ($diff < 3600)    { $m = (int) floor($diff / 60);   return $m === 1 ? 'قبل دقيقة' : "قبل {$m} دقيقة"; }
    if ($diff < 86400)   { $h = (int) floor($diff / 3600); return $h === 1 ? 'قبل ساعة'  : "قبل {$h} ساعة"; }
    if ($diff < 604800)  { $d = (int) floor($diff / 86400); return $d === 1 ? 'قبل يوم'    : "قبل {$d} أيام"; }
    if ($diff < 2592000) { $w = (int) floor($diff / 604800); return $w === 1 ? 'قبل أسبوع' : "قبل {$w} أسابيع"; }
    return date('Y-m-d', $ts);
}