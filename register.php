<?php
/**
 * Purge Coffee Shop - User Registration Page
 * This page handles new customer registration with validation and password hashing
 */

require_once 'php/config.php';

$error = '';
$success = '';

// Process registration when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate all required fields
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check_email = "SELECT user_id FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $check_email)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = "This email is already registered.";
            } else {
                // Hash password for security
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user into database
                $insert_sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'customer')";
                
                if ($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
                    mysqli_stmt_bind_param($insert_stmt, "sss", $full_name, $email, $hashed_password);
                    
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $success = "Registration successful! Redirecting to login...";
                        header("refresh:2;url=login.php");
                    } else {
                        $error = "Something went wrong. Please try again.";
                    }
                    mysqli_stmt_close($insert_stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Purge Coffee</title>
    
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        /* Styling for authentication pages - consistent with login page */
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-xl) var(--spacing-md);
            background: linear-gradient(135deg, var(--ivory-cream) 0%, var(--warm-sand) 100%);
        }
        
        .auth-card {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            padding: var(--spacing-xxl);
            max-width: 450px;
            width: 100%;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }
        
        .auth-logo {
            width: 80px;
            margin-bottom: var(--spacing-md);
        }
        
        .auth-title {
            font-family: var(--font-heading);
            font-size: 2rem;
            color: var(--deep-maroon);
            margin-bottom: var(--spacing-sm);
        }
        
        .form-group {
            margin-bottom: var(--spacing-md);
        }
        
        .form-label {
            font-family: var(--font-subheading);
            font-weight: 600;
            color: var(--dark-brown);
            margin-bottom: var(--spacing-xs);
            display: block;
        }
        
        .form-control {
            width: 100%;
            padding: var(--spacing-md);
            border: 2px solid var(--warm-sand);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: var(--transition-fast);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--burgundy-wine);
        }
        
        .btn-register {
            width: 100%;
            background-color: var(--deep-maroon);
            color: var(--ivory-cream);
            padding: var(--spacing-md);
            border: none;
            border-radius: var(--radius-md);
            font-family: var(--font-subheading);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition-normal);
            margin-top: var(--spacing-md);
        }
        
        .btn-register:hover {
            background-color: var(--burgundy-wine);
            transform: translateY(-2px);
        }
        
        .auth-link {
            text-align: center;
            margin-top: var(--spacing-md);
            color: var(--dark-brown);
        }
        
        .auth-link a {
            color: var(--burgundy-wine);
            text-decoration: none;
            font-weight: 600;
        }
        
        .auth-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-md);
        }
        
        .alert-danger {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background-color: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        .back-home {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: var(--burgundy-wine);
            text-decoration: none;
            margin-bottom: var(--spacing-md);
            font-weight: 600;
            transition: var(--transition-fast);
        }
        
        .back-home:hover {
            gap: var(--spacing-sm);
        }
    </style>
</head>
<body>
    
    <div class="auth-container">
        <div class="auth-card">
            <a href="index.php" class="back-home">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            
            <div class="auth-header">
                <img src="images/coffee_beans_logo.png" alt="Purge Coffee" class="auth-logo">
                <h1 class="auth-title">Create Account</h1>
                <p>Join Purge Coffee today</p>
            </div>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required 
                           placeholder="Enter your full name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required 
                           placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required 
                           placeholder="Create a password (min 6 characters)">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required 
                           placeholder="Confirm your password">
                </div>
                
                <button type="submit" class="btn-register">Create Account</button>
            </form>
            
            <div class="auth-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>