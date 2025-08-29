<?php

use PHPUnit\Framework\TestCase;

class TradesApiTest extends TestCase
{
    private PDO $pdo;
    private string $csrfToken;

    protected function setUp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        putenv('DB_DSN=sqlite::memory:');
        require_once __DIR__ . '/../trades.php';

        $this->pdo = get_db();
        $this->pdo->exec('CREATE TABLE pairs (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE NOT NULL)');
        $this->pdo->exec("CREATE TABLE trades (id INTEGER PRIMARY KEY AUTOINCREMENT, pair_id INTEGER NOT NULL, date TEXT NOT NULL, type TEXT NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(pair_id) REFERENCES pairs(id) ON DELETE CASCADE)");
        $this->pdo->exec("INSERT INTO pairs (name) VALUES ('BTCUSD')");
        $this->csrfToken = get_csrf_token();
    }

    protected function tearDown(): void
    {
        close_db();
        $_SESSION = [];
        session_destroy();
    }

    public function testMissingOrMalformedBody(): void
    {
        $response = handle_trades(null);
        $this->assertFalse($response['success']);
        $this->assertSame('Malformed or missing request body', $response['error']);

        $response = handle_trades(['csrf_token' => $this->csrfToken]);
        $this->assertFalse($response['success']);
        $this->assertSame('Malformed or missing request body', $response['error']);
    }

    public function testInvalidCsrfToken(): void
    {
        $response = handle_trades(['action' => 'add', 'csrf_token' => 'bad']);
        $this->assertFalse($response['success']);
        $this->assertSame('Invalid CSRF token', $response['error']);
    }

    public function testAddTradeAllowsDuplicates(): void
    {
        $payload = [
            'action' => 'add',
            'pair_id' => 1,
            'type' => 'positive',
            'date' => '2024-01-01',
            'csrf_token' => $this->csrfToken,
        ];
        $response = handle_trades($payload);
        $this->assertTrue($response['success']);
        $this->assertSame(1, $response['count']);

        $duplicate = handle_trades($payload);
        $this->assertTrue($duplicate['success']);
        $this->assertSame(2, $duplicate['count']);
    }

    public function testListTrades(): void
    {
        $payload = [
            'action' => 'add',
            'pair_id' => 1,
            'type' => 'positive',
            'date' => '2024-01-01',
            'csrf_token' => $this->csrfToken,
        ];
        handle_trades($payload);
        $payload['type'] = 'negative';
        handle_trades($payload);

        $list = handle_trades([
            'action' => 'list',
            'pair_id' => 1,
            'date' => '2024-01-01',
            'csrf_token' => $this->csrfToken,
        ]);

        $this->assertTrue($list['success']);
        $this->assertCount(2, $list['trades']);
        $types = array_column($list['trades'], 'type');
        $this->assertContains('positive', $types);
        $this->assertContains('negative', $types);
    }
}
