<?php
require_once 'db.php';
require_once 'functions.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('DELETE FROM expenses WHERE id = ? AND user_id = ?');
$stmt->execute([$id, current_user_id()]);
flash('success', 'Expense deleted.');
redirect('dashboard.php');
?>
