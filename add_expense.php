<?php
require_once 'db.php';
require_once 'functions.php';
require_login();
$pageTitle = 'Add Expense';
$categories = ['Food','Transport','Rent','Utilities','Shopping','Health','Entertainment','Education','Travel','Other'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = trim($_POST['amount'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $date = trim($_POST['expense_date'] ?? '');
    [$receiptPath, $uploadError] = upload_receipt($_FILES['receipt'] ?? null);

    if (!is_numeric($amount) || $amount <= 0 || $category === '' || $date === '') {
        flash('error', 'Enter a valid amount, category, and date.');
    } elseif ($uploadError) {
        flash('error', $uploadError);
    } else {
        $stmt = $pdo->prepare('INSERT INTO expenses (user_id, amount, category, note, expense_date, receipt_path) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([current_user_id(), $amount, $category, $note, $date, $receiptPath]);
        flash('success', 'Expense added successfully.');
        redirect('dashboard.php');
    }
}
include 'header.php';
?>
<section class="form-card glass-panel narrow">
    <h2>Add a new expense</h2>
    <form method="POST" enctype="multipart/form-data" class="stack-form">
        <label>Amount<input type="number" step="0.01" min="0.01" name="amount" required></label>
        <label>Category<select name="category" required><?php foreach ($categories as $cat): ?><option value="<?= e($cat) ?>"><?= e($cat) ?></option><?php endforeach; ?></select></label>
        <label>Expense Date<input type="date" name="expense_date" value="<?= e(date('Y-m-d')) ?>" required></label>
        <label>Note<textarea name="note" rows="4" placeholder="Optional details"></textarea></label>
        <label>Receipt JPG, PNG, or PDF<input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf"></label>
        <button class="btn primary full" type="submit">Save Expense</button>
    </form>
</section>
<?php include 'footer.php'; ?>
