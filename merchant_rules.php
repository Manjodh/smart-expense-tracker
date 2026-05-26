<?php
$pageTitle = 'Merchant Rules';

require_once 'db.php';
require_once 'functions.php';

require_login();

$userId = current_user_id();
$categories = get_user_categories($pdo, $userId);

$editingRule = null;

if (isset($_GET['edit'])) {
    $ruleId = (int)$_GET['edit'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM merchant_rules
        WHERE id = ?
        AND user_id = ?
    ");
    $stmt->execute([$ruleId, $userId]);
    $editingRule = $stmt->fetch();

    if (!$editingRule) {
        flash('error', 'Merchant rule not found.');
        redirect('merchant_rules.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_rule'])) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_rule'])) {
    $ruleId = (int)($_POST['rule_id'] ?? 0);
    $keyword = trim($_POST['keyword'] ?? '');
    $category = trim($_POST['category'] ?? '');

    if ($keyword === '' || $category === '') {
        flash('error', 'Keyword and category are required.');
        redirect('merchant_rules.php?edit=' . $ruleId);
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
        redirect('merchant_rules.php?edit=' . $ruleId);
    }

    $stmt = $pdo->prepare("
        UPDATE merchant_rules
        SET keyword = ?,
            category = ?
        WHERE id = ?
        AND user_id = ?
    ");
    $stmt->execute([$keyword, $category, $ruleId, $userId]);

    flash('success', 'Merchant rule updated successfully.');
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

    <h3><?= $editingRule ? 'Edit Merchant Rule' : 'Add Merchant Rule' ?></h3>

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

            <?php if ($editingRule): ?>
                <input
                    type="hidden"
                    name="rule_id"
                    value="<?= e($editingRule['id']) ?>"
                >
            <?php endif; ?>

            <div class="form-row">
                <label>Merchant Keyword</label>

                <input
                    type="text"
                    name="keyword"
                    value="<?= e($editingRule['keyword'] ?? '') ?>"
                    placeholder="Example: Netflix, Uber, Woolworths"
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
                            <?= (($editingRule['category'] ?? '') === $cat['name']) ? 'selected' : '' ?>
                        >
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row full">
                <?php if ($editingRule): ?>
                    <button type="submit" name="update_rule" value="1">
                        Update Rule
                    </button>

                    <a href="merchant_rules.php" class="btn secondary">
                        Cancel
                    </a>
                <?php else: ?>
                    <button type="submit" name="add_rule" value="1">
                        Save Rule
                    </button>
                <?php endif; ?>
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
                        <th>Actions</th>
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
                                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                                    <a
                                        class="btn secondary"
                                        href="merchant_rules.php?edit=<?= $rule['id'] ?>"
                                    >
                                        Edit
                                    </a>

                                    <a
                                        class="btn danger"
                                        href="merchant_rules.php?delete=<?= $rule['id'] ?>"
                                        onclick="return confirm('Delete this rule?')"
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