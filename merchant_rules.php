<?php
$pageTitle = 'Merchant Rules';

require_once 'db.php';
require_once 'functions.php';

require_login();

$userId = current_user_id();
$categories = get_user_categories($pdo, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keyword = trim($_POST['keyword'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if ($keyword === '' || $category === '') {
        flash('error', 'Keyword and category are required.');
        redirect('merchant_rules.php');
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
        redirect('merchant_rules.php');
    }

    $stmt = $pdo->prepare("
        INSERT INTO merchant_rules
        (user_id, keyword, category, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $keyword, $category]);

    flash('success', 'Merchant rule added successfully.');
    redirect('merchant_rules.php');
}

if (isset($_GET['delete'])) {
    $ruleId = (int)$_GET['delete'];

    $stmt = $pdo->prepare("
        DELETE FROM merchant_rules
        WHERE id = ?
        AND user_id = ?
    ");
    $stmt->execute([$ruleId, $userId]);

    flash('success', 'Merchant rule deleted successfully.');
    redirect('merchant_rules.php');
}

$rules = get_merchant_rules($pdo, $userId);

require_once 'header.php';
?>

<div class="table-card form-card">

    <h3>Add Merchant Rule</h3>

    <p class="muted">
        Create rules like Netflix → Entertainment or Uber → Transport.
    </p>

    <?php if (!$categories): ?>

        <p class="muted">Create a category before adding merchant rules.</p>

        <div class="action-row">
            <a href="categories.php" class="btn">Add Category</a>
        </div>

    <?php else: ?>

        <form method="POST" class="form-grid">

            <div class="form-row">
                <label>Merchant Keyword</label>
                <input
                    type="text"
                    name="keyword"
                    placeholder="Example: Netflix, Uber, Woolworths"
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

            <div class="form-row full">
                <button type="submit">Save Rule</button>
            </div>

        </form>

    <?php endif; ?>

</div>

<div class="table-card">

    <h3>Your Merchant Rules</h3>

    <?php if (!$rules): ?>

        <p class="muted">No merchant rules added yet.</p>

    <?php else: ?>

        <div class="table-responsive">

            <table>
                <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>Mapped Category</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($rules as $rule): ?>

                        <tr>
                            <td><?= e($rule['keyword']) ?></td>

                            <td>
                                <span class="badge">
                                    <?= e($rule['category']) ?>
                                </span>
                            </td>

                            <td><?= e($rule['created_at']) ?></td>

                            <td>
                                <a
                                    class="btn danger"
                                    href="merchant_rules.php?delete=<?= $rule['id'] ?>"
                                    onclick="return confirm('Delete this rule?')"
                                >
                                    Delete
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    <?php endif; ?>

</div>

<?php require_once 'footer.php'; ?>
