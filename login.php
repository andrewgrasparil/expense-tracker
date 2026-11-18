<?php
/**
 * login.php
 * ---------------------------------------------------------------
 * Checks the username and password against the users table (SELECT).
 * password_verify() compares the typed password with the stored hash.
 * ---------------------------------------------------------------
 */
require_once __DIR__ . '/db_connect.php';

// Already logged in? Go straight to the dashboard.
if (is_logged_in()) {
    redirect('index.php');
}

$errors   = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_check()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $username = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

        if ($username === '' || $password === '') {
            $errors[] = 'Please enter your username and password.';
        } else {
            // Find the user (prepared statement = safe from SQL injection)
            $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE username = :username');
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Correct login: create a fresh session id (prevents session fixation)
                session_regenerate_id(true);
                unset($_SESSION['csrf_token']);          // new token for the new session

                $_SESSION['user_id']  = (int)$user['id'];
                $_SESSION['username'] = $user['username'];

                set_flash('success', 'Welcome back, ' . $user['username'] . '!');
                redirect('index.php');
            } else {
                // Same message for "no such user" and "wrong password" on purpose,
                // so attackers cannot find out which usernames exist.
                $errors[] = 'Invalid username or password.';
            }
        }
    }
}

$page_title = 'Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card shadow-sm auth-card">
    <div class="card-body p-4">
        <h1 class="h4 mb-1"><i class="bi bi-box-arrow-in-right"></i> Log in</h1>
        <p class="text-muted">Welcome back! Log in to see your expenses.</p>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= e($username) ?>" required autofocus>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Log in</button>
        </form>

        <p class="text-center mt-3 mb-0">
            No account yet? <a href="register.php">Register here</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
