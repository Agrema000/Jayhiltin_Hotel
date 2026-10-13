<?php
// guest/register.php
require_once '../config/db.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($full_name) || empty($phone) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } else {
        $check = $pdo->prepare("SELECT id FROM guests WHERE phone = ? OR email = ?");
        $check->execute([$phone, $email]);
        
        if ($check->rowCount() > 0) {
            $error = 'A guest account with this phone number or email already exists.';
        } else {
            $photo_name = null;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                
                if (in_array($ext, $allowed)) {
                    $target_dir = __DIR__ . '/../assets/uploads/';
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }
                    $photo_name = 'guest_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    move_uploaded_file($_FILES['photo']['tmp_name'], $target_dir . $photo_name);
                }
            }

            // Generate next available member ID without infinite looping
            $stmtCount = $pdo->query("SELECT MAX(id) FROM guests");
            $maxId = (int)$stmtCount->fetchColumn();
            $nextNum = $maxId + 1;

            do {
                $member_id = 'JH-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);
                
                $checkID = $pdo->prepare("SELECT COUNT(*) FROM guests WHERE member_id = ?");
                $checkID->execute([$member_id]);
                $idExists = (int)$checkID->fetchColumn();

                if ($idExists > 0) {
                    $nextNum++; // Increment to next number if this ID is already taken
                }
            } while ($idExists > 0);

            $hashed_pass = password_hash($password, PASSWORD_BCRYPT);

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO guests (member_id, full_name, phone, email, password, photo, points, membership_level) 
                    VALUES (?, ?, ?, ?, ?, ?, 0, 'Bronze')
                ");
                
                if ($stmt->execute([$member_id, $full_name, $phone, $email, $hashed_pass, $photo_name])) {
                    $success = "Account created successfully! Your Member ID is <strong>{$member_id}</strong>. You can now log in.";
                } else {
                    $error = 'An error occurred while creating your account. Please try again.';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'An account with this email or phone already exists.';
                } else {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Jay Hiltin Rewards</title>
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

    <h2>Join Jay Hiltin Rewards</h2>
    <p class="auth-sub">Create your free account and start earning points today.</p>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <div style="text-align: center; margin-top: 15px;">
            <a href="login.php" class="btn-gold btn-block">Log In Now</a>
        </div>
    <?php else: ?>
        <form action="register.php" method="POST" enctype="multipart/form-data">
            <div class="form-group no-icon">
                <label class="field-label">Full Name *</label>
                <input type="text" name="full_name" required placeholder="e.g. Sora Sofia" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>

            <div class="form-group no-icon">
                <label class="field-label">Phone Number *</label>
                <input type="tel" name="phone" required placeholder="e.g. 08012345678" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
            </div>

            <div class="form-group no-icon">
                <label class="field-label">Email Address *</label>
                <input type="email" name="email" required placeholder="e.g. sora@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group no-icon">
                <label class="field-label">Password *</label>
                <input type="password" name="password" required placeholder="Create a strong password">
            </div>

            <div class="form-group no-icon">
                <label class="field-label">Upload Profile Photo</label>
                <input type="file" name="photo" accept="image/*">
                <p class="field-hint">(Optional)</p>
            </div>

            <button type="submit" class="btn-gold btn-block">Create Account</button>
        </form>

        <p class="auth-switch">
            Already a member? <a href="login.php">Log In</a>
        </p>
    <?php endif; ?>
</div>

</body>
</html>