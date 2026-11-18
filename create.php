<?php
/**
 * create.php  -  ADD A NEW EXPENSE  (the "C" in CRUD: CREATE / INSERT)
 * ---------------------------------------------------------------
 * Shows the form and, when it is submitted, validates the data and
 * INSERTs a new row into the expenses table.
 * ---------------------------------------------------------------
 */
require_once __DIR__ . '/db_connect.php';
require_login();

$userId = (int)$_SESSION['user_id'];
$errors = [];

// Starting values for the form (today's date by default)
$values = [
    'title'        => '',
    'amount'       => '',
    'category'     => '',
    'expense_date' => date('Y-m-d'),
    'description'  => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_check()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        // Validate and clean the submitted data (see includes/functions.php)
        [$clean, $errors] = validate_expense($_POST);
        $values = $clean;     // keep what the user typed so the form is not emptied

        if (!$errors) {
            // INSERT with a prepared statement: values are never glued into the SQL text
            $stmt = $pdo->prepare(
                'INSERT INTO expenses (user_id, title, amount, category, expense_date, description)
                 VALUES (:user_id, :title, :amount, :category, :expense_date, :description)'
            );
            $stmt->execute([
                ':user_id'      => $userId,                       // from the session, not the form
                ':title'        => $clean['title'],
                ':amount'       => $clean['amount'],
                ':category'     => $clean['category'],
                ':expense_date' => $clean['expense_date'],
                ':description'  => $clean['description'] !== '' ? $clean['description'] : null,
            ]);

            set_flash('success', 'Expense added successfully.');
            redirect('index.php');
        }
    }
}

$page_title = 'Add Expense';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h1 class="h5 mb-0"><i class="bi bi-plus-circle"></i> Add a new expense</h1>
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

                <form method="post" action="create.php">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="<?= e($values['title']) ?>" maxlength="100"
                               placeholder="e.g. Lunch at the canteen" required autofocus>
                    </div>

                    <div class="row">
                        <div class="col-12 col-md-6 mb-3">
                            <label for="amount" class="form-label">Amount (<?= e(APP_CURRENCY) ?>)</label>
                            <input type="number" class="form-control" id="amount" name="amount"
                                   value="<?= e($values['amount']) ?>" step="0.01" min="0.01"
                                   max="99999999.99" placeholder="0.00" required>
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
                                  maxlength="500" placeholder="Any extra notes..."><?= e($values['description']) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Expense</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
