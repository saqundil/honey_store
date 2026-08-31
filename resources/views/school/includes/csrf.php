<?php

declare(strict_types=1);

function school_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function school_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . school_e(school_csrf_token()) . '">';
}

function verify_csrf(?string $token): void
{
    if (!$token || !hash_equals(school_csrf_token(), $token)) {
        json_response(['ok' => false, 'message' => 'رمز الحماية غير صالح.'], 419);
    }
}