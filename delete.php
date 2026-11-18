<?php
/**
 * delete.php  -  DELETE AN EXPENSE  (the "D" in CRUD)
 * ---------------------------------------------------------------
 * Called by the Delete button on the dashboard.
 * - Only accepts POST (a link or a search engine cannot delete data).
 * - Checks the CSRF token.
 * - Deletes the row only if it belongs to the logged-in user.
 * This page shows nothing: it deletes, sets a message, and redirects.
 * ---------------------------------------------------------------
 */
require_once __DIR__ . '/db_connect.php';
require_login();

// Visiting delete.php directly (GET) does nothing.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

if (!csrf_check()) {
    set_flash('danger', 'Your session expired. Please try again.');
    redirect('index.php');
}

$userId = (int)$_SESSION['user_id'];
$id     = (int)($_POST['id'] ?? 0);

// DELETE with a prepared statement. "AND user_id" protects other users' data.
$stmt = $pdo->prepare('DELETE FROM expenses WHERE id = :id AND user_id = :uid');
$stmt->execute([':id' => $id, ':uid' => $userId]);

if ($stmt->rowCount() > 0) {
    set_flash('success', 'Expense deleted.');
} else {
    set_flash('danger', 'Expense not found.');
}

redirect('index.php');
