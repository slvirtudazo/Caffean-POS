<?php
/**
 * Purge Coffee Shop - User Registration Page
 * This page handles new customer registration with validation and password hashing
 */

require_once 'php/db_connection.php';

$error = '';
$success = '';

// Process registration when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $full_name = $first_name . ' ' . $last_name;
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate all required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password)) {
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
    <link rel="stylesheet" href="css/register.css">
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
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" name="first_name" class="form-control" required 
                           placeholder="First name">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" name="last_name" class="form-control" required 
                           placeholder="Last name">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required 
                       placeholder="Enter your email address">
            </div>
            
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required 
                       placeholder="Create a password">
            </div>
            
            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required 
                       placeholder="Confirm your password">
            </div>
            
            <button type="submit" class="btn-register">Register</button>
        </form>
        
        <a href="login.php" class="auth-link">
            Already have an account? <span>Login here</span>
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>