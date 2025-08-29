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
    debug_log(['request' => $data]);

    if (!isset($data['action'])) {
        debug_log('No action specified');
        return ['success' => false, 'error' => 'No action specified'];
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
            $type = $data['type'];
            $date = $data['date'];

            // Validate pair_id
            $stmt = $pdo->prepare("SELECT id FROM pairs WHERE id = ?");
            $stmt->execute([$pair_id]);
            if (!$stmt->fetchColumn()) {
                debug_log("Invalid pair_id: $pair_id");
                return ['success' => false, 'error' => 'Invalid pair_id'];
            }

            if (!in_array($type, ['positive', 'negative'])) {
                debug_log("Invalid type: $type");
                return ['success' => false, 'error' => 'Invalid type'];
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
            $date = $data['date'];

            // Validate pair_id
            $stmt = $pdo->prepare("SELECT id FROM pairs WHERE id = ?");
            $stmt->execute([$pair_id]);
            if (!$stmt->fetchColumn()) {
                debug_log("Invalid pair_id: $pair_id");
                return ['success' => false, 'error' => 'Invalid pair_id'];
            }

            $driver = DB_DSN ? explode(':', DB_DSN, 2)[0] : 'mysql';
            if ($driver === 'sqlite') {
                $sql = "SELECT date, type FROM trades WHERE pair_id = ? " .
                    "AND date BETWEEN date(?, '-13 day') AND ? ORDER BY date DESC, id DESC";
            } else {
                $sql = "SELECT date, type FROM trades WHERE pair_id = ? " .
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

    debug_log('Unknown action: ' . $data['action']);
    return ['success' => false, 'error' => 'Unknown action'];
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Content-Type: application/json');
    $data = json_decode(file_get_contents('php://input'), true);
    echo json_encode(handle_trades($data));
}
