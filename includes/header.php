<?php
/**
 * includes/header.php
 * ---------------------------------------------------------------
 * Top part of every page: <head>, Bootstrap 5 CSS, navbar and the
 * flash message. Each page sets $page_title BEFORE including this:
 *
 *     $page_title = 'Dashboard';
 *     require_once __DIR__ . '/includes/header.php';
 * ---------------------------------------------------------------
 */
require_once __DIR__ . '/functions.php';

$page_title   = $page_title ?? 'Expense Tracker';
$current_page = basename($_SERVER['PHP_SELF']);   // used to highlight the active link
$flash        = get_flash();                      // message from the previous page (or null)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- makes the page responsive on phones -->
    <title><?= e($page_title) ?> | Expense Tracker</title>

    <!-- Bootstrap 5 CSS and Bootstrap Icons (loaded from a CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Our own small stylesheet -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<!-- ===================== NAVIGATION BAR ===================== -->
<nav class="navbar navbar-expand-md navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= is_logged_in() ? 'index.php' : 'login.php' ?>">
            <i class="bi bi-wallet2 me-1"></i> Expense Tracker
        </a>

        <!-- Hamburger button shown on small screens -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <?php if (is_logged_in()): ?>
                <!-- Links for logged-in users -->
                <ul class="navbar-nav me-auto mb-2 mb-md-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'create.php' ? 'active' : '' ?>" href="create.php">
                            <i class="bi bi-plus-circle"></i> Add Expense
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="export.php">
                            <i class="bi bi-download"></i> Export CSV
                        </a>
                    </li>
                </ul>
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle"></i> <?= e($_SESSION['username'] ?? '') ?>
                </span>
                <a class="btn btn-light btn-sm" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            <?php else: ?>
                <!-- Links for visitors -->
                <ul class="navbar-nav ms-auto mb-2 mb-md-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'login.php' ? 'active' : '' ?>" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'register.php' ? 'active' : '' ?>" href="register.php">Register</a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ===================== PAGE CONTENT STARTS ===================== -->
<main class="flex-grow-1 py-4">
    <div class="container">

        <?php if ($flash): ?>
            <!-- One-time message (success, error, warning...) -->
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
