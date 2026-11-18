<?php
/**
 * register.php
 * ---------------------------------------------------------------
 * Creates a new user account (INSERT into the users table).
 * Passwords are never stored as plain text: password_hash() turns
 * them into a one-way bcrypt hash.
 * ---------------------------------------------------------------
 */
require_once __DIR__ . '/db_connect.php';

// Already logged in? No need to register again.
if (is_logged_in()) {
    redirect('index.php');
}

$errors   = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Check the CSRF token
    if (!csrf_check()) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        // 2. Read the form values safely
        $username = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
        $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
        $confirm  = isset($_POST['confirm_password']) && is_string($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        // 3. Validate
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Username must be 3-30 characters: letters, numbers, or underscore only.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (strlen($password) > 72) {
            $errors[] = 'Password must be 72 characters or fewer.';   // bcrypt limit
        }
        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        // 4. Is the username already taken? (SELECT with a prepared statement)
        if (!$errors) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
            $stmt->execute([':username' => $username]);
            if ($stmt->fetch()) {
                $errors[] = 'That username is already taken. Please choose another one.';
            }
        }

        // 5. Everything is fine: INSERT the new user
        if (!$errors) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);   // secure one-way hash

                $stmt = $pdo->prepare('INSERT INTO users (username, password) VALUES (:username, :password)');
                $stmt->execute([
                    ':username' => $username,
                    ':password' => $hash,
                ]);

                set_flash('success', 'Account created! You can now log in.');
                redirect('login.php');
            } catch (PDOException $ex) {
                // 23000 = duplicate entry (two people registered the same name at once)
                if ($ex->getCode() === '23000') {
                    $errors[] = 'That username is already taken. Please choose another one.';
                } else {
                    $errors[] = 'Something went wrong. Please try again.';
                }
            }
        }
    }
}

$page_title = 'Register';
require_once __DIR__ . '/includes/header.php';
?>

<div class="card shadow-sm auth-card">
    <div class="card-body p-4">
        <h1 class="h4 mb-1"><i class="bi bi-person-plus"></i> Create an account</h1>
        <p class="text-muted">Register to start tracking your expenses.</p>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="register.php" autocomplete="off">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= e($username) ?>" minlength="3" maxlength="30" required autofocus>
                <div class="form-text">3-30 characters: letters, numbers, underscore.</div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password"
                       minlength="8" maxlength="72" required>
                <div class="form-text">At least 8 characters.</div>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm password</label>
                <input type="password" class="form-control" id="confirm_password"
                       name="confirm_password" minlength="8" maxlength="72" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>

        <p class="text-center mt-3 mb-0">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
