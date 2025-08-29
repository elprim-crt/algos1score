<?php
require_once 'db.php';
require_once 'debug.php';
require_once 'csrf.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
debug_log(['request' => $data]);

if (!isset($data['action'])) {
    debug_log('No action specified');
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit;
}

if (!validate_csrf_token($data['csrf_token'] ?? '')) {
    debug_log('Invalid CSRF token');
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

try {
    $pdo = get_db();
} catch (Exception $e) {
    debug_log('DB connection error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error. Please try again later.']);
    exit;
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
            echo json_encode(['success' => false, 'error' => 'Invalid pair_id']);
            exit;
        }

        if (!in_array($type, ['positive', 'negative'])) {
            debug_log("Invalid type: $type");
            echo json_encode(['success' => false, 'error' => 'Invalid type']);
            exit;
        }

        // Insert trade
        $stmt = $pdo->prepare("INSERT INTO trades (pair_id, date, type) VALUES (?, ?, ?)");
        $stmt->execute([$pair_id, $date, $type]);
        debug_log("Inserted trade pair_id=$pair_id type=$type date=$date");

        // Return updated count for this type and pair in the last 14 days
        $stmt2 = $pdo->prepare(
            "SELECT COUNT(*) FROM trades WHERE pair_id = ? AND type = ? " .
            "AND date BETWEEN DATE_SUB(?, INTERVAL 13 DAY) AND ?"
        );
        $stmt2->execute([$pair_id, $type, $date, $date]);
        $count = $stmt2->fetchColumn();

        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    } catch (Exception $e) {
        debug_log('Error processing trade: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Database error. Please try again later.']);
        exit;
    }
}

debug_log('Unknown action: ' . $data['action']);
echo json_encode(['success' => false, 'error' => 'Unknown action']);
