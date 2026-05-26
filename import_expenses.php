<?php
$pageTitle = 'Import Expenses';

require_once 'db.php';
require_once 'functions.php';

require_login();

$userId = current_user_id();
$categories = get_user_categories($pdo, $userId);

$previewRows = [];

function parse_commbank_date($dateValue) {
    $date = DateTime::createFromFormat('d/m/Y', trim($dateValue));

    if (!$date) {
        return null;
    }

    return $date->format('Y-m-d');
}

function parse_commbank_csv($csvContent) {
    $rows = [];
    $handle = fopen('php://temp', 'r+');

    fwrite($handle, $csvContent);
    rewind($handle);

    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) < 4) {
            continue;
        }

        $date = parse_commbank_date($data[0]);
        $amount = (float)$data[1];
        $description = trim($data[2]);

        if (!$date) {
            continue;
        }

        if ($amount >= 0) {
            continue;
        }

        $rows[] = [
            'expense_date' => $date,
            'amount' => abs($amount),
            'note' => $description,
        ];
    }

    fclose($handle);

    return $rows;
}

function imported_expense_exists($pdo, $userId, $row, $category) {
    $stmt = $pdo->prepare("
        SELECT id
        FROM expenses
        WHERE user_id = ?
        AND expense_date = ?
        AND amount = ?
        AND category = ?
        AND note = ?
        LIMIT 1
    ");

    $stmt->execute([
        $userId,
        $row['expense_date'],
        $row['amount'],
        $category,
        $row['note']
    ]);

    return (bool)$stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview'])) {
    $category = trim($_POST['category'] ?? '');

    if ($category === '') {
        flash('error', 'Please select a category for imported expenses.');
        redirect('import_expenses.php');
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
        redirect('import_expenses.php');
    }

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        flash('error', 'Please upload a valid CSV file.');
        redirect('import_expenses.php');
    }

    $extension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

    if ($extension !== 'csv') {
        flash('error', 'Only CSV files are allowed.');
        redirect('import_expenses.php');
    }

    $csvContent = file_get_contents($_FILES['csv_file']['tmp_name']);
    $rows = parse_commbank_csv($csvContent);

    if (!$rows) {
        flash('error', 'No expense rows were found. Income rows are skipped.');
        redirect('import_expenses.php');
    }

    foreach ($rows as $row) {
        $row['is_duplicate'] = imported_expense_exists($pdo, $userId, $row, $category);
        $previewRows[] = $row;
    }

    $_SESSION['csv_import_rows'] = $previewRows;
    $_SESSION['csv_import_category'] = $category;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_import'])) {
    $rows = $_SESSION['csv_import_rows'] ?? [];
    $category = $_SESSION['csv_import_category'] ?? '';

    if (!$rows || $category === '') {
        flash('error', 'Import session expired. Please upload the CSV again.');
        redirect('import_expenses.php');
    }

    $checkStmt = $pdo->prepare("
        SELECT id
        FROM categories
        WHERE user_id = ?
        AND name = ?
    ");
    $checkStmt->execute([$userId, $category]);

    if (!$checkStmt->fetch()) {
        flash('error', 'Selected category no longer exists.');
        redirect('import_expenses.php');
    }

    $stmt = $pdo->prepare("
        INSERT INTO expenses
        (user_id, amount, category, note, expense_date, receipt_path, created_at)
        VALUES
        (?, ?, ?, ?, ?, NULL, NOW())
    ");

    $imported = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        if (!empty($row['is_duplicate'])) {
            $skipped++;
            continue;
        }

        if (imported_expense_exists($pdo, $userId, $row, $category)) {
            $skipped++;
            continue;
        }

        $stmt->execute([
            $userId,
            $row['amount'],
            $category,
            $row['note'],
            $row['expense_date']
        ]);

        $imported++;
    }

    unset($_SESSION['csv_import_rows'], $_SESSION['csv_import_category']);

    flash(
        'success',
        $imported . ' expenses imported. ' . $skipped . ' duplicates skipped.'
    );

    redirect('dashboard.php');
}

require_once 'header.php';
?>

<div class="table-card form-card">

    <h3>Import CommBank CSV</h3>

    <p class="muted">
        Upload your CommBank CSV statement. Positive income rows will be skipped.
        Duplicate expenses will be detected before import.
    </p>

    <?php if (!$categories): ?>

        <p class="muted">
            You need to create at least one category before importing expenses.
        </p>

        <div class="action-row">
            <a href="categories.php" class="btn">
                Add Category
            </a>
        </div>

    <?php else: ?>

        <form method="POST" enctype="multipart/form-data" class="form-grid">

            <div class="form-row">
                <label>Default Category</label>

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
                <label>CSV File</label>

                <input
                    type="file"
                    name="csv_file"
                    accept=".csv"
                    required
                >
            </div>

            <div class="form-row full">
                <button type="submit" name="preview" value="1">
                    Preview Import
                </button>
            </div>

        </form>

    <?php endif; ?>

</div>

<?php if ($previewRows): ?>

    <?php
    $newCount = 0;
    $duplicateCount = 0;

    foreach ($previewRows as $row) {
        if (!empty($row['is_duplicate'])) {
            $duplicateCount++;
        } else {
            $newCount++;
        }
    }
    ?>

    <div class="table-card">

        <div class="table-header-row">
            <div>
                <h3>Preview Import</h3>

                <p class="muted">
                    <?= count($previewRows) ?> rows found.
                    <?= $newCount ?> new,
                    <?= $duplicateCount ?> duplicate.
                </p>
            </div>

            <?php if ($newCount > 0): ?>
                <form method="POST">
                    <button type="submit" name="confirm_import" value="1">
                        Import <?= $newCount ?> New Expenses
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="table-responsive">

            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($previewRows as $row): ?>

                        <tr>
                            <td>
                                <?php if (!empty($row['is_duplicate'])): ?>
                                    <span class="warning">Duplicate</span>
                                <?php else: ?>
                                    <span class="badge">New</span>
                                <?php endif; ?>
                            </td>

                            <td><?= e($row['expense_date']) ?></td>

                            <td>
                                <span class="badge">
                                    <?= e($_SESSION['csv_import_category']) ?>
                                </span>
                            </td>

                            <td><?= e($row['note']) ?></td>

                            <td><?= money($row['amount']) ?></td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

        <?php if ($newCount === 0): ?>
            <p class="muted" style="margin-top: 18px;">
                All rows are duplicates. Nothing new to import.
            </p>
        <?php endif; ?>

    </div>

<?php endif; ?>

<?php require_once 'footer.php'; ?>