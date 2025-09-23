<?php
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

use function App\Debug\debug_log;

/**
 * Handle a trade API request.
 *
 * @param array|null $data Decoded JSON request data
 * @return array Response array to be JSON-encoded
 */
function handle_trades(?array $data): array {
    $logData = $data;
    if (is_array($logData)) {
        $sensitiveKeys = [
            'csrf_token',
            'password',
            'secret',
            'api_key',
            'auth_token',
            'access_token',
            'refresh_token',
        ];

        foreach ($logData as $key => $value) {
            $normalizedKey = is_string($key) ? strtolower($key) : $key;
            if (is_string($normalizedKey) && (in_array($normalizedKey, $sensitiveKeys, true) || str_contains($normalizedKey, 'token'))) {
                $logData[$key] = '[redacted]';
            }
        }
    }

    debug_log(['request' => $logData]);

    if (!is_array($data) || !isset($data['action'])) {
        debug_log('Malformed or missing request body');
        return ['success' => false, 'error' => 'Malformed or missing request body'];
    }

    if (!validate_csrf_token($data['csrf_token'] ?? '')) {
        debug_log('Invalid CSRF token');
        return ['success' => false, 'error' => 'Invalid CSRF token'];
    }

    try {
        $pdo = get_db();
    } catch (Exception $e) {
        debug_log('DB connection error: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Database error. Please try again later.'];
    }

    if ($data['action'] === 'add') {
        try {
            $pair_id = (int)($data['pair_id'] ?? 0);

            if (!array_key_exists('type', $data) || !is_string($data['type']) || trim($data['type']) === '') {
                debug_log('Missing type');
                return ['success' => false, 'error' => 'Missing type'];
            }
            $type = $data['type'];

            if (!array_key_exists('date', $data) || !is_string($data['date']) || trim($data['date']) === '') {
                debug_log('Missing date');
                return ['success' => false, 'error' => 'Missing date'];
            }
            $date = $data['date'];

            $dateTime = DateTime::createFromFormat('Y-m-d', $date);
            $dateErrors = DateTime::getLastErrors();
            $isValidDate = $dateTime instanceof DateTimeInterface
                && ($dateErrors === false || (($dateErrors['warning_count'] ?? 0) === 0 && ($dateErrors['error_count'] ?? 0) === 0))
                && $dateTime->format('Y-m-d') === $date;
            if (!$isValidDate) {
                debug_log("Invalid date format: $date");
                return ['success' => false, 'error' => 'Invalid date format'];
            }

            if (!in_array($type, ['positive', 'negative'], true)) {
                debug_log("Invalid type: $type");
                return ['success' => false, 'error' => 'Invalid type'];
            }

            // Validate pair_id
            $stmt = $pdo->prepare("SELECT id FROM pairs WHERE id = ?");
            $stmt->execute([$pair_id]);
            if (!$stmt->fetchColumn()) {
                debug_log("Invalid pair_id: $pair_id");
                return ['success' => false, 'error' => 'Invalid pair_id'];
            }

            // Insert trade
            $stmt = $pdo->prepare("INSERT INTO trades (pair_id, date, type) VALUES (?, ?, ?)");
            $stmt->execute([$pair_id, $date, $type]);
            debug_log("Inserted trade pair_id=$pair_id type=$type date=$date");

            // Return updated count for this type and pair in the last 14 days
            $driver = DB_DSN ? explode(':', DB_DSN, 2)[0] : 'mysql';
            if ($driver === 'sqlite') {
                $sql = "SELECT COUNT(*) FROM trades WHERE pair_id = ? AND type = ? " .
                    "AND date BETWEEN date(?, '-13 day') AND ?";
            } else {
                $sql = "SELECT COUNT(*) FROM trades WHERE pair_id = ? AND type = ? " .
                    "AND date BETWEEN DATE_SUB(?, INTERVAL 13 DAY) AND ?";
            }
            $stmt2 = $pdo->prepare($sql);
            $stmt2->execute([$pair_id, $type, $date, $date]);
            $count = $stmt2->fetchColumn();

            return ['success' => true, 'count' => $count];
        } catch (Exception $e) {
            debug_log('Error processing trade: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Database error. Please try again later.'];
        }
    }

    if ($data['action'] === 'list') {
        try {
            $pair_id = (int)($data['pair_id'] ?? 0);

            if (!array_key_exists('date', $data) || !is_string($data['date']) || trim($data['date']) === '') {
                debug_log('Missing date');
                return ['success' => false, 'error' => 'Missing date'];
            }
            $date = $data['date'];

            $dateTime = DateTime::createFromFormat('Y-m-d', $date);
            $dateErrors = DateTime::getLastErrors();
            $isValidDate = $dateTime instanceof DateTimeInterface
                && ($dateErrors === false || (($dateErrors['warning_count'] ?? 0) === 0 && ($dateErrors['error_count'] ?? 0) === 0))
                && $dateTime->format('Y-m-d') === $date;
            if (!$isValidDate) {
                debug_log("Invalid date format: $date");
                return ['success' => false, 'error' => 'Invalid date format'];
            }

            // Validate pair_id
            $stmt = $pdo->prepare("SELECT id FROM pairs WHERE id = ?");
            $stmt->execute([$pair_id]);
            if (!$stmt->fetchColumn()) {
                debug_log("Invalid pair_id: $pair_id");
                return ['success' => false, 'error' => 'Invalid pair_id'];
            }

            $driver = DB_DSN ? explode(':', DB_DSN, 2)[0] : 'mysql';
            if ($driver === 'sqlite') {
                $sql = "SELECT id, date, type FROM trades WHERE pair_id = ? " .
                    "AND date BETWEEN date(?, '-13 day') AND ? ORDER BY date DESC, id DESC";
            } else {
                $sql = "SELECT id, date, type FROM trades WHERE pair_id = ? " .
                    "AND date BETWEEN DATE_SUB(?, INTERVAL 13 DAY) AND ? ORDER BY date DESC, id DESC";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$pair_id, $date, $date]);
            $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['success' => true, 'trades' => $trades];
        } catch (Exception $e) {
            debug_log('Error fetching trades: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Database error. Please try again later.'];
        }
    }

    if ($data['action'] === 'remove') {
        try {
            $id = (int)($data['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM trades WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) {
                debug_log("Trade not found: $id");
                return ['success' => false, 'error' => 'Trade not found'];
            }
            return ['success' => true];
        } catch (Exception $e) {
            debug_log('Error deleting trade: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Database error. Please try again later.'];
        }
    }

    debug_log('Unknown action: ' . $data['action']);
    return ['success' => false, 'error' => 'Unknown action'];
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    echo json_encode(handle_trades($data));
}
