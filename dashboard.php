<?php
$pageTitle = 'Dashboard';

require_once 'db.php';
require_once 'functions.php';

require_login();

$userId = $_SESSION['user_id'];

require_once 'header.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$from = trim($_GET['from'] ?? '');
$to = trim($_GET['to'] ?? '');

$allowedLimits = [5, 10, 20, 50];
$limit = (int)($_GET['limit'] ?? 5);

if (!in_array($limit, $allowedLimits, true)) {
    $limit = 5;
}

$page = (int)($_GET['page'] ?? 1);

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $limit;

$where = ["user_id = :user_id"];
$params = ['user_id' => $userId];

if ($search !== '') {
    $where[] = "note LIKE :search";
    $params['search'] = "%{$search}%";
}

if ($category !== '') {
    $where[] = "category = :category";
    $params['category'] = $category;
}

if ($from !== '') {
    $where[] = "expense_date >= :from_date";
    $params['from_date'] = $from;
}

if ($to !== '') {
    $where[] = "expense_date <= :to_date";
    $params['to_date'] = $to;
}

$whereSql = implode(' AND ', $where);
$currentMonth = date('Y-m');

$categories = get_user_categories($pdo, $userId);
$stats = get_monthly_stats($pdo, $userId, $currentMonth);
$categoryTotals = get_category_totals($pdo, $userId);
$budgets = get_budget_progress($pdo, $userId);
$goals = get_user_goals($pdo, $userId);

$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM expenses
    WHERE {$whereSql}
");
$countStmt->execute($params);
$totalFilteredExpenses = (int)$countStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalFilteredExpenses / $limit));

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

