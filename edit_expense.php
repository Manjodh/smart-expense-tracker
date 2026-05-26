<?php
$pageTitle = 'Edit Expense';

require_once 'db.php';
require_once 'functions.php';

require_login();

$userId = $_SESSION['user_id'];
$expenseId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM expenses
    WHERE id = ?
    AND user_id = ?
");
$stmt->execute([$expenseId, $userId]);
$expense = $stmt->fetch();

if (!$expense) {
    flash('error', 'Expense not found.');
    redirect('dashboard.php');
}

$stmt = $pdo->prepare("
    SELECT name
    FROM categories
    WHERE user_id = ?
    ORDER BY name ASC
");
$stmt->execute([$userId]);
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = trim($_POST['amount'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $expenseDate = trim($_POST['expense_date'] ?? '');

    if ($amount === '' || $category === '' || $expenseDate === '') {
        flash('error', 'Amount, category, and date are required.');
    } elseif (!is_numeric($amount) || $amount <= 0) {
        flash('error', 'Please enter a valid amount.');
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

            $receiptPath = $expense['receipt_path'];

            if (!empty($_FILES['receipt']['name'])) {
                [$newReceiptPath, $uploadError] = upload_receipt($_FILES['receipt']);

                if ($uploadError) {
                    flash('error', $uploadError);
                        redirect('edit_expense.php?id=' . $expenseId);
            }   

            $receiptPath = $newReceiptPath;
            }

            $stmt = $pdo->prepare("
                UPDATE expenses
                SET amount = ?,
                    category = ?,
                    note = ?,
                    expense_date = ?,
                    receipt_path = ?
                WHERE id = ?
                AND user_id = ?
            ");

            $stmt->execute([
                $amount,
                $category,
                $note,
                $expenseDate,
                $receiptPath,
                $expenseId,
                $userId
            ]);

            flash('success', 'Expense updated successfully.');
            redirect('dashboard.php');
        }
    }
}

require_once 'header.php';
?>

<div class="table-card form-card">

    <h3>Edit Expense</h3>

    <?php if (!$categories): ?>

        <p class="muted">
            You need to create at least one category before editing this expense.
        </p>

        <div class="action-row">
            <a href="categories.php" class="btn">
                Add Category
            </a>
        </div>

    <?php else: ?>

        <form method="POST" enctype="multipart/form-data" class="form-grid">

            <div class="form-row">
                <label>Amount</label>

                <input
                    type="number"
                    step="0.01"
                    name="amount"
                    value="<?= e($expense['amount']) ?>"
                    required
                >
            </div>

            <div class="form-row">
                <label>Category</label>

                <select name="category" required>
                    <option value="">Select category</option>

                    <?php foreach ($categories as $cat): ?>
                        <option
                            value="<?= e($cat['name']) ?>"
                            <?= $expense['category'] === $cat['name'] ? 'selected' : '' ?>
                        >
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-row">
                <label>Expense Date</label>

                <input
                    type="text"
                    name="expense_date"
                    class="date-picker"
                    value="<?= e($expense['expense_date']) ?>"
                    required
                >
            </div>

            <div class="form-row">
                <label>Replace Receipt</label>

                <input
                    type="file"
                    name="receipt"
                    accept=".jpg,.jpeg,.png,.pdf"
                >
            </div>

            <?php if (!empty($expense['receipt_path'])): ?>
                <div class="form-row full">
                    <label>Current Receipt</label>

                    <a
                        class="receipt-link"
                        href="<?= e($expense['receipt_path']) ?>"
                        target="_blank"
                    >
                        View current receipt
                    </a>
                </div>
            <?php endif; ?>

            <div class="form-row full">
                <label>Note</label>

                <textarea
                    name="note"
                    rows="4"
                ><?= e($expense['note']) ?></textarea>
            </div>

            <div class="form-row full">
                <button type="submit">
                    Update Expense
                </button>
            </div>

        </form>

    <?php endif; ?>

</div>

<?php require_once 'footer.php'; ?>