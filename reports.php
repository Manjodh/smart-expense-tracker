<?php
$pageTitle = 'Monthly Reports';

require_once 'db.php';
require_once 'functions.php';

require_login();

$userId = current_user_id();
$selectedMonth = trim($_GET['month'] ?? date('Y-m'));

if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}

$stats = get_monthly_stats($pdo, $userId, $selectedMonth);
$categoryTotals = get_category_totals($pdo, $userId, $selectedMonth);

$totalSpending = (float)($stats['total_spending'] ?? 0);
$totalExpenses = (int)($stats['total_expenses'] ?? 0);
$highestCategory = $categoryTotals[0]['category'] ?? 'N/A';
$highestAmount = $categoryTotals[0]['total'] ?? 0;
$averageExpense = $totalExpenses > 0 ? $totalSpending / $totalExpenses : 0;

$chartLabels = [];
$chartValues = [];

foreach ($categoryTotals as $item) {
    $chartLabels[] = $item['category'];
    $chartValues[] = (float)$item['total'];
}

require_once 'header.php';
?>

<div class="table-card">

    <div class="table-header-row">
        <div>
            <h3>Monthly Report</h3>
            <p class="muted">View spending insights for a selected month.</p>
        </div>

        <form method="GET" class="report-filter">
            <input
                type="text"
                name="month"
                class="month-picker"
                value="<?= e($selectedMonth) ?>"
                required
            >

            <button type="submit">View Report</button>
        </form>
    </div>

</div>

<div class="stats-grid">

    <div class="card">
        <h3>Total Spending</h3>
        <div class="value"><?= money($totalSpending) ?></div>
    </div>

    <div class="card">
        <h3>Total Transactions</h3>
        <div class="value"><?= $totalExpenses ?></div>
    </div>

    <div class="card">
        <h3>Top Category</h3>
        <div class="value"><?= e($highestCategory) ?></div>
    </div>

    <div class="card">
        <h3>Average Expense</h3>
        <div class="value"><?= money($averageExpense) ?></div>
    </div>

</div>

<div class="dashboard-grid">

    <div class="chart-card">
        <h3>Category Breakdown</h3>

        <?php if (!$categoryTotals): ?>
            <p class="muted">No expenses found for this month.</p>
        <?php else: ?>
            <div class="chart-wrap">
                <canvas id="reportDoughnutChart"></canvas>
            </div>
        <?php endif; ?>
    </div>

    <div class="chart-card">
        <h3>Top Spending Categories</h3>

        <?php if (!$categoryTotals): ?>
            <p class="muted">No chart data available.</p>
        <?php else: ?>
            <div class="chart-wrap">
                <canvas id="reportBarChart"></canvas>
            </div>
        <?php endif; ?>
    </div>

</div>

<div class="table-card">

    <h3>Category Summary</h3>

    <?php if (!$categoryTotals): ?>

        <p class="muted">No category spending found for this month.</p>

    <?php else: ?>

        <div class="table-responsive">

            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total Spent</th>
                        <th>Percentage</th>
                        <th>Progress</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($categoryTotals as $item): ?>

                        <?php
                        $categoryTotal = (float)$item['total'];
                        $percentage = $totalSpending > 0
                            ? ($categoryTotal / $totalSpending) * 100
                            : 0;
                        ?>

                        <tr>
                            <td>
                                <span class="badge">
                                    <?= e($item['category']) ?>
                                </span>
                            </td>

                            <td><?= money($categoryTotal) ?></td>

                            <td><?= number_format($percentage, 1) ?>%</td>

                            <td>
                                <div class="progress">
                                    <span style="width: <?= min($percentage, 100) ?>%"></span>
                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    <?php endif; ?>

</div>

<script>
const reportLabels = <?= json_encode($chartLabels) ?>;
const reportValues = <?= json_encode($chartValues) ?>;

if (document.getElementById('reportDoughnutChart')) {
    new Chart(document.getElementById('reportDoughnutChart'), {
        type: 'doughnut',

        data: {
            labels: reportLabels,

            datasets: [{
                data: reportValues,
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
}

if (document.getElementById('reportBarChart')) {
    new Chart(document.getElementById('reportBarChart'), {
        type: 'bar',

        data: {
            labels: reportLabels,

            datasets: [{
                label: 'Amount Spent',
                data: reportValues,
                borderWidth: 0
            }]
        },

        options: {
            responsive: true,
            maintainAspectRatio: false,

            scales: {
                x: {
                    ticks: {
                        color: '#ffffff'
                    },
                    grid: {
                        color: 'rgba(255,255,255,0.08)'
                    }
                },

                y: {
                    ticks: {
                        color: '#ffffff'
                    },
                    grid: {
                        color: 'rgba(255,255,255,0.08)'
                    }
                }
            },

            plugins: {
                legend: {
                    labels: {
                        color: '#ffffff'
                    }
                }
            }
        }
    });
}
</script>

<?php require_once 'footer.php'; ?>