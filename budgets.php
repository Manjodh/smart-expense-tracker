<?php
$pageTitle = 'Budgets';

require_once 'db.php';
require_once 'functions.php';

require_login();

$userId = $_SESSION['user_id'];

$categoryStmt = $pdo->prepare("
    SELECT name
    FROM categories
    WHERE user_id = ?
    ORDER BY name ASC
");
$categoryStmt->execute([$userId]);
$categories = $categoryStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $limitAmount = trim($_POST['limit_amount'] ?? '');
    $monthYear = trim($_POST['month_year'] ?? '');

    if ($category === '' || $limitAmount === '' || $monthYear === '') {
        flash('error', 'Category, limit amount, and month are required.');
    } elseif (!is_numeric($limitAmount) || $limitAmount <= 0) {
        flash('error', 'Please enter a valid budget amount.');
    } else {
        $checkStmt = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE user_id = ?
            AND name = ?
        ");
        $checkStmt->execute([$userId, $category]);

        if (!$checkStmt->fetch()) {
            flash('error', 'Please select a valid category.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO budgets
                (user_id, category, limit_amount, month_year, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $userId,
                $category,
                $limitAmount,
                $monthYear
            ]);

            flash('success', 'Budget added successfully.');
            redirect('budgets.php');
        }
    }
}

if (isset($_GET['delete'])) {
    $budgetId = (int) $_GET['delete'];

    $stmt = $pdo->prepare("
        DELETE FROM budgets
        WHERE id = ?
        AND user_id = ?
    ");
    $stmt->execute([$budgetId, $userId]);

    flash('success', 'Budget deleted successfully.');
    redirect('budgets.php');
}

$stmt = $pdo->prepare("
    SELECT 
        b.*,
        COALESCE(SUM(e.amount), 0) AS spent
    FROM budgets b
    LEFT JOIN expenses e
        ON e.user_id = b.user_id
        AND e.category = b.category
        AND DATE_FORMAT(e.expense_date, '%Y-%m') = b.month_year
    WHERE b.user_id = ?
    GROUP BY b.id
    ORDER BY b.month_year DESC, b.category ASC
");
$stmt->execute([$userId]);
$budgets = $stmt->fetchAll();

require_once 'header.php';
?>

<div class="table-card form-card">

    <h3>Add Budget</h3>

    <?php if (!$categories): ?>

        <p class="muted">
            You need to create at least one category before adding a budget.
        </p>

        <div class="action-row">
            <a href="categories.php" class="btn">
                Add Category
            </a>
        </div>

    <?php else: ?>

        <form method="POST" class="form-grid">

            <div class="form-row">
                <label>Category</label>

                <select name="category" required>
                    <option value="">Select category</option>

                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['name']) ?>">
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <label>Budget Limit</label>

                <input
                    type="number"
                    step="0.01"
                    name="limit_amount"
                    placeholder="0.00"
                    required
                >
            </div>

            <div class="form-row">
                <label>Month</label>

                <input
                    type="text"
                    name="month_year"
                    class="month-picker"
                    value="<?= date('Y-m') ?>"
                    required
                >
            </div>

            <div class="form-row">
                <label>&nbsp;</label>

                <button type="submit">
                    Save Budget
                </button>
            </div>

        </form>

    <?php endif; ?>

</div>

<div class="table-card">

    <h3>Your Budgets</h3>

    <?php if (!$budgets): ?>

        <p class="muted">No budgets added yet.</p>

    <?php else: ?>

        <div class="table-responsive">

            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Category</th>
                        <th>Limit</th>
                        <th>Spent</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($budgets as $budget): ?>

                        <?php
                        $percent = 0;

                        if ($budget['limit_amount'] > 0) {
                            $percent = ($budget['spent'] / $budget['limit_amount']) * 100;
                        }
                        ?>

                        <tr>
                            <td><?= e($budget['month_year']) ?></td>

                            <td>
                                <span class="badge">
                                    <?= e($budget['category']) ?>
                                </span>
                            </td>

                            <td>$<?= number_format($budget['limit_amount'], 2) ?></td>

                            <td>$<?= number_format($budget['spent'], 2) ?></td>

                            <td>
                                <?php if ($percent >= 100): ?>
                                    <span class="danger-text">Over Budget</span>
                                <?php elseif ($percent >= 80): ?>
                                    <span class="warning">Close to Limit</span>
                                <?php else: ?>
                                    <span class="muted">On Track</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="progress">
                                    <span style="width: <?= min($percent, 100) ?>%"></span>
                                </div>
                            </td>

                            <td>
                                <a
                                    class="btn danger"
                                    href="budgets.php?delete=<?= $budget['id'] ?>"
                                    onclick="return confirm('Delete this budget?')"
                                >
                                    Delete
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    <?php endif; ?>

</div>

<?php require_once 'footer.php'; ?>