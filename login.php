<?php
require_once 'db.php';
require_once 'functions.php';
if (is_logged_in()) redirect('dashboard.php');
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Enter a valid email address.');
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            redirect('dashboard.php');
        } else {
            flash('error', 'Invalid email or password.');
        }
    }
}
$pageTitle = 'Login';
include 'header.php';
?>
<div class="auth-card glass-panel">
    <a class="brand center" href="index.php">💎 SmartSpend</a>
    <h2>Welcome back</h2>
    <p>Login to view budgets, receipts, goals, and charts.</p>
    <form method="POST" class="stack-form">
        <label>Email<input type="email" name="email" value="<?= e($email) ?>" required></label>
        <label>Password<input type="password" name="password" required></label>
        <button class="btn primary full" type="submit">Login</button>
    </form>
    <p class="switch-link">New here? <a href="register.php">Create account</a></p>
</div>
<?php include 'footer.php'; ?>
