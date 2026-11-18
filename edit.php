<?php
/**
 * edit.php  -  EDIT AN EXPENSE  (the "U" in CRUD: UPDATE)
 * ---------------------------------------------------------------
 * 1. Loads the expense chosen on the dashboard (edit.php?id=5).
 * 2. Shows it in a form.
 * 3. When submitted, validates the data and UPDATEs the row.
 *
 * Security: every query also checks user_id, so a user can only
 * open or change THEIR OWN expenses (even if they type another id).
 * ---------------------------------------------------------------
 */
require_once __DIR__ . '/db_connect.php';
require_login();

$userId = (int)$_SESSION['user_id'];
$errors = [];

// Which expense? It comes from the URL (first visit) or a hidden field (form submit).
$id = (int)($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['id'] ?? 0) : ($_GET['id'] ?? 0));

// SELECT the expense, but only if it belongs to the logged-in user
$stmt = $pdo->prepare(
    'SELECT id, title, amount, category, expense_date, description
     FROM expenses
     WHERE id = :id AND user_id = :uid'
);
$stmt->execute([':id' => $id, ':uid' => $userId]);
$expense = $stmt->fetch();

if (!$expense) {
    set_flash('danger', 'Expense not found.');
    redirect('index.php');
}

// Values shown in the form (start with what is saved in the database)
$values = [
    'title'        => $expense['title'],
    'amount'       => $expense['amount'],
    'category'     => $expense['category'],
    'expense_date' => $expense['expense_date'],
    'description'  => (string)$expense['description'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_check()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        [$clean, $errors] = validate_expense($_POST);
        $values = $clean;     // show what the user typed if there are errors

        if (!$errors) {
            // UPDATE with a prepared statement. "AND user_id" is the safety check.
            $stmt = $pdo->prepare(
                'UPDATE expenses
                 SET title = :title,
                     amount = :amount,
                     category = :category,
                     expense_date = :expense_date,
                     description = :description
                 WHERE id = :id AND user_id = :uid'
            );
            $stmt->execute([
                ':title'        => $clean['title'],
                ':amount'       => $clean['amount'],
                ':category'     => $clean['category'],
                ':expense_date' => $clean['expense_date'],
                ':description'  => $clean['description'] !== '' ? $clean['description'] : null,
                ':id'           => $id,
                ':uid'          => $userId,
            ]);

            set_flash('success', 'Expense updated successfully.');
            redirect('index.php');
        }
    }
}

$page_title = 'Edit Expense';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h1 class="h5 mb-0"><i class="bi bi-pencil-square"></i> Edit expense</h1>
            </div>
            <div class="card-body p-4">

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="edit.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$expense['id'] ?>">

                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="<?= e($values['title']) ?>" maxlength="100" required autofocus>
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount (<?= e(APP_CURRENCY) ?>)</label>
                            <input type="number" class="form-control" id="amount" name="amount"
                                   value="<?= e($values['amount']) ?>" step="0.01" min="0.01"
                                   max="99999999.99" required>
                        </div>
                        <div class="col-12 col-md-6 mb-3">
                            <label for="expense_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="expense_date" name="expense_date"
                                   value="<?= e($values['expense_date']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Choose a category...</option>
                            <?php foreach (expense_categories() as $name => $badgeClass): ?>
                                <option value="<?= e($name) ?>" <?= $values['category'] === $name ? 'selected' : '' ?>>
                                    <?= e($name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">Description <span class="text-muted">(optional)</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  maxlength="500"><?= e($values['description']) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Update Expense</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
