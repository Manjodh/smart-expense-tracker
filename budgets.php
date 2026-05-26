<?php
require_once 'db.php';
require_once 'functions.php';
require_login();
$pageTitle = 'Budgets';
$userId = current_user_id();
$categories = ['Food','Transport','Rent','Utilities','Shopping','Health','Entertainment','Education','Travel','Other'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $limit = trim($_POST['limit_amount'] ?? '');
    $month = trim($_POST['month_year'] ?? date('Y-m'));
    if ($category === '' || !is_numeric($limit) || $limit <= 0 || !preg_match('/^\d{4}-\d{2}$/', $month)) {
        flash('error', 'Enter a valid category, limit, and month.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO budgets (user_id, category, limit_amount, month_year) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE limit_amount = VALUES(limit_amount)');
        $stmt->execute([$userId, $category, $limit, $month]);
        flash('success', 'Budget saved.');
        redirect('budgets.php');
    }
}
$stmt = $pdo->prepare("SELECT b.*, COALESCE(SUM(e.amount),0) spent FROM budgets b LEFT JOIN expenses e ON e.user_id=b.user_id AND e.category=b.category AND DATE_FORMAT(e.expense_date,'%Y-%m')=b.month_year WHERE b.user_id=? GROUP BY b.id ORDER BY b.month_year DESC, b.category");
$stmt->execute([$userId]);
$budgets = $stmt->fetchAll();
include 'header.php';
?>
<section class="dashboard-grid">
    <div class="form-card glass-panel">
        <h2>Add or update budget</h2>
        <form method="POST" class="stack-form">
            <label>Category<select name="category" required><?php foreach ($categories as $cat): ?><option value="<?= e($cat) ?>"><?= e($cat) ?></option><?php endforeach; ?></select></label>
            <label>Monthly Limit<input type="number" step="0.01" min="0.01" name="limit_amount" required></label>
            <label>Month<input type="month" name="month_year" value="<?= e(date('Y-m')) ?>" required></label>
            <button class="btn primary full" type="submit">Save Budget</button>
        </form>
    </div>
    <div class="glass-panel">
        <h2>Budget status</h2>
        <?php foreach ($budgets as $b): $pct = $b['limit_amount'] > 0 ? ($b['spent'] / $b['limit_amount']) * 100 : 0; ?>
            <div class="budget-card">
                <div class="mini-row"><strong><?= e($b['category']) ?> · <?= e($b['month_year']) ?></strong><span><?= round($pct) ?>%</span></div>
                <p><?= money($b['spent']) ?> spent of <?= money($b['limit_amount']) ?></p>
                <div class="progress"><span style="width: <?= min(100, $pct) ?>%"></span></div>
                <?php if ($pct >= 100): ?><p class="warning">You are over this budget.</p><?php elseif ($pct >= 80): ?><p class="warning soft">You are close to this budget.</p><?php else: ?><p class="muted">Healthy budget usage.</p><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php if (!$budgets): ?><p class="muted">No budgets yet.</p><?php endif; ?>
    </div>
</section>
<?php include 'footer.php'; ?>
