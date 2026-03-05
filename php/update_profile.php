<?php
/**
 * Purge Coffee — Update Profile Endpoint (php/update_profile.php)
 * Handles two actions via POST field 'action':
 *   'info'     — update full_name, email, mobile_number, optional avatar
 *   'password' — update password only (requires current_password verification)
 * Default (no action) behaves as 'info' for backward compatibility.
 */

require_once 'db_connection.php';

header('Content-Type: application/json');

/* Must be logged in */
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$action  = trim($_POST['action'] ?? 'info');

/* ── PASSWORD CHANGE ─────────────────────────────────────────── */
if ($action === 'password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password']     ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($current_password === '') {
        echo json_encode(['success' => false, 'message' => 'Enter your current password to set a new one.']);
        exit();
    }
    if (strlen($new_password) < 8) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
        exit();
    }
    if ($new_password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
        exit();
    }

    /* Verify current password */
    $pw_stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($pw_stmt, 'i', $user_id);
    mysqli_stmt_execute($pw_stmt);
    $pw_row = mysqli_fetch_assoc(mysqli_stmt_get_result($pw_stmt));
    mysqli_stmt_close($pw_stmt);

    if (!$pw_row || !password_verify($current_password, $pw_row['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit();
    }

    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt   = mysqli_prepare($conn, "UPDATE users SET password=? WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, 'si', $hashed, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    exit();
}

/* ── ACCOUNT INFO UPDATE (action='info' or default) ─────────── */
$full_name         = trim($_POST['full_name']         ?? '');
$email             = trim($_POST['email']             ?? '');
$mobile_number     = trim($_POST['mobile_number']     ?? '');
$house_unit        = trim($_POST['house_unit']        ?? '');
$street_name       = trim($_POST['street_name']       ?? '');
$barangay          = trim($_POST['barangay']          ?? '');
$city_municipality = trim($_POST['city_municipality'] ?? '');
$province          = trim($_POST['province']          ?? '');
$zip_code          = trim($_POST['zip_code']          ?? '');

/* Basic validation */
if ($full_name === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Full name and email are required.']);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit();
}
if ($mobile_number !== '' && !preg_match('/^(\+63|0)[0-9]{10}$/', $mobile_number)) {
    echo json_encode(['success' => false, 'message' => 'Mobile number must be in 09XXXXXXXXX format.']);
    exit();
}
if ($zip_code !== '' && !preg_match('/^\d{4}$/', $zip_code)) {
    echo json_encode(['success' => false, 'message' => 'ZIP code must be 4 digits.']);
    exit();
}

/* Email uniqueness check */
$chk = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ?");
mysqli_stmt_bind_param($chk, 'si', $email, $user_id);
mysqli_stmt_execute($chk);
mysqli_stmt_store_result($chk);
if (mysqli_stmt_num_rows($chk) > 0) {
    mysqli_stmt_close($chk);
    echo json_encode(['success' => false, 'message' => 'Email address is already in use.']);
    exit();
}
mysqli_stmt_close($chk);

/* Handle optional profile image upload */
$profile_image = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $allowed_mime = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo        = finfo_open(FILEINFO_MIME_TYPE);
    $mime         = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_mime)) {
        echo json_encode(['success' => false, 'message' => 'Profile image must be JPEG, PNG, or WebP.']);
        exit();
    }
    if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Profile image must be under 5 MB.']);
        exit();
    }

    $ext        = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $filename   = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
    $upload_dir = '../uploads/avatars/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_dir . $filename)) {
        $profile_image = 'uploads/avatars/' . $filename;
    }
}

/* Build UPDATE query */
if ($profile_image !== null) {
    $sql  = "UPDATE users SET full_name=?, email=?, mobile_number=?,
             profile_image=?, house_unit=?, street_name=?, barangay=?,
             city_municipality=?, province=?, zip_code=? WHERE user_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'ssssssssssi',
        $full_name, $email, $mobile_number, $profile_image,
        $house_unit, $street_name, $barangay,
        $city_municipality, $province, $zip_code, $user_id
    );
} else {
    $sql  = "UPDATE users SET full_name=?, email=?, mobile_number=?,
             house_unit=?, street_name=?, barangay=?,
             city_municipality=?, province=?, zip_code=? WHERE user_id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssssssssi',
        $full_name, $email, $mobile_number,
        $house_unit, $street_name, $barangay,
        $city_municipality, $province, $zip_code, $user_id
    );
}

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['full_name'] = $full_name;
    echo json_encode(['success' => true, 'profile_image' => $profile_image]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);