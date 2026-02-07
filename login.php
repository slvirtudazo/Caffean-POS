<?php
/**
 * Purge Coffee Shop - Login Page
 * This page handles user authentication, allowing customers and administrators
 * to log into the system. It validates credentials against the database
 * and creates appropriate session variables upon successful authentication.
 */

require_once 'php/config.php';

$error = '';
$success = '';

// Process login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Check if email and password are provided
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Query to find user by email
        $sql = "SELECT user_id, full_name, email, password, role FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $user_id, $full_name, $db_email, $hashed_password, $role);
                    
                    if (mysqli_stmt_fetch($stmt)) {
                        // Verify password
                        if ($hashed_password !== null && password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['full_name'] = $full_name;
                            $_SESSION['email'] = $db_email;
                            $_SESSION['role'] = $role;
                            
                            // Redirect based on user role
                            if ($role == 'admin') {
                                header("location: admin/dashboard.php");
                            } else {
                                header("location: index.php");
                            }
                            exit();
                        } else {
                            $error = "Invalid email or password.";
                        }
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Something went wrong. Please try again later.";
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
    <title>Login - Purge Coffee</title>
    
    <link rel="icon" type="image/png" href="images/coffee_beans_logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
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
        
        .btn-login {
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
        
        .btn-login:hover {
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
                <h1 class="auth-title">Welcome Back</h1>
                <p>Login to your account</p>
            </div>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required 
                           placeholder="Enter your email">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required 
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="btn-login">Login</button>
            </form>
            
            <div class="auth-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
</body>
</html>