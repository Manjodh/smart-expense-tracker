<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function redirect($path) {
    header("Location: $path");
    exit;
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }

    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }

    return null;
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function money($amount) {
    return '$' . number_format((float)$amount, 2);
}

function active_nav($page) {
    return basename($_SERVER['PHP_SELF']) === $page ? 'active' : '';
}

function upload_receipt($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Receipt upload failed. Try again.'];
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $maxSize = 5 * 1024 * 1024;

    if ($file['size'] > $maxSize) {
        return [null, 'Receipt must be 5MB or smaller.'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        return [null, 'Only JPG, JPEG, PNG, and PDF receipts are allowed.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedMimeTypes, true)) {
        return [null, 'Invalid receipt file type.'];
    }

    $uploadDir = __DIR__ . '/uploads/receipts/';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $uniqueName = 'receipt_' . bin2hex(random_bytes(16)) . '.' . $extension;
    $targetPath = $uploadDir . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [null, 'Could not save receipt.'];
    }

    return ['uploads/receipts/' . $uniqueName, null];
}

function get_user_categories($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT name
        FROM categories
        WHERE user_id = ?
        ORDER BY name ASC
    ");
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function get_monthly_stats($pdo, $userId, $monthYear) {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(amount), 0) AS total_spending,
            COUNT(*) AS total_expenses
        FROM expenses
        WHERE user_id = ?
        AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ");
    $stmt->execute([$userId, $monthYear]);

    return $stmt->fetch();
}

function get_category_totals($pdo, $userId, $monthYear = null) {
    $sql = "
        SELECT category, SUM(amount) AS total
        FROM expenses
        WHERE user_id = ?
    ";

    $params = [$userId];

    if ($monthYear !== null) {
        $sql .= " AND DATE_FORMAT(expense_date, '%Y-%m') = ?";
        $params[] = $monthYear;
    }

    $sql .= "
        GROUP BY category
        ORDER BY total DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function get_budget_progress($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT 
            b.category,
            b.limit_amount,
            b.month_year,
            COALESCE(SUM(e.amount), 0) AS spent
        FROM budgets b
        LEFT JOIN expenses e
            ON e.user_id = b.user_id
            AND e.category = b.category
            AND DATE_FORMAT(e.expense_date, '%Y-%m') = b.month_year
        WHERE b.user_id = ?
        GROUP BY b.id
        ORDER BY b.month_year DESC, b.category ASC
    ");
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function get_user_goals($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM goals
        WHERE user_id = ?
        ORDER BY id DESC
    ");
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}
function get_due_recurring_expenses($pdo, $userId, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM recurring_expenses
        WHERE user_id = ?
        AND is_active = 1
        AND next_due_date <= CURDATE()
        ORDER BY next_due_date ASC
        LIMIT ?
    ");

    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function get_upcoming_recurring_expenses($pdo, $userId, $days = 7, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM recurring_expenses
        WHERE user_id = ?
        AND is_active = 1
        AND next_due_date > CURDATE()
        AND next_due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ORDER BY next_due_date ASC
        LIMIT ?
    ");

    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $days, PDO::PARAM_INT);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function get_merchant_rules($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM merchant_rules
        WHERE user_id = ?
        ORDER BY keyword ASC
    ");
    $stmt->execute([$userId]);

    return $stmt->fetchAll();
}

function match_category_from_rules($rules, $description) {
    $description = strtolower($description);

    foreach ($rules as $rule) {
        $keyword = strtolower($rule['keyword']);

        if ($keyword !== '' && strpos($description, $keyword) !== false) {
            return $rule['category'];
        }
    }

    return null;
}

?>