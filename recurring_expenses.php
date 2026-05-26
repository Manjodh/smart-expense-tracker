<?php
$pageTitle = 'Recurring Expenses';

require_once 'db.php';
require_once 'functions.php';

require_login();

$userId = current_user_id();
$categories = get_user_categories($pdo, $userId);

function get_next_month_date($dateValue) {
    $date = new DateTime($dateValue);
    $date->modify('+1 month');

    return $date->format('Y-m-d');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_recurring'])) {
    $amount = trim($_POST['amount'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $startDate = trim($_POST['start_date'] ?? '');

    if ($amount === '' || $category === '' || $startDate === '') {
        flash('error', 'Amount, category, and start date are required.');
        redirect('recurring_expenses.php');
    }

    if (!is_numeric($amount) || $amount <= 0) {
        flash('error', 'Please enter a valid amount.');
        redirect('recurring_expenses.php');
    }

    $checkStmt = $pdo->prepare("
        SELECT id
        FROM categories
        WHERE user_id = ?
        AND name = ?
    ");
    $checkStmt->execute([$userId, $category]);

    if (!$checkStmt->fetch()) {
        flash('error', 'Please select a valid category.');
        redirect('recurring_expenses.php');
    }

    $nextDueDate = $startDate;

    $stmt = $pdo->prepare("
        INSERT INTO recurring_expenses
        (user_id, amount, category, note, frequency, start_date, next_due_date, is_active, created_at)
        VALUES
        (?, ?, ?, ?, 'monthly', ?, ?, 1, NOW())
    ");

    $stmt->execute([
        $userId,
        $amount,
        $category,
        $note,
        $startDate,
        $nextDueDate
    ]);

    flash('success', 'Recurring expense added successfully.');
    redirect('recurring_expenses.php');
}

if (isset($_GET['generate'])) {
    $recurringId = (int)$_GET['generate'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM recurring_expenses
        WHERE id = ?
        AND user_id = ?
        AND is_active = 1
    ");
    $stmt->execute([$recurringId, $userId]);
    $recurring = $stmt->fetch();

    if (!$recurring) {
        flash('error', 'Recurring expense not found.');
        redirect('recurring_expenses.php');
    }

    $insertStmt = $pdo->prepare("
        INSERT INTO expenses
        (user_id, amount, category, note, expense_date, receipt_path, created_at)
        VALUES
        (?, ?, ?, ?, ?, NULL, NOW())
    ");

    $insertStmt->execute([
        $userId,
        $recurring['amount'],
        $recurring['category'],
        $recurring['note'],
        $recurring['next_due_date']
    ]);

    $newNextDueDate = get_next_month_date($recurring['next_due_date']);

    $updateStmt = $pdo->prepare("
        UPDATE recurring_expenses
        SET next_due_date = ?
        WHERE id = ?
        AND user_id = ?
    ");
    $updateStmt->execute([
        $newNextDueDate,
        $recurringId,
        $userId
    ]);

    flash('success', 'Recurring expense generated successfully.');
    redirect('recurring_expenses.php');
}

if (isset($_GET['delete'])) {
    $recurringId = (int)$_GET['delete'];

    $stmt = $pdo->prepare("
        DELETE FROM recurring_expenses
        WHERE id = ?
        AND user_id = ?
    ");
    $stmt->execute([$recurringId, $userId]);

    flash('success', 'Recurring expense deleted successfully.');
    redirect('recurring_expenses.php');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM recurring_expenses
    WHERE user_id = ?
    ORDER BY next_due_date ASC, id DESC
");
$stmt->execute([$userId]);
$recurringExpenses = $stmt->fetchAll();

require_once 'header.php';
?>

<div class="table-card form-card">

    <h3>Add Recurring Expense</h3>

    <?php if (!$categories): ?>

        <p class="muted">
            You need to create at least one category before adding recurring expenses.
        </p>

        <div class="action-row">
            <a href="categories.php" class="btn">
                Add Category
            </a>
        </div>

    <?php else: ?>

        <form method="POST" class="form-grid">

            <div class="form-row">
                <label>Amount</label>

                <input
                    type="number"
                    step="0.01"
                    name="amount"
                    placeholder="0.00"
                    required
                >
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
                <label>Start Date</label>

                <input
                    type="text"
                    name="start_date"
                    class="date-picker"
                    value="<?= date('Y-m-d') ?>"
                    required
                >
            </div>

            <div class="form-row">
                <label>Frequency</label>

                <input
                    type="text"
                    value="Monthly"
                    disabled
                >
            </div>

            <div class="form-row full">
                <label>Note</label>

                <textarea
                    name="note"
                    rows="4"
                    placeholder="Example: Netflix subscription, Rent, Phone bill..."
                ></textarea>
            </div>

            <div class="form-row full">
                <button type="submit" name="add_recurring" value="1">
                    Save Recurring Expense
                </button>
            </div>

        </form>

    <?php endif; ?>

</div>

<div class="table-card">

    <h3>Your Recurring Expenses</h3>

    <?php if (!$recurringExpenses): ?>

        <p class="muted">No recurring expenses added yet.</p>

    <?php else: ?>

        <div class="table-responsive">

            <table>
                <thead>
                    <tr>
                        <th>Next Due</th>
                        <th>Category</th>
                        <th>Note</th>
                        <th>Amount</th>
                        <th>Frequency</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($recurringExpenses as $item): ?>

                        <?php
                        $isDue = $item['next_due_date'] <= date('Y-m-d');
                        ?>

                        <tr>
                            <td><?= e($item['next_due_date']) ?></td>

                            <td>
                                <span class="badge">
                                    <?= e($item['category']) ?>
                                </span>
                            </td>

                            <td><?= e($item['note']) ?></td>

                            <td><?= money($item['amount']) ?></td>

                            <td><?= e(ucfirst($item['frequency'])) ?></td>

                            <td>
                                <?php if ($isDue): ?>
                                    <span class="warning">Due Now</span>
                                <?php else: ?>
                                    <span class="muted">Upcoming</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div style="display:flex;gap:10px;flex-wrap:wrap;">

                                    <?php if ($isDue): ?>
                                        <a
                                            class="btn"
                                            href="recurring_expenses.php?generate=<?= $item['id'] ?>"
                                            onclick="return confirm('Generate this recurring expense now?')"
                                        >
                                            Generate
                                        </a>
                                    <?php endif; ?>

                                    <a
                                        class="btn danger"
                                        href="recurring_expenses.php?delete=<?= $item['id'] ?>"
                                        onclick="return confirm('Delete this recurring expense?')"
                                    >
                                        Delete
                                    </a>

                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    <?php endif; ?>

</div>

<?php require_once 'footer.php'; ?>