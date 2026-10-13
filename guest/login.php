<?php
// guest/login.php
require_once '../config/db.php';
require_once '../includes/functions.php';

$error = '';

if (isset($_SESSION['guest_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password    = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $error = 'Please enter your account details and password.';
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM guests 
            WHERE email = ? OR phone = ? OR member_id = ?
        ");
        $stmt->execute([$login_input, $login_input, $login_input]);
        $guest = $stmt->fetch();

        if ($guest && password_verify($password, $guest['password'])) {
            $_SESSION['guest_id'] = $guest['id'];
            $_SESSION['guest_name'] = $guest['full_name'];
            
            updateMembershipLevel($pdo, $guest['id']);
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid login credentials. Please check your details and try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guest Login | Jay Hiltin Rewards</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-body">

<div class="auth-card">
    <div class="auth-brand">
        <div class="brand-monogram">JH</div>
        <h1>JAY HILTIN</h1>
        <p class="hotel-sub">HOTEL & SUITES</p>
    </div>

    <h2>Guest Portal Login</h2>
    <p class="auth-sub">Access your points, rewards, and booking history.</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group no-icon">
            <label class="field-label">Email, Phone, or Member ID</label>
            <input type="text" name="login_input" required placeholder="e.g. 08012345678 or JH-000124" value="<?php echo htmlspecialchars($_POST['login_input'] ?? ''); ?>">
        </div>

        <div class="form-group no-icon">
            <label class="field-label">Password</label>
            <input type="password" name="password" required placeholder="Enter your password">
        </div>

        <button type="submit" class="btn-gold btn-block">Log In</button>
    </form>

    <p class="auth-switch">
        Not a member yet? <a href="register.php">Join Jay Hiltin Rewards</a>
    </p>
</div>

</body>
</html>