<?php
require_once 'db.php';
require_once 'functions.php';
if (is_logged_in()) redirect('dashboard.php');

$name = $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        flash('error', 'All fields are required.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'Please enter a valid email address.');
    } elseif (strlen($password) < 6) {
        flash('error', 'Password must be at least 6 characters.');
    } elseif ($password !== $confirm) {
        flash('error', 'Passwords do not match.');
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            flash('error', 'Email is already registered.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$name, $email, $hash]);
            flash('success', 'Account created. Please login.');
            redirect('login.php');
        }
    }
}
$pageTitle = 'Create Account';
include 'header.php';
?>
<div class="auth-card glass-panel">
    <a class="brand center" href="index.php">💎 SmartSpend</a>
    <h2>Create your account</h2>
    <p>Start tracking expenses with a clean SaaS-style dashboard.</p>
    <form method="POST" class="stack-form">
        <label>Name<input type="text" name="name" value="<?= e($name) ?>" required></label>
        <label>Email<input type="email" name="email" value="<?= e($email) ?>" required></label>
        <label>Password<input type="password" name="password" required></label>
        <label>Confirm Password<input type="password" name="confirm_password" required></label>
        <button class="btn primary full" type="submit">Register</button>
    </form>
    <p class="switch-link">Already have an account? <a href="login.php">Login</a></p>
</div>
<?php include 'footer.php'; ?>
