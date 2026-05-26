<?php
require_once 'db.php';
require_once 'functions.php';

require_login();

$userId = current_user_id();
$selectedMonth = trim($_GET['month'] ?? date('Y-m'));

if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
    $selectedMonth = date('Y-m');
}

$stmt = $pdo->prepare("
    SELECT 
        expense_date,
        category,
        note,
        amount
    FROM expenses
    WHERE user_id = ?
    AND DATE_FORMAT(expense_date, '%Y-%m') = ?
    ORDER BY expense_date DESC, id DESC
");
$stmt->execute([$userId, $selectedMonth]);
$expenses = $stmt->fetchAll();

$filename = 'monthly-report-' . $selectedMonth . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Date',
    'Category',
    'Note',
    'Amount'
]);

foreach ($expenses as $expense) {
    fputcsv($output, [
        $expense['expense_date'],
        $expense['category'],
        $expense['note'],
        number_format((float)$expense['amount'], 2, '.', '')
    ]);
}

fclose($output);
exit;