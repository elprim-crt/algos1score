<?php

use PHPUnit\Framework\TestCase;

class IndexPageTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        putenv('DB_DSN=sqlite::memory:');
        require_once __DIR__ . '/../db.php';
        require_once __DIR__ . '/../csrf.php';
        $this->pdo = get_db();
        $this->pdo->exec('CREATE TABLE pairs (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE NOT NULL)');
        $this->pdo->exec("CREATE TABLE trades (id INTEGER PRIMARY KEY AUTOINCREMENT, pair_id INTEGER NOT NULL, date TEXT NOT NULL, type TEXT NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(pair_id) REFERENCES pairs(id) ON DELETE CASCADE, UNIQUE(pair_id, date, type))");
        $this->pdo->exec("INSERT INTO pairs (id, name) VALUES (1, 'BTCUSD')");
        $this->pdo->exec("INSERT INTO trades (pair_id, date, type) VALUES (1, '2024-01-01', 'positive'), (1, '2024-01-01', 'negative'), (1, '2024-01-02', 'positive')");
    }

    protected function tearDown(): void
    {
        close_db();
        $_SESSION = [];
        session_destroy();
    }

    public function testIndexDisplaysPairsAndCounts(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['date'] = '2024-01-02';
        $token = get_csrf_token();
        ob_start();
        include __DIR__ . '/../index.php';
        $output = ob_get_clean();
        $this->assertStringContainsString('Trading Pairs - Last 14 Days', $output);
        $this->assertStringContainsString('BTCUSD', $output);
        $this->assertMatchesRegularExpression('/<td class="positive">\s*2\s*<\/td>/', $output);
        $this->assertMatchesRegularExpression('/<td class="negative">\s*1\s*<\/td>/', $output);
        $this->assertStringContainsString('name="csrf_token" value="'.htmlspecialchars($token, ENT_QUOTES).'"', $output);
    }
}