$expenseStmt = $pdo->prepare("
    SELECT *
    FROM expenses
    WHERE {$whereSql}
    ORDER BY expense_date DESC, id DESC
    LIMIT :limit OFFSET :offset
");

foreach ($params as $key => $value) {
    $expenseStmt->bindValue(':' . $key, $value);
}

$expenseStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$expenseStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$expenseStmt->execute();

$expenses = $expenseStmt->fetchAll();

$highestCategory = $categoryTotals[0]['category'] ?? 'N/A';

$chartLabels = [];
$chartValues = [];

foreach ($categoryTotals as $item) {
    $chartLabels[] = $item['category'];
    $chartValues[] = $item['total'];
}

function pagination_url($pageNumber, $limitValue) {
    $query = $_GET;
    $query['page'] = $pageNumber;
    $query['limit'] = $limitValue;

    return 'dashboard.php?' . http_build_query($query);
}
?>

<div class="stats-grid">

    <div class="card">
        <h3>Monthly Spending</h3>
        <div class="value">$<?= number_format($stats['total_spending'], 2) ?></div>
    </div>

    <div class="card">
        <h3>Total Expenses</h3>
        <div class="value"><?= $stats['total_expenses'] ?></div>
    </div>

    <div class="card">
        <h3>Top Category</h3>
        <div class="value"><?= e($highestCategory) ?></div>
    </div>

    <div class="card">
        <h3>Savings Goals</h3>
        <div class="value"><?= count($goals) ?></div>
    </div>

</div>

<div class="dashboard-grid">

    <div class="chart-card">
        <h3>Spending by Category</h3>

        <div class="chart-wrap">
            <canvas id="expenseChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <h3>Budget Warnings</h3>

        <?php if (!$budgets): ?>
            <p class="muted">No budgets added yet.</p>
        <?php endif; ?>

        <?php foreach ($budgets as $budget): ?>

            <?php
            $percent = 0;

            if ($budget['limit_amount'] > 0) {
                $percent = ($budget['spent'] / $budget['limit_amount']) * 100;
            }
            ?>

            <div style="margin-bottom:20px;">

                <div style="display:flex;justify-content:space-between;margin-bottom:8px;gap:12px;">
                    <strong><?= e($budget['category']) ?></strong>

                    <span>
                        $<?= number_format($budget['spent'],2) ?>
                        /
                        $<?= number_format($budget['limit_amount'],2) ?>
                    </span>
                </div>

                <div class="progress">
                    <span style="width:<?= min($percent,100) ?>%"></span>
                </div>

                <?php if ($percent >= 100): ?>
                    <p class="danger-text">Over budget</p>
                <?php elseif ($percent >= 80): ?>
                    <p class="warning">Close to budget limit</p>
                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<div class="table-card">

    <h3>Expense Search & Filters</h3>

    <form method="GET" class="filter-form">

        <div class="form-row">
            <label>Search Note</label>
            <input
                type="text"
                name="search"
                placeholder="Search notes..."
                value="<?= e($search) ?>"
            >
        </div>

        <div class="form-row">
            <label>Category</label>

            <select name="category">
                <option value="">All Categories</option>

                <?php foreach ($categories as $cat): ?>

                    <option
                        value="<?= e($cat['name']) ?>"
                        <?= $category === $cat['name'] ? 'selected' : '' ?>
                    >
                        <?= e($cat['name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>
        </div>

        <div class="form-row">
            <label>From Date</label>

            <input
                type="text"
                name="from"
                class="date-picker"
                value="<?= e($from) ?>"
                placeholder="Select date"
            >
        </div>

        <div class="form-row">
            <label>To Date</label>

            <input
                type="text"
                name="to"
                class="date-picker"
                value="<?= e($to) ?>"
                placeholder="Select date"
            >
        </div>

        <input type="hidden" name="page" value="1">
        <input type="hidden" name="limit" value="<?= e((string)$limit) ?>">

        <div class="filter-actions">
            <button type="submit">
                Filter
            </button>

            <a href="dashboard.php" class="btn secondary">
                Clear
            </a>
        </div>

    </form>

</div>

<div class="table-card">

    <div class="table-header-row">
        <div>
            <h3>Recent Expenses</h3>
            <p class="muted">
                Showing <?= $totalFilteredExpenses === 0 ? 0 : $offset + 1 ?>
                -
                <?= min($offset + $limit, $totalFilteredExpenses) ?>
                of <?= $totalFilteredExpenses ?> expenses
            </p>
        </div>

        <form method="GET" class="limit-form">
            <input type="hidden" name="search" value="<?= e($search) ?>">
            <input type="hidden" name="category" value="<?= e($category) ?>">
            <input type="hidden" name="from" value="<?= e($from) ?>">
            <input type="hidden" name="to" value="<?= e($to) ?>">
            <input type="hidden" name="page" value="1">

            <label>Show</label>

            <select name="limit" onchange="this.form.submit()">
                <?php foreach ($allowedLimits as $option): ?>
                    <option
                        value="<?= $option ?>"
                        <?= $limit === $option ? 'selected' : '' ?>
                    >
                        <?= $option ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (!$expenses): ?>
        <p class="muted">No expenses found.</p>
    <?php else: ?>

        <div class="table-responsive">

            <table>

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Note</th>
                        <th>Amount</th>
                        <th>Receipt</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($expenses as $expense): ?>

                        <tr>

                            <td><?= e($expense['expense_date']) ?></td>

                            <td>
                                <span class="badge">
                                    <?= e($expense['category']) ?>
                                </span>
                            </td>

                            <td><?= e($expense['note']) ?></td>

                            <td>
                                $<?= number_format($expense['amount'], 2) ?>
                            </td>

                            <td>

                                <?php if (!empty($expense['receipt_path'])): ?>

                                    <a
                                        class="receipt-link"
                                        href="<?= e($expense['receipt_path']) ?>"
                                        target="_blank"
                                    >
                                        View
                                    </a>

                                <?php else: ?>

                                    <span class="muted">None</span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <div style="display:flex;gap:10px;flex-wrap:wrap;">

                                    <a
                                        class="btn secondary"
                                        href="edit_expense.php?id=<?= $expense['id'] ?>"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        class="btn danger"
                                        href="delete_expense.php?id=<?= $expense['id'] ?>"
                                        onclick="return confirm('Delete this expense?')"
                                    >
                                        Delete
                                    </a>

                                </div>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

        <div class="pagination">

            <?php if ($page > 1): ?>
                <a class="page-link" href="<?= e(pagination_url($page - 1, $limit)) ?>">
                    Previous
                </a>
            <?php else: ?>
                <span class="page-link disabled">
                    Previous
                </span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a
                    class="page-link <?= $page === $i ? 'active' : '' ?>"
                    href="<?= e(pagination_url($i, $limit)) ?>"
                >
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a class="page-link" href="<?= e(pagination_url($page + 1, $limit)) ?>">
                    Next
                </a>
            <?php else: ?>
                <span class="page-link disabled">
                    Next
                </span>
            <?php endif; ?>

        </div>

    <?php endif; ?>

</div>

<div class="table-card">

    <h3>Goal Progress</h3>

    <?php if (!$goals): ?>
        <p class="muted">No goals added yet.</p>
    <?php endif; ?>

    <?php foreach ($goals as $goal): ?>

        <?php
        $progress = 0;

        if ($goal['target_amount'] > 0) {
            $progress = ($goal['saved_amount'] / $goal['target_amount']) * 100;
        }
        ?>

        <div style="margin-bottom:22px;">

            <div style="display:flex;justify-content:space-between;margin-bottom:8px;gap:12px;flex-wrap:wrap;">
                <strong><?= e($goal['goal_name']) ?></strong>

                <span>
                    $<?= number_format($goal['saved_amount'],2) ?>
                    /
                    $<?= number_format($goal['target_amount'],2) ?>
                </span>
            </div>

            <div class="progress">
                <span style="width: <?= min($progress,100) ?>%"></span>
            </div>

            <p class="muted">
                <?= number_format($progress,1) ?>% completed
            </p>

        </div>

    <?php endforeach; ?>

</div>

<script>
const ctx = document.getElementById('expenseChart');

new Chart(ctx, {
    type: 'doughnut',

    data: {
        labels: <?= json_encode($chartLabels) ?>,

        datasets: [{
            data: <?= json_encode($chartValues) ?>,
            borderWidth: 0
        }]
    },

    options: {
        responsive: true,
        maintainAspectRatio: false,

        plugins: {
            legend: {
                labels: {
                    color: '#ffffff'
                }
            }
        }
    }
});
</script>

<?php require_once 'footer.php'; ?>