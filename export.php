<?php
/**
 * export.php  -  EXPORT TO CSV  (the Creativity / Enhancement feature)
 * ---------------------------------------------------------------
 * Downloads the user's expenses as a .csv file that opens in Excel.
 * It uses the SAME filters as the dashboard, so "Export CSV" exports
 * exactly what you are looking at (export.php?category=Food&q=lunch).
 * ---------------------------------------------------------------
 */
require_once __DIR__ . '/db_connect.php';
require_login();

$userId = (int)$_SESSION['user_id'];

[$category, $q]   = get_filters();
[$where, $params] = expense_filter($userId, $category, $q);

$stmt = $pdo->prepare(
    "SELECT title, amount, category, expense_date, description
     FROM expenses
     WHERE $where
     ORDER BY expense_date DESC, id DESC"
);
$stmt->execute($params);

/**
 * Protects against "CSV injection": a cell starting with = + - @ could be
 * run as a formula by Excel. A leading apostrophe makes it plain text.
 */
function csv_safe($value): string
{
    $value = (string)$value;
    if ($value !== '' && strpbrk($value[0], "=+-@\t\r") !== false) {
        return "'" . $value;
    }
    return $value;
}

// Tell the browser: "this is a file to download", not a web page.
$filename = 'expenses_' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');

// UTF-8 "BOM" so Excel shows special characters correctly
fwrite($out, "\xEF\xBB\xBF");

// Header row
fputcsv($out, ['Date', 'Title', 'Category', 'Amount', 'Description'], ',', '"', '\\');

// One row per expense, and add up the total as we go
$total = 0.0;
while ($row = $stmt->fetch()) {
    $total += (float)$row['amount'];
    fputcsv($out, [
        $row['expense_date'],
        csv_safe($row['title']),
        $row['category'],
        number_format((float)$row['amount'], 2, '.', ''),   // 1250.50 (no thousands comma)
        csv_safe($row['description']),
    ], ',', '"', '\\');
}

// Last row: the total
fputcsv($out, ['', 'TOTAL', '', number_format($total, 2, '.', ''), ''], ',', '"', '\\');

fclose($out);
exit;
