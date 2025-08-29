<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token(?string $token): bool {
    $session_token = $_SESSION['csrf_token'] ?? '';
    if (!is_string($token) || $session_token === '') {
        return false;
    }
    return hash_equals($session_token, $token);
}
