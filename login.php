<?php
/**
 * Purge Coffee Shop - Login Page
 * This page handles user authentication, allowing customers and administrators
 * to log into the system. It validates credentials against the database
 * and creates appropriate session variables upon successful authentication.
 */

require_once 'php/db_connection.php';

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
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    
    <div class="login-container">
        <a href="index.php" class="back-home">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Home</span>
        </a>
        
        <img src="images/coffee_beans_logo.png" alt="Purge Coffee" class="logo">
        
        <div class="login-header">
            <h1 class="login-title">Welcome back to Purge Coffee!</h1>
            <p class="login-subtitle">Access your favorites and recent orders</p>
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
                       placeholder="Enter your email address">
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required 
                       placeholder="Enter your password">
            </div>
            
            <button type="submit" class="btn-login">Login</button>
        </form>
        
        <a href="register.php" class="auth-link">
            Don't have an account? <span>Register here</span>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>