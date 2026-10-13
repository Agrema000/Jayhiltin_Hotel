<?php
// staff/settings.php — Staff Profile & Account Settings
require_once '../includes/staff_auth.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

$message = '';
$error   = '';

// Get logged-in staff ID
$staff_id = $_SESSION['staff_id'] ?? $_SESSION['user_id'] ?? 0;

// Fetch current staff member profile from database
$staff = null;
if ($staff_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch();
    } catch (Exception $e) {
        $staff = null;
    }
}

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');

    if (empty($full_name) || empty($email)) {
        $error = "Full Name and Email are required fields.";
    } else {
        try {
            $u_stmt = $pdo->prepare("UPDATE staff SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $u_stmt->execute([$full_name, $email, $phone, $staff_id]);
            
            // Sync Session Name
            $_SESSION['staff_name'] = $full_name;
            $message = "Profile details updated successfully!";

            // Refresh staff variable
            $staff['full_name'] = $full_name;
            $staff['email']     = $email;
            $staff['phone']     = $phone;
        } catch (Exception $e) {
            $error = "Failed to update profile. Email may already be in use.";
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        // Verify current password
        if ($staff && password_verify($current_password, $staff['password'])) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            try {
                $p_stmt = $pdo->prepare("UPDATE staff SET password = ? WHERE id = ?");
                $p_stmt->execute([$hashed, $staff_id]);
                $message = "Password changed successfully!";
            } catch (Exception $e) {
                $error = "Failed to update password.";
            }
        } else {
            $error = "Incorrect current password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Settings — Jay Hiltin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="staff-body">
    <button class="menu-toggle" aria-label="Open menu">☰</button>
<div class="sidebar-overlay"></div>
<div class="staff-layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-monogram small">JH</div>
            <div>
                <strong>JAY HILTIN</strong>
                <span>HOTEL &amp; SUITES</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="search.php">🔍 Guest Search</a>
            <a href="bookings.php">🛏️ Bookings</a>
            <a href="loyalty.php">🎁 Loyalty Program</a>
            <a href="reports.php">📊 Reports</a>
            <a href="settings.php" class="active">⚙️ Settings</a>
        </nav>
        <a href="logout.php" class="sidebar-logout">⬅ Log Out</a>
    </aside>

    <!-- MAIN -->
    <main class="staff-main">

        <h2 style="font-size: 22px; font-weight: 800; color: #0B2B21; margin-bottom: 20px;">Staff Account Settings</h2>

        <?php if ($message): ?>
            <div style="background: #ECFDF5; color: #065F46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #A7F3D0;">
                ✓ <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #FEE2E2; color: #991B1B; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #FCA5A5;">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
            
            <!-- 1. Profile Information Card -->
            <div style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
                <h3 style="font-size: 16px; font-weight: 700; color: #0B2B21; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
                    👤 Personal Information
                </h3>

                <form method="POST" action="settings.php" style="display: flex; flex-direction: column; gap: 16px;">
                    <input type="hidden" name="action" value="update_profile">

                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #4B5563; display: block; margin-bottom: 6px;">Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($staff['full_name'] ?? $_SESSION['staff_name'] ?? ''); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                    </div>

                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #4B5563; display: block; margin-bottom: 6px;">Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($staff['email'] ?? ''); ?>" required style="width: 100%; padding: 10px 12px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                    </div>

                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #4B5563; display: block; margin-bottom: 6px;">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($staff['phone'] ?? ''); ?>" placeholder="e.g. 08012345678" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                    </div>

                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #4B5563; display: block; margin-bottom: 6px;">Designated Role</label>
                        <input type="text" value="<?php echo htmlspecialchars($staff['role'] ?? 'Front Desk Staff'); ?>" disabled style="width: 100%; padding: 10px 12px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px; background: #F3F4F6; color: #6B7280;">
                    </div>

                    <button type="submit" class="btn-green" style="margin-top: 10px; cursor: pointer; font-size: 14px; padding: 10px;">
                        Save Profile Changes
                    </button>
                </form>
            </div>

            <!-- 2. Security & Password Change Card -->
            <div style="background: #fff; padding: 24px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
                <h3 style="font-size: 16px; font-weight: 700; color: #0B2B21; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
                    🔒 Security & Password
                </h3>

                <form method="POST" action="settings.php" style="display: flex; flex-direction: column; gap: 16px;">
                    <input type="hidden" name="action" value="change_password">

                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #4B5563; display: block; margin-bottom: 6px;">Current Password</label>
                        <input type="password" name="current_password" required style="width: 100%; padding: 10px 12px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                    </div>

                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #4B5563; display: block; margin-bottom: 6px;">New Password</label>
                        <input type="password" name="new_password" required placeholder="At least 6 characters" style="width: 100%; padding: 10px 12px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                    </div>

                    <div>
                        <label style="font-size: 12px; font-weight: 700; color: #4B5563; display: block; margin-bottom: 6px;">Confirm New Password</label>
                        <input type="password" name="confirm_password" required style="width: 100%; padding: 10px 12px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px;">
                    </div>

                    <button type="submit" class="btn-green" style="margin-top: 10px; cursor: pointer; font-size: 14px; padding: 10px;">
                        Update Password
                    </button>
                </form>
            </div>

        </div>

    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>