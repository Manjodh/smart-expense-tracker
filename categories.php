<?php
$pageTitle = 'Categories';

require_once 'db.php';
require_once 'functions.php';

require_login();

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        flash('error', 'Category name is required.');
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO categories (user_id, name, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$userId, $name]);

            flash('success', 'Category added successfully.');
        } catch (PDOException $e) {
            flash('error', 'This category already exists.');
        }
    }

    redirect('categories.php');
}

if (isset($_GET['delete'])) {
    $categoryId = (int) $_GET['delete'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM categories
        WHERE id = ?
        AND user_id = ?
    ");
    $stmt->execute([$categoryId, $userId]);
    $category = $stmt->fetch();

    if (!$category) {
        flash('error', 'Category not found.');
        redirect('categories.php');
    }

    $expenseCheck = $pdo->prepare("
        SELECT COUNT(*)
        FROM expenses
        WHERE user_id = ?
        AND category = ?
    ");
    $expenseCheck->execute([$userId, $category['name']]);
    $expenseCount = (int) $expenseCheck->fetchColumn();

    $budgetCheck = $pdo->prepare("
        SELECT COUNT(*)
        FROM budgets
        WHERE user_id = ?
        AND category = ?
    ");
    $budgetCheck->execute([$userId, $category['name']]);
    $budgetCount = (int) $budgetCheck->fetchColumn();

    if ($expenseCount > 0 || $budgetCount > 0) {
        flash(
            'error',
            'This category is being used by expenses or budgets and cannot be deleted.'
        );

        redirect('categories.php');
    }

    $stmt = $pdo->prepare("
        DELETE FROM categories
        WHERE id = ?
        AND user_id = ?
    ");
    $stmt->execute([$categoryId, $userId]);

    flash('success', 'Category deleted successfully.');
    redirect('categories.php');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM categories
    WHERE user_id = ?
    ORDER BY name ASC
");
$stmt->execute([$userId]);
$categories = $stmt->fetchAll();

require_once 'header.php';
?>

<div class="table-card form-card">

    <h3>Add Category</h3>

    <form method="POST" class="form-grid">

        <div class="form-row full">
            <label>Category Name</label>

            <input
                type="text"
                name="name"
                placeholder="Example: Rent, Coffee, Uni, Fuel"
                required
            >
        </div>

        <div class="form-row full">
            <button type="submit">
                Add Category
            </button>
        </div>

    </form>

</div>

<div class="table-card">

    <h3>Your Categories</h3>

    <?php if (!$categories): ?>

        <p class="muted">No custom categories yet.</p>

    <?php else: ?>

        <div class="table-responsive">

            <table>

                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($categories as $category): ?>

                        <tr>
                            <td>
                                <span class="badge">
                                    <?= e($category['name']) ?>
                                </span>
                            </td>

                            <td><?= e($category['created_at']) ?></td>

                            <td>
                                <a
                                    class="btn danger"
                                    href="categories.php?delete=<?= $category['id'] ?>"
                                    onclick="return confirm('Delete this category?')"
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