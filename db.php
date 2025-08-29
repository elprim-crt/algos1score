<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/autoload.php';

use function App\Debug\debug_log;

/**
 * Returns a reference to the static PDO instance used by the application.
 *
 * @return \PDO|null Reference to the PDO instance or null if not connected.
 */
function &pdo_instance() {
    static $pdo = null;
    return $pdo;
}

/**
 * Get (and lazily create) the application's PDO connection.
 *
 * @return \PDO Active PDO connection.
 * @throws \PDOException When the connection cannot be established.
 */
function get_db() {
    $pdo = &pdo_instance();
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

/**
 * Close the database connection.
 *
 * Calling this function sets the internal PDO instance to null so a
 * subsequent call to {@see get_db()} will create a new connection. Use this
 * at the end of scripts or after long-running tasks to free up resources.
 */
function close_db() {
    $pdo = &pdo_instance();
    $pdo = null;
    debug_log('Database connection closed');
}
