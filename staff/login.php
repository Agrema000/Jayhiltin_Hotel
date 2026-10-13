<?php
// staff/login.php
session_start();

// Already logged in? Skip straight to dashboard
if (isset($_SESSION['staff_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE username = ?");
        $stmt->execute([$username]);
        $staff = $stmt->fetch();

        if ($staff && password_verify($password, $staff['password'])) {
            session_regenerate_id(true);   // prevent session fixation attacks
            $_SESSION['staff_id']  = $staff['id'];
            $_SESSION['staff_name'] = $staff['username'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal — Jay Hiltin Hotel & Suites</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="staff-login-body">
    <div class="staff-login-card">
        <div class="brand-center">
            <div class="brand-monogram">JH</div>
            <h1>JAY HILTIN</h1>
            <p class="hotel-sub">HOTEL &amp; SUITES</p>
        </div>

        <h2>Staff Portal</h2>
        <p class="secure-note">Secure access for authorized personnel only.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <span class="input-icon">👤</span>
                <input type="text" name="username" placeholder="Enter your username" required>
            </div>
            <div class="form-group">
                <span class="input-icon">🔒</span>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-gold btn-block">Login</button>
        </form>

        <p class="footer-note">© 2026 Jay Hiltin Hotel & Suites. All rights reserved.</p>
    </div>
</body>
</html>