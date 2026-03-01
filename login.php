<?php
/* Enforce HTTPS connection for security */
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}
require_once 'php/db_connection.php';
$error   = '';
$success = '';
/* Retrieve remembered email from cookie */
$remembered_email = isset($_COOKIE['remember_email'])
    ? htmlspecialchars($_COOKIE['remember_email']) : '';
/* Process login form submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';
    $remember_me  = isset($_POST['remember_me']);
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $sql = "SELECT user_id, full_name, email, password, role
FROM users WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) === 1) {
                mysqli_stmt_bind_result(
                    $stmt,
                    $user_id,
                    $full_name,
                    $db_email,
                    $hashed_pw,
                    $role
                );
                mysqli_stmt_fetch($stmt);
                if ($hashed_pw && password_verify($password, $hashed_pw)) {
                    /* Set remember me cookie for 30 days */
                    if ($remember_me) {
                        setcookie(
                            'remember_email',
                            $email,
                            time() + 30 * 86400,
                            '/',
                            '',
                            true,
                            true
                        );
                    } else {
                        setcookie('remember_email', '', time() - 3600, '/');
                    }
                    /* Initialize user session */
                    session_regenerate_id(true);
                    $_SESSION['user_id']   = $user_id;
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['email']     = $db_email;
                    $_SESSION['role']      = $role;
                    /* Sync cart from database */
                    if ($role !== 'admin') {
                        require_once 'php/sync_cart.php';
                        loadCartFromDb($conn, $user_id);
                    }
                    /* Redirect based on user role */
                    header('Location: ' . ($role === 'admin'
                        ? 'admin/dashboard.php' : 'index.php'));
                    exit();
                } else {
                    $error = "Invalid credentials. Please try again.";
                }
            } else {
                $error = "Invalid credentials. Please try again.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "Something went wrong. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Purge Coffee</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="login-container">
        <a href="index.php" class="back-home">
            <i class="bi bi-arrow-left"></i>
            <span>Back to Home</span>
        </a>
        <img src="images/coffee_beans_logo.png" alt="Purge Coffee" class="logo">
        <div class="login-header">
            <h1 class="login-title">Welcome back to Purge Coffee!</h1>
            <p class="login-subtitle">Log in to access your favorites and recent orders</p>
        </div>
        <!-- Display error messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <!-- Display success messages -->
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
            novalidate id="loginForm">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" required
                    placeholder="Enter your email address"
                    value="<?= $remembered_email ?>"
                    autocomplete="email">
                <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-group password-group">
                    <input type="password" id="password" name="password"
                        class="form-control" required
                        placeholder="Enter your password"
                        autocomplete="current-password">

                    <button type="button" class="btn-eye" id="togglePassword"
                        aria-label="Show password">
                        <i class="bi bi-eye-slash" id="eyeIcon"></i>
                    </button>
                </div>
                <div class="invalid-feedback">Password is required.</div>
            </div>
            <div class="form-extras">
                <label class="remember-label">
                    <input type="checkbox" name="remember_me" id="remember_me"
                        <?= $remembered_email ? 'checked' : '' ?>>
                    Remember me
                </label>
                <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
        <a href="register.php" class="auth-link">
            Don't have an account? <span>Register here</span>
        </a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /* Toggle password visibility */
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
                this.setAttribute('aria-label', 'Hide password');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
                this.setAttribute('aria-label', 'Show password');
            }
        });
        /* Validate form inputs */
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            let valid = true;
            const email = document.getElementById('email');
            const pwd = document.getElementById('password');
            const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            [email, pwd].forEach(f => f.classList.remove('is-invalid'));
            if (!emailRe.test(email.value.trim())) {
                email.classList.add('is-invalid');
                valid = false;
            }
            if (!pwd.value) {
                pwd.classList.add('is-invalid');
                valid = false;
            }
            if (!valid) e.preventDefault();
        });
    </script>
</body>

</html>