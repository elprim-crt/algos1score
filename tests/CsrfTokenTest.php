<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../csrf.php';

class CsrfTokenTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    protected function tearDown(): void
    {
        session_destroy();
        $_SESSION = [];
    }

    public function testCsrfTokenValidation(): void
    {
        $token = get_csrf_token();
        $this->assertTrue(validate_csrf_token($token));
        $this->assertFalse(validate_csrf_token('invalid'));
    }
}
