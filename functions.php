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
?>
