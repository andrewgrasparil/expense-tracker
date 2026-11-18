<?php
/**
 * includes/functions.php
 * ---------------------------------------------------------------
 * Small helper functions used by every page.
 * This file is loaded automatically by db_connect.php, so each page
 * only needs:  require_once __DIR__ . '/db_connect.php';
 *
 * What is inside:
 *   1. Session setup
 *   2. Output escaping (protects against XSS)
 *   3. Redirect, login check, flash messages
 *   4. CSRF protection (protects forms from fake submissions)
 *   5. Expense helpers: categories, validation, filters, money format
 * ---------------------------------------------------------------
 */

// Currency symbol shown on the pages (Philippine peso).
const APP_CURRENCY = '₱';

/* ================================================================
 * 1. SESSION SETUP
 * A session lets the server "remember" who is logged in.
 * ================================================================ */
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,          // cookie lasts until the browser closes
        'path'     => '/',
        'secure'   => $isHttps,   // only send over HTTPS when the site uses it
        'httponly' => true,       // JavaScript cannot read the session cookie
        'samesite' => 'Lax',      // extra protection against CSRF
    ]);
    session_start();
}

/* ================================================================
 * 2. OUTPUT ESCAPING
 * ALWAYS wrap user data in e() before printing it in HTML.
 * Example: <?= e($row['title']) ?>
 * ================================================================ */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* ================================================================
 * 3. REDIRECT, LOGIN CHECK, FLASH MESSAGES
 * ================================================================ */

// Send the browser to another page and stop running this one.
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

// True when a user is logged in.
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

// Put this at the top of any page that needs a login.
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in first.');
        redirect('login.php');
    }
}

// A "flash message" is shown once (after a redirect) and then disappears.
function set_flash(string $type, string $message): void
{
    // $type is a Bootstrap alert color: success, danger, warning, info
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);   // show it only once
        return $flash;
    }
    return null;
}

/* ================================================================
 * 4. CSRF PROTECTION
 * Every form includes a secret token. The server checks that the
 * token it receives matches the one stored in the session. This stops
 * other websites from submitting forms on behalf of a logged-in user.
 * ================================================================ */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Returns the hidden input. Echo it inside every form: echo csrf_field();
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// Returns true when the submitted token is valid.
function csrf_check(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $sent);
}

/* ================================================================
 * 5. EXPENSE HELPERS
 * ================================================================ */

// The allowed categories and the Bootstrap classes of their badge color.
function expense_categories(): array
{
    return [
        'Food'              => 'bg-warning text-dark',
        'Transportation'    => 'bg-info text-dark',
        'Bills & Utilities' => 'bg-danger',
        'Education'         => 'bg-primary',
        'Health'            => 'bg-success',
        'Entertainment'     => 'bg-secondary',
        'Shopping'          => 'bg-dark',
        'Other'             => 'bg-light text-dark border',
    ];
}

// Returns a colored Bootstrap badge for a category.
function category_badge(string $category): string
{
    $categories = expense_categories();
    $class = $categories[$category] ?? 'bg-light text-dark border';
    return '<span class="badge ' . $class . '">' . e($category) . '</span>';
}

// 1234.5 -> "₱1,234.50"
function format_money($amount): string
{
    return APP_CURRENCY . number_format((float)$amount, 2);
}

/**
 * Validate the data from the Add / Edit expense form.
 * Returns two things: the cleaned values and a list of error messages.
 * Usage:  [$clean, $errors] = validate_expense($_POST);
 */
function validate_expense(array $input): array
{
    $errors = [];

    // Read a text field safely (ignores arrays and missing fields).
    $get = function (string $key) use ($input): string {
        return (isset($input[$key]) && is_string($input[$key])) ? trim($input[$key]) : '';
    };

    $title       = $get('title');
    $amount      = str_replace(',', '', $get('amount'));  // allow "1,250.50"
    $category    = $get('category');
    $date        = $get('expense_date');
    $description = $get('description');

    // Title: required, max 100 characters
    if ($title === '') {
        $errors[] = 'Title is required.';
    } elseif (mb_strlen($title) > 100) {
        $errors[] = 'Title must be 100 characters or fewer.';
    }

    // Amount: positive number, up to 2 decimals (fits DECIMAL(10,2))
    if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $amount) || (float)$amount <= 0) {
        $errors[] = 'Amount must be a positive number with up to 2 decimals (for example 150.75).';
    }

    // Category: must be one of the allowed categories
    if (!array_key_exists($category, expense_categories())) {
        $errors[] = 'Please choose a valid category.';
    }

    // Date: must be a real date in YYYY-MM-DD format
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) {
        $errors[] = 'Please enter a valid date.';
    }

    // Description: optional, max 500 characters
    if (mb_strlen($description) > 500) {
        $errors[] = 'Description must be 500 characters or fewer.';
    }

    $clean = [
        'title'        => $title,
        'amount'       => $amount,
        'category'     => $category,
        'expense_date' => $date,
        'description'  => $description,
    ];

    return [$clean, $errors];
}

/**
 * Reads the filter values from the URL (?category=Food&q=lunch).
 * Returns [$category, $keyword]. Invalid categories become '' (= all).
 */
function get_filters(): array
{
    $category = (isset($_GET['category']) && is_string($_GET['category'])) ? trim($_GET['category']) : '';
    if (!array_key_exists($category, expense_categories())) {
        $category = '';
    }

    $q = (isset($_GET['q']) && is_string($_GET['q'])) ? trim($_GET['q']) : '';
    $q = mb_substr($q, 0, 100);

    return [$category, $q];
}

/**
 * Builds the WHERE part of the SQL query for the current user + filters.
 * It only joins fixed text; the user's values go into $params and are
 * bound later by PDO (prepared statement), so SQL injection is impossible.
 * Used by both index.php and export.php so they always match.
 */
function expense_filter(int $userId, string $category, string $q): array
{
    $where  = 'user_id = :uid';
    $params = [':uid' => $userId];

    if ($category !== '') {
        $where .= ' AND category = :category';
        $params[':category'] = $category;
    }

    if ($q !== '') {
        // Two different placeholder names because real prepared statements
        // do not allow the same name twice.
        $where .= ' AND (title LIKE :q1 OR description LIKE :q2)';
        $like = '%' . addcslashes($q, '%_\\') . '%';   // escape % and _
        $params[':q1'] = $like;
        $params[':q2'] = $like;
    }

    return [$where, $params];
}
