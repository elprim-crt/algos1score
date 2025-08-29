<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

use function App\Debug\debug_log;

$error_message = null;
$pair_name = '';
$pair_id = isset($_GET['pair_id']) ? (int)$_GET['pair_id'] : 0;
$date = $_GET['date'] ?? date('Y-m-d');
$csrf_token = get_csrf_token();

try {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT name FROM pairs WHERE id = ?');
    $stmt->execute([$pair_id]);
    $pair_name = $stmt->fetchColumn();
    if (!$pair_name) {
        $error_message = 'Invalid pair ID.';
    }
} catch (Exception $e) {
    debug_log('Error fetching pair: ' . $e->getMessage());
    $error_message = 'An error occurred while retrieving data. Please try again later.';
}

$trades = [];
if (!$error_message) {
    try {
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
    } catch (Exception $e) {
        debug_log('Error fetching trades: ' . $e->getMessage());
        $error_message = 'Could not retrieve trades.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trades</title>
    <style>
        body { font-family: sans-serif; margin: 2em; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 0.5em 1em; border: 1px solid #ccc; }
        button.remove-trade { color: #fff; background: #f44336; border: none; padding: 0.3em 0.7em; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Trades for <?= htmlspecialchars(strtoupper($pair_name)) ?> - Last 14 Days</h2>
    <?php if ($error_message): ?>
        <p style="color:red;"><?= htmlspecialchars($error_message) ?></p>
    <?php elseif (empty($trades)): ?>
        <p>No trades in this period.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Date</th><th>Type</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach ($trades as $t): ?>
                <tr data-trade-id="<?= (int)$t['id'] ?>">
                    <td><?= htmlspecialchars($t['date']) ?></td>
                    <td><?= htmlspecialchars($t['type']) ?></td>
                    <td><button class="remove-trade" data-id="<?= (int)$t['id'] ?>">Remove</button></td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <input type="hidden" id="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <?php endif; ?>
    <p><a href="index.php?date=<?= htmlspecialchars($date) ?>">Back to main page</a></p>
    <script src="assets/js/trades_view.js"></script>
</body>
</html>
