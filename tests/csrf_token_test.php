<?php
require_once __DIR__ . '/../csrf.php';

$token = get_csrf_token();
if (!validate_csrf_token($token)) {
    echo "Token validation failed for valid token\n";
    exit(1);
}

if (validate_csrf_token('invalid')) {
    echo "Token validation succeeded for invalid token\n";
    exit(1);
}

echo "CSRF token tests passed\n";
