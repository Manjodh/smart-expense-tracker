<?php
require_once 'db.php';
require_once 'functions.php';
require_login();
$pageTitle = 'Savings Goals';
$userId = current_user_id();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['goal_name'] ?? '');
    $target = trim($_POST['target_amount'] ?? '');
    $saved = trim($_POST['saved_amount'] ?? '0');
    if ($name === '' || !is_numeric($target) || $target <= 0 || !is_numeric($saved) || $saved < 0) {
        flash('error', 'Enter a valid goal name, target, and saved amount.');
    } else {
        $stmt = $pdo->prepare('INSERT INTO goals (user_id, goal_name, target_amount, saved_amount) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $name, $target, $saved]);
        flash('success', 'Goal added.');
        redirect('goals.php');
    }
}
$stmt = $pdo->prepare('SELECT * FROM goals WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$goals = $stmt->fetchAll();
include 'header.php';
?>
<section class="dashboard-grid">
    <div class="form-card glass-panel">
        <h2>Add savings goal</h2>
        <form method="POST" class="stack-form">
            <label>Goal Name<input type="text" name="goal_name" placeholder="Emergency fund" required></label>
            <label>Target Amount<input type="number" step="0.01" min="0.01" name="target_amount" required></label>
            <label>Saved Amount<input type="number" step="0.01" min="0" name="saved_amount" value="0" required></label>
            <button class="btn primary full" type="submit">Add Goal</button>
        </form>
    </div>
    <div class="glass-panel">
        <h2>Your progress</h2>
        <?php foreach ($goals as $goal): $pct = $goal['target_amount'] > 0 ? ($goal['saved_amount'] / $goal['target_amount']) * 100 : 0; ?>
            <div class="budget-card">
                <div class="mini-row"><strong><?= e($goal['goal_name']) ?></strong><span><?= round($pct) ?>%</span></div>
                <p><?= money($goal['saved_amount']) ?> saved of <?= money($goal['target_amount']) ?></p>
                <div class="progress"><span style="width: <?= min(100, $pct) ?>%"></span></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$goals): ?><p class="muted">No goals yet.</p><?php endif; ?>
    </div>
</section>
<?php include 'footer.php'; ?>
