<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/autoload.php';

use function App\Debug\debug_log;

function get_db() {
    static $pdo;
    if (!$pdo) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            debug_log('Database connection established');
        } catch (PDOException $e) {
            debug_log('DB connection failed: ' . $e->getMessage());
            throw $e;
        }
    }
    return $pdo;
}
