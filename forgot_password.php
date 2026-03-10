<?php

/**
 * Caffean Shop — Forgot Password Page
 * Allows a registered customer to reset their password directly
 * by verifying their email and setting a new password.
 *
 * NOTE: For production, replace direct reset with a secure
 * email-token flow (e.g. PHPMailer + signed token table).
 */

// ── HTTPS enforcement ──────────────────────────────────────────────
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

require_once 'php/db_connection.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email           = trim($_POST['email']            ?? '');
    $new_password    = $_POST['new_password']           ?? '';
    $confirm_password = $_POST['confirm_password']       ?? '';

    // ── Validate fields ────────────────────────────────────────────
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $error = "Password must contain at least one number.";
    } elseif (!preg_match('/[\W_]/', $new_password)) {
        $error = "Password must contain at least one special character (@, #, !, etc.).";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // ── Check email exists (customers only) ────────────────────
        $stmt = mysqli_prepare(
            $conn,
            "SELECT user_id FROM users WHERE email = ? AND role = 'customer'"
        );
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) === 1) {
            $hashed_pw = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);

            $upd = mysqli_prepare(
                $conn,
                "UPDATE users SET password = ? WHERE email = ? AND role = 'customer'"
            );
            mysqli_stmt_bind_param($upd, 'ss', $hashed_pw, $email);

            if (mysqli_stmt_execute($upd)) {
                $success = "Password updated successfully! Redirecting to login…";
                header('refresh:2;url=login.php');
            } else {
                $error = "Something went wrong. Please try again.";
            }
            mysqli_stmt_close($upd);
        } else {
            // Generic message — do not reveal whether email exists
            $error = "If that email is registered, your password has been updated.";
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Caffean</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/forgot_password.css?v=<?php echo time(); ?>">
</head>

<body>

    <div class="fp-container">
        <a href="login.php" class="back-home">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Login</span>
        </a>
        <img src="images/coffee_beans_logo.png" alt="Caffean" class="logo">

        <div class="fp-header">
            <h1 class="fp-title">Reset Your Password</h1>
            <p class="fp-subtitle">Enter your email and choose a new password</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
            novalidate id="fpForm">

            <!-- Email -->
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email"
                    class="form-control" required
                    placeholder="Enter your registered email"
                    autocomplete="email">
                <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>

            <!-- New Password + show/hide + strength -->
            <div class="form-group">
                <label class="form-label" for="new_password">Create New Password</label>
                <div class="input-group password-group">
                    <input type="password" id="new_password" name="new_password"
                        class="form-control" required
                        placeholder="Create your new password"
                        autocomplete="new-password"
                        oninput="checkStrength(this.value)">
                    <button type="button" class="btn-eye" id="toggleNew"
                        aria-label="Show password">
                        <i class="bi bi-eye-slash" id="eyeIcon1"></i>
                    </button>
                </div>
                <div class="invalid-feedback">Password does not meet requirements.</div>

                <div class="strength-bar mt-2">
                    <div class="strength-fill" id="strengthFill"></div>
                </div>
                <small class="strength-text" id="strengthText"></small>

                <ul class="pwd-req" id="pwdReq">
                    <li id="req-len"> <i class="bi bi-x-circle"></i> At least 8 characters</li>
                    <li id="req-upper"><i class="bi bi-x-circle"></i> One uppercase letter</li>
                    <li id="req-lower"><i class="bi bi-x-circle"></i> One lowercase letter</li>
                    <li id="req-num"> <i class="bi bi-x-circle"></i> One number</li>
                    <li id="req-sym"> <i class="bi bi-x-circle"></i> One special character (@#!…)</li>
                </ul>
            </div>

            <!-- Confirm New Password -->
            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm New Password</label>
                <div class="input-group password-group">
                    <input type="password" id="confirm_password" name="confirm_password"
                        class="form-control" required
                        placeholder="Confirm your new password"
                        autocomplete="new-password"
                        oninput="checkMatch()">
                    <button type="button" class="btn-eye" id="toggleConfirm"
                        aria-label="Show confirm password">
                        <i class="bi bi-eye-slash" id="eyeIcon2"></i>
                    </button>
                </div>
                <div class="invalid-feedback" id="confirmFeedback">Passwords do not match.</div>
                <small class="match-ok" id="matchOk">
                    <i class="bi bi-check-circle"></i> Passwords match!
                </small>
            </div>

            <button type="submit" class="btn-fp">Reset Password</button>
        </form>

        <div class="auth-link-wrapper">
            <a href="login.php" class="auth-link">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Login</span>
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── Show/Hide ─────────────────────────────────────────────────────
        function toggleEye(btnId, fieldId, iconId) {
            document.getElementById(btnId).addEventListener('click', function() {
                const f = document.getElementById(fieldId);
                const i = document.getElementById(iconId);
                const show = f.type === 'password';
                f.type = show ? 'text' : 'password';
                i.className = show ? 'bi bi-eye' : 'bi bi-eye-slash';
                this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        }
        toggleEye('toggleNew', 'new_password', 'eyeIcon1');
        toggleEye('toggleConfirm', 'confirm_password', 'eyeIcon2');

        // ── Strength ──────────────────────────────────────────────────────
        function checkStrength(val) {
            const rules = {
                'req-len': val.length >= 8,
                'req-upper': /[A-Z]/.test(val),
                'req-lower': /[a-z]/.test(val),
                'req-num': /[0-9]/.test(val),
                'req-sym': /[\W_]/.test(val)
            };
            let score = 0;
            for (const [id, ok] of Object.entries(rules)) {
                const li = document.getElementById(id);
                const icon = li.querySelector('i');
                ok ? (li.classList.add('req-ok'), icon.className = 'bi bi-check-circle', score++) :
                    (li.classList.remove('req-ok'), icon.className = 'bi bi-x-circle');
            }
            const levels = [{
                    pct: 0,
                    cls: '',
                    label: ''
                },
                {
                    pct: 20,
                    cls: 'weak',
                    label: 'Weak'
                },
                {
                    pct: 40,
                    cls: 'fair',
                    label: 'Fair'
                },
                {
                    pct: 60,
                    cls: 'good',
                    label: 'Good'
                },
                {
                    pct: 80,
                    cls: 'strong',
                    label: 'Strong'
                },
                {
                    pct: 100,
                    cls: 'very-strong',
                    label: 'Very Strong'
                }
            ];
            const lv = levels[score];
            const fill = document.getElementById('strengthFill');
            const txt = document.getElementById('strengthText');
            fill.style.width = lv.pct + '%';
            fill.className = 'strength-fill ' + lv.cls;
            txt.textContent = lv.label;
            txt.className = 'strength-text ' + lv.cls;
        }

        // ── Match ─────────────────────────────────────────────────────────
        function checkMatch() {
            const p = document.getElementById('new_password').value;
            const c = document.getElementById('confirm_password');
            const fb = document.getElementById('confirmFeedback');
            const ok = document.getElementById('matchOk');
            if (!c.value) {
                c.classList.remove('is-invalid', 'is-valid');
                ok.style.display = 'none';
                return;
            }
            const match = p === c.value;
            c.classList.toggle('is-invalid', !match);
            c.classList.toggle('is-valid', match);
            fb.style.display = match ? 'none' : 'block';
            ok.style.display = match ? 'block' : 'none';
        }

        // ── Submit validation ─────────────────────────────────────────────
        document.getElementById('fpForm').addEventListener('submit', function(e) {
            let valid = true;
            const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const email = document.getElementById('email');
            const pwd = document.getElementById('new_password');
            const conf = document.getElementById('confirm_password');

            if (!emailRe.test(email.value.trim())) {
                email.classList.add('is-invalid');
                valid = false;
            } else email.classList.remove('is-invalid');

            const strongPwd = pwd.value.length >= 8 &&
                /[A-Z]/.test(pwd.value) && /[a-z]/.test(pwd.value) &&
                /[0-9]/.test(pwd.value) && /[\W_]/.test(pwd.value);
            if (!strongPwd) {
                pwd.classList.add('is-invalid');
                valid = false;
            } else pwd.classList.remove('is-invalid');

            if (conf.value !== pwd.value || !conf.value) {
                conf.classList.add('is-invalid');
                valid = false;
            } else conf.classList.remove('is-invalid');

            if (!valid) e.preventDefault();
        });
    </script>
</body>

</html>