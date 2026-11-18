<?php
/**
 * db_connect.php
 * ---------------------------------------------------------------
 * Connects to the MySQL database using PDO and makes the $pdo
 * variable available to every page that does:
 *
 *     require_once __DIR__ . '/db_connect.php';
 *
 * HOW THE CREDENTIALS WORK
 *   - On your computer (XAMPP) the site runs on "localhost", so the
 *     LOCAL settings are used automatically.
 *   - On InfinityFree the site runs on your free domain, so the LIVE
 *     settings are used. Fill them in (Step 7 of the deployment guide)
 *     with the values shown in InfinityFree > MySQL Databases.
 *
 * TIP: Do not push your real live password to GitHub. Keep the
 * placeholders below in your repository, and edit the file only
 * on the InfinityFree server (File Manager).
 * ---------------------------------------------------------------
 */

// Use the Philippine time zone for dates such as "this month".
date_default_timezone_set('Asia/Manila');

// Load the helper functions (this also starts the session).
require_once __DIR__ . '/includes/functions.php';

// Are we running on the local XAMPP server?
$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
$isLocal    = in_array($serverName, ['localhost', '127.0.0.1', '::1'], true);

if ($isLocal) {
    /* ---------- LOCAL (XAMPP) SETTINGS ---------- */
    $db_host = 'localhost';
    $db_name = 'expense_tracker';
    $db_user = 'root';
    $db_pass = '';              // XAMPP's default root password is empty

    // Show all errors while developing so bugs are easy to find.
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    /* ---------- LIVE (INFINITYFREE) SETTINGS ----------
     * Copy these four values from your InfinityFree account:
     * Client Area > your hosting account > Control Panel > MySQL Databases
     */
    $db_host = 'sqlXXX.infinityfree.com';   // MySQL Host Name
    $db_name = 'if0_XXXXXXXX_expenses';     // full database name (with prefix)
    $db_user = 'if0_XXXXXXXX';              // MySQL User Name
    $db_pass = 'YOUR_VPANEL_PASSWORD';      // your hosting account password

    // Never show technical errors to visitors on the live site.
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
}

// DSN = "Data Source Name": tells PDO which server and database to use.
$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // throw errors so we can catch them
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // rows come back as ['title' => '...']
    PDO::ATTR_EMULATE_PREPARES   => false,                   // use REAL prepared statements
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $ex) {
    // Do not reveal passwords or server details to visitors.
    http_response_code(500);
    echo '<h3>Database connection failed.</h3>';
    echo '<p>Please check the settings in <code>db_connect.php</code>.</p>';
    if ($isLocal) {
        // Only show the real reason on your own computer.
        echo '<p><small>' . htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8') . '</small></p>';
    }
    exit;
}
