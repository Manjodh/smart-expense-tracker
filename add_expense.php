<?php
$pageTitle = 'Add Expense';

require_once 'db.php';
require_once 'functions.php';

require_login();

$userId = $_SESSION['user_id'];

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

            $receiptPath = upload_receipt($_FILES['receipt'] ?? null);

            if ($receiptPath === false) {
                flash('error', 'Invalid receipt file. Only JPG, JPEG, PNG, and PDF files are allowed.');
            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO expenses
                    (user_id, amount, category, note, expense_date, receipt_path, created_at)
                    VALUES
                    (?, ?, ?, ?, ?, ?, NOW())
                ");

                $stmt->execute([
                    $userId,
                    $amount,
                    $category,
                    $note,
                    $expenseDate,
                    $receiptPath
                ]);

                flash('success', 'Expense added successfully.');
                redirect('dashboard.php');
            }
        }
    }
}

require_once 'header.php';
?>

<div class="table-card form-card">

    <h3>Add New Expense</h3>

    <?php if (!$categories): ?>

        <p class="muted">
            You need to create at least one category before adding an expense.
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
                <input type="number" step="0.01" name="amount" placeholder="0.00" required>
            </div>

            <div class="form-row">
                <label>Category</label>

                <select name="category" required>
                    <option value="">Select category</option>

                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['name']) ?>">
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
                    value="<?= date('Y-m-d') ?>"
                    required
                >
            </div>

            <div class="form-row">
                <label>Receipt</label>

                <input
                    type="file"
                    name="receipt"
                    accept=".jpg,.jpeg,.png,.pdf"
                >
            </div>

            <div class="form-row full">
                <label>Note</label>

                <textarea
                    name="note"
                    rows="4"
                    placeholder="Optional note..."
                ></textarea>
            </div>

            <div class="form-row full">
                <button type="submit">
                    Save Expense
                </button>
            </div>

        </form>

    <?php endif; ?>

</div>

<?php require_once 'footer.php'; ?>