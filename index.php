<?php
require_once 'db.php';

$error_message = null;

// Handle add new pair form submission (POST with normal form, not AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_pair'])) {
    $new_pair = trim($_POST['new_pair']);
    if ($new_pair !== '') {
        try {
            $pdo = get_db();
            $stmt = $pdo->prepare("INSERT IGNORE INTO pairs (pair) VALUES (?)");
            $stmt->execute([$new_pair]);
            // Redirect to avoid resubmission only on success
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query($_GET));
            exit;
        } catch (Exception $e) {
            debug_log('Error adding pair: ' . $e->getMessage());
            $error_message = 'Could not add the trading pair. Please try again later.';
        }
    }
}

// Fetch trading pairs from DB
$pairs = [];
try {
    $pdo = get_db();
    $pairs = $pdo->query("SELECT * FROM pairs ORDER BY pair ASC")->fetchAll();
} catch (Exception $e) {
    debug_log('Error fetching pairs: ' . $e->getMessage());
    $error_message = $error_message ?? 'An error occurred while retrieving data. Please try again later.';
}

// Get selected date
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Fetch stats for all pairs
$pair_ids = array_column($pairs, 'id');
$stats = [];
if ($pair_ids) {
    $in = implode(',', array_fill(0, count($pair_ids), '?'));
    try {
        $stmt = $pdo->prepare("SELECT pair_id, \
        SUM(type='positive') as positive, \
        SUM(type='negative') as negative \
        FROM trades \
        WHERE pair_id IN ($in) AND date BETWEEN DATE_SUB(?, INTERVAL 13 DAY) AND ? \
        GROUP BY pair_id");
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
            <tr data-pair-id="<?= (int)$pid ?>">
                <td><?= htmlspecialchars($pair['pair']) ?></td>
                <td class="positive"><?= $pos ?></td>
                <td class="negative"><?= $neg ?></td>
                <td>
                    <button class="plus" data-type="positive">+</button>
                    <button class="minus" data-type="negative">-</button>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    <script>
        document.querySelectorAll('button.plus, button.minus').forEach(function(btn){
            btn.addEventListener('click', function(e){
                e.preventDefault();
                let tr = btn.closest('tr');
                let pair_id = tr.getAttribute('data-pair-id');
                let type = btn.getAttribute('data-type');
                let date = document.getElementById('date').value;
                fetch('trades.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'add', pair_id, type, date })
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        tr.querySelector('.' + type).textContent = data.count;
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            });
        });
    </script>
</body>
</html>
