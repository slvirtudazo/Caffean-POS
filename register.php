<?php

/**
 * Purge Coffee Shop — Customer Registration Page
 * Features: field validation, password strength check, password_hash(),
 * show/hide password, password match, role-based restriction, HTTPS enforcement.
 * Admin accounts are managed outside this system.
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
    $first_name       = trim($_POST['first_name']       ?? '');
    $last_name        = trim($_POST['last_name']        ?? '');
    $full_name        = $first_name . ' ' . $last_name;
    $email            = trim($_POST['email']            ?? '');
    $password         = $_POST['password']              ?? '';
    $confirm_password = $_POST['confirm_password']      ?? '';
    $role             = $_POST['role']                  ?? '';

    // ── Server-side validation ─────────────────────────────────────
    if (
        empty($first_name) || empty($last_name) || empty($email)
        || empty($password) || empty($confirm_password)
    ) {
        $error = "All fields are required.";
    } elseif (
        !preg_match('/^[a-zA-Z\s\-\']{2,50}$/', $first_name)
        || !preg_match('/^[a-zA-Z\s\-\']{2,50}$/', $last_name)
    ) {
        $error = "Names must be 2–50 letters and may contain hyphens or apostrophes.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number.";
    } elseif (!preg_match('/[\W_]/', $password)) {
        $error = "Password must contain at least one special character (e.g. @, #, !).";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (!in_array($role, ['customer', 'admin'])) {
        $error = "Please select a valid role.";
    } elseif ($role === 'admin') {
        $error = "Admin accounts cannot be registered here. Please contact your system administrator.";
    } else {
        // ── Check duplicate email ──────────────────────────────────
        $check = mysqli_prepare(
            $conn,
            "SELECT user_id FROM users WHERE email = ?"
        );
        mysqli_stmt_bind_param($check, 's', $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "This email address is already registered.";
        } else {
            // ── Hash password & insert ─────────────────────────────
            $hashed_pw = password_hash(
                $password,
                PASSWORD_BCRYPT,
                ['cost' => 12]
            );

            $ins = mysqli_prepare(
                $conn,
                "INSERT INTO users (full_name, email, password, role)
                 VALUES (?, ?, ?, 'customer')"
            );
            mysqli_stmt_bind_param($ins, 'sss', $full_name, $email, $hashed_pw);

            if (mysqli_stmt_execute($ins)) {
                $success = "Registration successful! Redirecting to login…";
                header('refresh:2;url=login.php');
            } else {
                $error = "Something went wrong. Please try again.";
            }
            mysqli_stmt_close($ins);
        }
        mysqli_stmt_close($check);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Purge Coffee</title>
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/register.css?v=<?php echo time(); ?>">
</head>

<body>

    <div class="register-container">
        <a href="index.php" class="back-home">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Home</span>
        </a>
        <img src="images/coffee_beans_logo.png" alt="Purge Coffee" class="logo">

        <div class="register-header">
            <h1 class="register-title">Welcome to Purge Coffee!</h1>
            <p class="register-subtitle">Join our coffee community</p>
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
            novalidate id="registerForm">

            <!-- Name row -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name"
                        class="form-control" required
                        placeholder="First name"
                        autocomplete="given-name">
                    <div class="invalid-feedback">Enter a valid first name (letters only).</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name"
                        class="form-control" required
                        placeholder="Last name"
                        autocomplete="family-name">
                    <div class="invalid-feedback">Enter a valid last name (letters only).</div>
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" id="email" name="email"
                    class="form-control" required
                    placeholder="Enter your email address"
                    autocomplete="email">
                <div class="invalid-feedback">Please enter a valid email address.</div>
            </div>

            <!-- Password + show/hide + strength -->
            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-group password-group">
                    <input type="password" id="password" name="password"
                        class="form-control" required
                        placeholder="Create a password"
                        autocomplete="new-password"
                        oninput="checkStrength(this.value)">
                    <button type="button" class="btn-eye" id="togglePassword"
                        aria-label="Show password">
                        <i class="fas fa-eye" id="eyeIcon1"></i>
                    </button>
                </div>
                <div class="invalid-feedback">Password is required.</div>

                <!-- Strength bar -->
                <div class="strength-bar mt-2">
                    <div class="strength-fill" id="strengthFill"></div>
                </div>
                <small class="strength-text" id="strengthText"></small>

                <!-- Requirements checklist -->
                <ul class="pwd-req" id="pwdReq">
                    <li id="req-len"> <i class="fas fa-circle-xmark"></i> At least 8 characters</li>
                    <li id="req-upper"><i class="fas fa-circle-xmark"></i> One uppercase letter</li>
                    <li id="req-lower"><i class="fas fa-circle-xmark"></i> One lowercase letter</li>
                    <li id="req-num"> <i class="fas fa-circle-xmark"></i> One number</li>
                    <li id="req-sym"> <i class="fas fa-circle-xmark"></i> One special character (@#!…)</li>
                </ul>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <div class="input-group password-group">
                    <input type="password" id="confirm_password" name="confirm_password"
                        class="form-control" required
                        placeholder="Confirm your password"
                        autocomplete="new-password"
                        oninput="checkMatch()">
                    <button type="button" class="btn-eye" id="toggleConfirm"
                        aria-label="Show confirm password">
                        <i class="fas fa-eye" id="eyeIcon2"></i>
                    </button>
                </div>
                <div class="invalid-feedback" id="confirmFeedback">Passwords do not match.</div>
                <small class="match-ok" id="matchOk">
                    <i class="fas fa-circle-check"></i> Passwords match!
                </small>
            </div>

            <!-- Role Selection -->
            <div class="form-group">
                <label class="form-label">Register as</label>
                <div class="role-group">
                    <label class="role-option">
                        <input type="radio" name="role" value="customer" checked>
                        <span class="role-box">
                            <i class="bi bi-person role-icon-outline"></i>
                            <i class="bi bi-person-fill role-icon-filled"></i>
                            Customer
                        </span>
                    </label>
                    <label class="role-option">
                        <input type="radio" name="role" value="admin">
                        <span class="role-box">
                            <i class="bi bi-shield role-icon-outline"></i>
                            <i class="bi bi-shield-fill role-icon-filled"></i>
                            Admin
                        </span>
                    </label>
                </div>
                <div class="invalid-feedback" id="roleFeedback">Please select a role.</div>
            </div>

            <button type="submit" class="btn-register">Register</button>
        </form>

        <a href="login.php" class="auth-link">
            Already have an account? <span>Login here</span>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── Show/Hide passwords ───────────────────────────────────────────
        function toggleEye(btnId, fieldId, iconId) {
            document.getElementById(btnId).addEventListener('click', function() {
                const f = document.getElementById(fieldId);
                const i = document.getElementById(iconId);
                const show = f.type === 'password';
                f.type = show ? 'text' : 'password';
                i.classList.toggle('fa-eye', !show);
                i.classList.toggle('fa-eye-slash', show);
                this.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        }
        toggleEye('togglePassword', 'password', 'eyeIcon1');
        toggleEye('toggleConfirm', 'confirm_password', 'eyeIcon2');

        // ── Password strength ─────────────────────────────────────────────
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
                if (ok) {
                    li.classList.add('req-ok');
                    icon.className = 'fas fa-circle-check';
                    score++;
                } else {
                    li.classList.remove('req-ok');
                    icon.className = 'fas fa-circle-xmark';
                }
            }
            const fill = document.getElementById('strengthFill');
            const txt = document.getElementById('strengthText');
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
            fill.style.width = lv.pct + '%';
            fill.className = 'strength-fill ' + lv.cls;
            txt.textContent = lv.label;
            txt.className = 'strength-text ' + lv.cls;
        }

        // ── Password match ────────────────────────────────────────────────
        function checkMatch() {
            const p = document.getElementById('password').value;
            const c = document.getElementById('confirm_password');
            const fb = document.getElementById('confirmFeedback');
            const ok = document.getElementById('matchOk');
            if (c.value === '') {
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

        // ── Form submit validation ────────────────────────────────────────
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let valid = true;
            const nameRe = /^[a-zA-Z\s\-']{2,50}$/;
            const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            const fields = {
                first_name: f => nameRe.test(f.value.trim()),
                last_name: f => nameRe.test(f.value.trim()),
                email: f => emailRe.test(f.value.trim()),
                password: f => f.value.length >= 8 &&
                    /[A-Z]/.test(f.value) &&
                    /[a-z]/.test(f.value) &&
                    /[0-9]/.test(f.value) &&
                    /[\W_]/.test(f.value),
                confirm_password: f => f.value === document.getElementById('password').value &&
                    f.value !== ''
            };

            for (const [name, fn] of Object.entries(fields)) {
                const f = document.getElementById(name) ||
                    document.querySelector(`[name="${name}"]`);
                if (!f) continue;
                if (!fn(f)) {
                    f.classList.add('is-invalid');
                    valid = false;
                } else f.classList.remove('is-invalid');
            }

            // ── Role validation ───────────────────────────────────────────
            const role = document.querySelector('input[name="role"]:checked');
            const roleFb = document.getElementById('roleFeedback');
            if (!role) {
                roleFb.textContent = 'Please select a role.';
                roleFb.style.display = 'block';
                valid = false;
            } else if (role.value === 'admin') {
                roleFb.textContent = 'Admin accounts cannot be registered here. Please contact your system administrator.';
                roleFb.style.display = 'block';
                valid = false;
            } else {
                roleFb.style.display = 'none';
            }

            if (!valid) e.preventDefault();
        });
    </script>
</body>

</html>