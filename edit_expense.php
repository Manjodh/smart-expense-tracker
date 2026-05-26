<?php
require_once 'db.php';
require_once 'functions.php';
require_login();
$pageTitle = 'Edit Expense';
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM expenses WHERE id = ? AND user_id = ?');
$stmt->execute([$id, current_user_id()]);
$expense = $stmt->fetch();
if (!$expense) { flash('error', 'Expense not found.'); redirect('dashboard.php'); }
$categories = ['Food','Transport','Rent','Utilities','Shopping','Health','Entertainment','Education','Travel','Other'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = trim($_POST['amount'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $date = trim($_POST['expense_date'] ?? '');
    $receiptPath = $expense['receipt_path'];
    [$newReceipt, $uploadError] = upload_receipt($_FILES['receipt'] ?? null);
    if ($newReceipt) $receiptPath = $newReceipt;

    if (!is_numeric($amount) || $amount <= 0 || $category === '' || $date === '') {
        flash('error', 'Enter a valid amount, category, and date.');
    } elseif ($uploadError) {
        flash('error', $uploadError);
    } else {
        $stmt = $pdo->prepare('UPDATE expenses SET amount=?, category=?, note=?, expense_date=?, receipt_path=? WHERE id=? AND user_id=?');
        $stmt->execute([$amount, $category, $note, $date, $receiptPath, $id, current_user_id()]);
        flash('success', 'Expense updated successfully.');
        redirect('dashboard.php');
    }
}
include 'header.php';
?>
<section class="form-card glass-panel narrow">
    <h2>Edit expense</h2>
    <form method="POST" enctype="multipart/form-data" class="stack-form">
        <label>Amount<input type="number" step="0.01" min="0.01" name="amount" value="<?= e($expense['amount']) ?>" required></label>
        <label>Category<select name="category" required><?php foreach ($categories as $cat): ?><option value="<?= e($cat) ?>" <?= $expense['category']===$cat?'selected':'' ?>><?= e($cat) ?></option><?php endforeach; ?></select></label>
        <label>Expense Date<input type="date" name="expense_date" value="<?= e($expense['expense_date']) ?>" required></label>
        <label>Note<textarea name="note" rows="4"><?= e($expense['note']) ?></textarea></label>
        <?php if ($expense['receipt_path']): ?><p>Current receipt: <a class="table-link" target="_blank" href="<?= e($expense['receipt_path']) ?>">View receipt</a></p><?php endif; ?>
        <label>Replace Receipt<input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf"></label>
        <button class="btn primary full" type="submit">Update Expense</button>
    </form>
</section>
<?php include 'footer.php'; ?>
