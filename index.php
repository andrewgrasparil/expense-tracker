<?php
/**
 * index.php  -  DASHBOARD  (the "R" in CRUD: READ / SELECT)
 * ---------------------------------------------------------------
 * Shows the logged-in user's expenses in a table, with:
 *   - summary cards (total, number of records, this month)
 *   - filter by category + keyword search
 *   - a Delete button for each row (handled by delete.php)
 * ---------------------------------------------------------------
 */
require_once __DIR__ . '/db_connect.php';
require_login();                                  // visitors are sent to login.php

$userId = (int)$_SESSION['user_id'];

// 1. Read the filters from the URL (?category=Food&q=lunch)
[$category, $q] = get_filters();

// 2. Build the WHERE part of the query (it always includes this user's id)
[$where, $params] = expense_filter($userId, $category, $q);

// 3. SELECT: the list of expenses, newest first
//    ($where only contains fixed text; the values are bound separately.)
$stmt = $pdo->prepare(
    "SELECT id, title, amount, category, expense_date, description
     FROM expenses
     WHERE $where
     ORDER BY expense_date DESC, id DESC"
);
$stmt->execute($params);
$expenses = $stmt->fetchAll();

// 4. SELECT: total and number of records for the current filter
$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS total, COUNT(*) AS cnt FROM expenses WHERE $where");
$stmt->execute($params);
$summary = $stmt->fetch();

// 5. SELECT: total for THIS MONTH (ignores the filters)
$stmt = $pdo->prepare(
    'SELECT COALESCE(SUM(amount), 0) FROM expenses
     WHERE user_id = :uid AND expense_date BETWEEN :first_day AND :last_day'
);
$stmt->execute([
    ':uid'       => $userId,
    ':first_day' => date('Y-m-01'),   // first day of this month
    ':last_day'  => date('Y-m-t'),    // last day of this month
]);
$monthTotal = $stmt->fetchColumn();

// Link for the Export button: keeps the current filters
$exportQuery = http_build_query(array_filter(['category' => $category, 'q' => $q]));
$isFiltered  = ($category !== '' || $q !== '');

$page_title = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-2 mb-md-0">My Expenses</h1>
    <div>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add Expense</a>
        <a href="export.php<?= $exportQuery ? '?' . e($exportQuery) : '' ?>" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
        </a>
    </div>
</div>

<!-- ===================== SUMMARY CARDS ===================== -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label"><?= $isFiltered ? 'Total (filtered)' : 'Total spent' ?></div>
                <div class="stat-value"><?= e(format_money($summary['total'])) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card stat-card stat-green shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label">Records</div>
                <div class="stat-value"><?= (int)$summary['cnt'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card stat-card stat-orange shadow-sm h-100">
            <div class="card-body">
                <div class="stat-label">This month</div>
                <div class="stat-value"><?= e(format_money($monthTotal)) ?></div>
            </div>
        </div>
    </div>
</div>

<!-- ===================== FILTER / SEARCH ===================== -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="index.php" class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
                <label for="category" class="form-label mb-1">Category</label>
                <select name="category" id="category" class="form-select">
                    <option value="">All categories</option>
                    <?php foreach (expense_categories() as $name => $badgeClass): ?>
                        <option value="<?= e($name) ?>" <?= $category === $name ? 'selected' : '' ?>>
                            <?= e($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-5">
                <label for="q" class="form-label mb-1">Search title or description</label>
                <input type="text" name="q" id="q" class="form-control" maxlength="100"
                       placeholder="e.g. lunch, jeepney, load" value="<?= e($q) ?>">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
                <a href="index.php" class="btn btn-outline-secondary flex-fill">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- ===================== EXPENSES TABLE ===================== -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <?php if (!$expenses): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox display-5"></i>
                <p class="mt-2 mb-3">
                    <?= $isFiltered ? 'No expenses match your filter.' : 'You have no expenses yet.' ?>
                </p>
                <?php if (!$isFiltered): ?>
                    <a href="create.php" class="btn btn-primary">Add your first expense</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th class="text-end">Amount</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenses as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="text-nowrap"><?= e(date('M d, Y', strtotime($row['expense_date']))) ?></td>
                                <td>
                                    <?= e($row['title']) ?>
                                    <?php if ($row['description'] !== null && $row['description'] !== ''): ?>
                                        <div class="small text-muted"><?= e($row['description']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= category_badge($row['category']) ?></td>
                                <td class="text-end amount-col"><?= e(format_money($row['amount'])) ?></td>
                                <td class="text-center text-nowrap">
                                    <!-- UPDATE: opens the edit form -->
                                    <a href="edit.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>

                                    <!-- DELETE: a small POST form (with CSRF token) and a confirm box -->
                                    <form action="delete.php" method="post" class="d-inline"
                                          onsubmit="return confirm('Delete this expense? This cannot be undone.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="4" class="text-end">TOTAL<?= $isFiltered ? ' (filtered)' : '' ?></td>
                            <td class="text-end amount-col"><?= e(format_money($summary['total'])) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
