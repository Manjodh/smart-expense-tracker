<?php
require_once 'functions.php';
if (is_logged_in()) redirect('dashboard.php');
$pageTitle = 'Smart Expense Tracker';
include 'header.php';
?>
<section class="hero glass-panel">
    <div class="hero-badge">Modern personal finance control</div>
    <h1>Track spending, protect budgets, and grow savings with clarity.</h1>
    <p>SmartSpend helps you log expenses, upload receipts, monitor category budgets, and visualize monthly spending in one polished dashboard.</p>
    <div class="hero-actions">
        <a class="btn primary" href="register.php">Create Account First</a>
        <a class="btn secondary" href="login.php">Login</a>
    </div>
</section>
<?php include 'footer.php'; ?>
