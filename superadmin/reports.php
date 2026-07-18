<?php
require_once '../includes/auth.php';
require_role(1);
require_once '../includes/header.php';
require_once '../config/database.php';

$pdo = getDbConnection();

$report_type = $_GET['type'] ?? 'wallet';
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'all';
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$params = [];

if ($filter === 'today') {
    $date_cond = "DATE(PREFIXcreated_at) = CURDATE()";
} elseif ($filter === 'yesterday') {
    $date_cond = "DATE(PREFIXcreated_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} elseif ($filter === '7days') {
    $date_cond = "PREFIXcreated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
} elseif ($filter === '30days') {
    $date_cond = "PREFIXcreated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} else {
    $date_cond = "1=1";
}

$data = [];

if ($report_type === 'wallet') {
    $q = "SELECT u.username, w.balance, w.total_received, w.total_sent FROM wallets w JOIN users u ON w.user_id = u.id";
    $conds = [str_replace('PREFIX', 'u.', $date_cond)];
    if ($search) {
        $conds[] = "u.username LIKE ?";
        $params[] = "%$search%";
    }
    if (!empty($conds)) $q .= " WHERE " . implode(' AND ', $conds);
    $q .= " LIMIT $limit OFFSET $offset";
    $stmt = $pdo->prepare($q);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

} elseif ($report_type === 'bet') {
    $q = "SELECT b.id, b.amount, b.status as result, b.created_at, u.username, m.title
          FROM bets b
          JOIN users u ON b.user_id = u.id
          JOIN matches m ON b.match_id = m.id";

    $conds = [str_replace('PREFIX', 'b.', $date_cond)];
    if ($search) {
        $conds[] = "(u.username LIKE ? OR m.title LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($conds)) $q .= " WHERE " . implode(' AND ', $conds);

    $stmt = $pdo->prepare($q . " ORDER BY b.created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $data = $stmt->fetchAll();
} elseif ($report_type === 'match') {
    $q = "SELECT m.title as Teams, m.status as Status,
          (SELECT COUNT(*) FROM bets WHERE match_id = m.id) as Total_Bets,
          (SELECT SUM(amount) FROM bets WHERE match_id = m.id) as Total_Coins
          FROM matches m";

    $conds = [str_replace('PREFIX', 'm.', $date_cond)];
    if ($search) {
        $conds[] = "m.title LIKE ?";
        $params[] = "%$search%";
    }

    if (!empty($conds)) $q .= " WHERE " . implode(' AND ', $conds);

    $stmt = $pdo->prepare($q . " ORDER BY m.start_time DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $data = $stmt->fetchAll();
}
?>

<div class="container-fluid mt-4 mb-5">
    <h4>System Reports</h4>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Report Type</label>
                    <select name="type" class="form-select">
                        <option value="wallet" <?php echo $report_type=='wallet'?'selected':''; ?>>Wallet Report</option>
                        <option value="bet" <?php echo $report_type=='bet'?'selected':''; ?>>Bet Report</option>
                        <option value="match" <?php echo $report_type=='match'?'selected':''; ?>>Match Report</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date Filter</label>
                    <select name="filter" class="form-select">
                        <option value="all" <?php echo $filter=='all'?'selected':''; ?>>All Time</option>
                        <option value="today" <?php echo $filter=='today'?'selected':''; ?>>Today</option>
                        <option value="yesterday" <?php echo $filter=='yesterday'?'selected':''; ?>>Yesterday</option>
                        <option value="7days" <?php echo $filter=='7days'?'selected':''; ?>>Last 7 Days</option>
                        <option value="30days" <?php echo $filter=='30days'?'selected':''; ?>>Last 30 Days</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Generate</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <?php if ($report_type === 'wallet'): ?>
                            <tr><th>Username</th><th>Balance</th><th>Received</th><th>Sent</th></tr>
                        <?php elseif ($report_type === 'bet'): ?>
                            <tr><th>Date</th><th>Match</th><th>User</th><th>Amount</th><th>Result</th></tr>
                        <?php elseif ($report_type === 'match'): ?>
                            <tr><th>Teams</th><th>Status</th><th>Bets</th><th>Coins</th></tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <?php if ($report_type === 'wallet'): ?>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo number_format($row['balance'], 2); ?></td>
                                    <td><?php echo number_format($row['total_received'], 2); ?></td>
                                    <td><?php echo number_format($row['total_sent'], 2); ?></td>
                                <?php elseif ($report_type === 'bet'): ?>
                                    <td><?php echo date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['result']); ?></td>
                                <?php elseif ($report_type === 'match'): ?>
                                    <td><?php echo htmlspecialchars($row['Teams']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Status']); ?></td>
                                    <td><?php echo $row['Total_Bets']; ?></td>
                                    <td><?php echo number_format($row['Total_Coins'] ?? 0, 2); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($data)): ?>
                            <tr><td colspan="5" class="text-center p-3">No data found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-between">
        <?php if ($page > 1): ?>
            <a href="?type=<?php echo urlencode($report_type); ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>&page=<?php echo $page - 1; ?>" class="btn btn-outline-primary btn-sm">Previous</a>
        <?php else: ?>
            <button disabled class="btn btn-outline-secondary btn-sm">Previous</button>
        <?php endif; ?>

        <?php if (count($data) == $limit): ?>
            <a href="?type=<?php echo urlencode($report_type); ?>&search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>&page=<?php echo $page + 1; ?>" class="btn btn-outline-primary btn-sm">Next</a>
        <?php else: ?>
            <button disabled class="btn btn-outline-secondary btn-sm">Next</button>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
