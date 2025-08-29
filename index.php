<?php
require_once 'db.php';
require_once 'csrf.php';

use function App\Debug\debug_log;

$error_message = null;
$csrf_token = get_csrf_token();

// Handle add new pair form submission (POST with normal form, not AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_pair'])) {
    if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid CSRF token.';
    } else {
        $new_pair = strtoupper(trim($_POST['new_pair']));
        // Validate pair: 1-20 characters, uppercase alphanumeric, underscore or hyphen
        if (!preg_match('/^[A-Z0-9_-]{1,20}$/', $new_pair)) {
            $error_message = 'Invalid trading pair. Use 1-20 letters, numbers, hyphens, or underscores.';
        } else {
            try {
                $pdo = get_db();
                $driver = DB_DSN ? explode(':', DB_DSN, 2)[0] : 'mysql';
                $sql = $driver === 'sqlite'
                    ? "INSERT OR IGNORE INTO pairs (name) VALUES (?)"
                    : "INSERT IGNORE INTO pairs (name) VALUES (?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$new_pair]);
                // Redirect to avoid resubmission only on success when running via web server
                if (php_sapi_name() !== 'cli') {
                    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . (!empty($_GET) ? '?' . http_build_query($_GET) : ''));
                    exit;
                }
            } catch (Exception $e) {
                debug_log('Error adding pair: ' . $e->getMessage());
                $error_message = 'Could not add the trading pair. Please try again later.';
            }
        }
    }
}

// Fetch trading pairs from DB
$pairs = [];
try {
    $pdo = get_db();
    $pairs = $pdo->query("SELECT * FROM pairs ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    debug_log('Error fetching pairs: ' . $e->getMessage());
    $error_message = $error_message ?? 'An error occurred while retrieving data. Please try again later.';
}

// Get selected date
$selected_date = date('Y-m-d');
if (isset($_GET['date'])) {
    $dt = DateTime::createFromFormat('Y-m-d', $_GET['date']);
    if ($dt && $dt->format('Y-m-d') === $_GET['date']) {
        $selected_date = $_GET['date'];
    } else {
        $error_message = 'Invalid date provided. Showing today\'s data.';
    }
}

// Fetch stats for all pairs
$pair_ids = array_column($pairs, 'id');
$stats = [];
if ($pair_ids) {
    $in = implode(',', array_fill(0, count($pair_ids), '?'));
    try {
        $driver = DB_DSN ? explode(':', DB_DSN, 2)[0] : 'mysql';
        if ($driver === 'sqlite') {
            $sql = "SELECT pair_id, " .
                "SUM(type='positive') as positive, " .
                "SUM(type='negative') as negative " .
                "FROM trades " .
                "WHERE pair_id IN ($in) AND date BETWEEN date(?, '-13 day') AND ? " .
                "GROUP BY pair_id";
        } else {
            $sql = "SELECT pair_id, " .
                "SUM(type='positive') as positive, " .
                "SUM(type='negative') as negative " .
                "FROM trades " .
                "WHERE pair_id IN ($in) AND date BETWEEN DATE_SUB(?, INTERVAL 13 DAY) AND ? " .
                "GROUP BY pair_id";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge($pair_ids, [$selected_date, $selected_date]));
        foreach ($stmt as $row) {
            $stats[$row['pair_id']] = $row;
        }
    } catch (Exception $e) {
        debug_log('Error fetching stats: ' . $e->getMessage());
        $error_message = $error_message ?? 'An error occurred while retrieving data. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trading Pairs Stats</title>
    <style>
        body { font-family: sans-serif; margin: 2em; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 0.5em 1em; border: 1px solid #ccc; }
        button.plus { color: #fff; background: #4caf50; border: none; padding: 0.3em 1em; cursor: pointer; }
        button.minus { color: #fff; background: #f44336; border: none; padding: 0.3em 1em; cursor: pointer; }
        form.inline { display: inline; }
        td.pair-name { cursor: pointer; color: #1a0dab; text-decoration: underline; }
        tr.drawer { display: none; background: #f9f9f9; }
    </style>
</head>
<body>
    <h2>Trading Pairs - Last 14 Days</h2>
    <?php if ($error_message): ?>
        <p style="color:red;"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>
    <form id="dateForm" method="get">
        <label for="date">Select Date:</label>
        <input type="date" name="date" id="date" value="<?= htmlspecialchars($selected_date) ?>" max="<?= date('Y-m-d') ?>">
        <button type="submit">Go</button>
    </form>
    <br>
    <form method="post" class="inline" id="addPairForm" autocomplete="off">
        <input type="text" name="new_pair" id="new_pair" placeholder="Add new trading pair" maxlength="20" required>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <button type="submit">Add Pair</button>
    </form>
    <br><br>
    <table>
        <thead>
            <tr>
                <th>Pair</th>
                <th>Positive Trades</th>
                <th>Negative Trades</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="pairsTable">
            <?php foreach ($pairs as $pair):
                $pid = $pair['id'];
                $pos = $stats[$pid]['positive'] ?? 0;
                $neg = $stats[$pid]['negative'] ?? 0;
            ?>
            <tr data-pair-id="<?= (int)$pid ?>" class="pair-row">
                <td class="pair-name"><?= htmlspecialchars(strtoupper($pair['name'])) ?></td>
                <td class="positive"><?= $pos ?></td>
                <td class="negative"><?= $neg ?></td>
                <td>
                    <button class="plus" data-type="positive">+</button>
                    <button class="minus" data-type="negative">-</button>
                </td>
            </tr>
            <tr class="drawer">
                <td colspan="4" class="trades-cell"></td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    <script src="assets/js/trades.js"></script>
</body>
</html>
