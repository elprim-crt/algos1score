<?php
require_once 'db.php';

header('Content-Type: application/json');

data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit;
}

$pdo = get_db();

if ($data['action'] === 'add') {
    $pair_id = (int)($data['pair_id'] ?? 0);
    $type = $data['type'];
    $date = $data['date'];

    // Validate pair_id
    $stmt = $pdo->prepare("SELECT id FROM pairs WHERE id = ?");
    $stmt->execute([$pair_id]);
    if (!$stmt->fetchColumn()) {
        echo json_encode(['success' => false, 'error' => 'Invalid pair_id']);
        exit;
    }

    if (!in_array($type, ['positive', 'negative'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid type']);
        exit;
    }

    // Insert trade
    $stmt = $pdo->prepare("INSERT INTO trades (pair_id, date, type) VALUES (?, ?, ?)");
    $stmt->execute([$pair_id, $date, $type]);

    // Return updated count for this type and pair in the last 14 days
    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM trades WHERE pair_id = ? AND type = ? AND date BETWEEN DATE_SUB(?, INTERVAL 13 DAY) AND ?");
    $stmt2->execute([$pair_id, $type, $date, $date]);
    $count = $stmt2->fetchColumn();

    echo json_encode(['success' => true, 'count' => $count]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
