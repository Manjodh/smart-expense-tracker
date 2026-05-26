<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'Smart Expense Tracker';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
</head>
<body>
<div class="orb orb-one"></div>
<div class="orb orb-two"></div>
<?php if (is_logged_in()): ?>
<div class="app-shell">
    <aside class="sidebar glass-panel">
        <a class="brand" href="dashboard.php"><span>💎</span> SmartSpend</a>
        <nav>
            <a class="<?= active_nav('dashboard.php') ?>" href="dashboard.php">Dashboard</a>
            <a class="<?= active_nav('reports.php') ?>" href="reports.php">Reports</a>
            <a class="<?= active_nav('add_expense.php') ?>" href="add_expense.php">Add Expense</a>
            <a class="<?= active_nav('import_expenses.php') ?>" href="import_expenses.php">Import CSV</a>
            <a class="<?= active_nav('categories.php') ?>" href="categories.php">Categories</a>
            <a class="<?= active_nav('budgets.php') ?>" href="budgets.php">Budgets</a>
            <a class="<?= active_nav('goals.php') ?>" href="goals.php">Savings Goals</a>
            <a href="logout.php">Logout</a>
        </nav>
    </aside>
    <main class="main-content">
        <header class="topbar glass-panel">
            <div>
                <p class="eyebrow">Welcome back</p>
                <h1><?= e($pageTitle) ?></h1>
            </div>
            <div class="user-pill"><?= e($_SESSION['user_name'] ?? 'User') ?></div>
        </header>
<?php else: ?>
<main class="auth-page">
<?php endif; ?>

<?php if ($msg = flash('success')): ?><div class="alert success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash('error')): ?><div class="alert error"><?= e($msg) ?></div><?php endif; ?>