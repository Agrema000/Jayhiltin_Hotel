<?php
// guest/profile.php
require_once '../includes/header.php';

if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit;
}

$guest_id = $_SESSION['guest_id'];
$message = '';
$error = '';

// Update Profile Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $phone     = trim($_POST['phone']);
    $email     = trim($_POST['email']);

    if (!empty($full_name) && !empty($phone) && !empty($email)) {
        // Handle photo upload if present
        $photo_sql = "";
        $params = [$full_name, $phone, $email];

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            
            if (in_array($ext, $allowed)) {
                $target_dir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                $photo_name = 'guest_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_dir . $photo_name)) {
                    $photo_sql = ", photo = ?";
                    $params[] = $photo_name;
                }
            }
        }

        $params[] = $guest_id;
        $stmt = $pdo->prepare("UPDATE guests SET full_name = ?, phone = ?, email = ? {$photo_sql} WHERE id = ?");
        
        if ($stmt->execute($params)) {
            $message = "Profile details updated successfully.";
            $_SESSION['guest_name'] = $full_name;
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    } else {
        $error = "All profile fields are required.";
    }
}

// Security / Password Update Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass     = $_POST['new_password'];

    $stmt = $pdo->prepare("SELECT password FROM guests WHERE id = ?");
    $stmt->execute([$guest_id]);
    $user = $stmt->fetch();

    if ($user && password_verify($current_pass, $user['password'])) {
        $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE guests SET password = ? WHERE id = ?");
        $update->execute([$hashed, $guest_id]);
        $message = "Password changed successfully!";
    } else {
        $error = "Current password was incorrect.";
    }
}

$guest = getGuestFullProfile($pdo, $guest_id);
$photo_path = getProfilePhotoPath($guest['photo']);
?>

<div class="container py-4" style="max-width: 800px;">

    <?php if ($message): ?>
        <div class="alert alert-success mb-4" style="background: rgba(16,185,129,0.1); border: 1px solid #10B981; color: #065F46; padding: 14px 18px; border-radius: 10px;">
            <i class="fas fa-check-circle" style="margin-right: 8px;"></i> <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error mb-4" style="background: rgba(239,68,68,0.1); border: 1px solid #EF4444; color: #991B1B; padding: 14px 18px; border-radius: 10px;">
            <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Header Card -->
    <div class="card mb-4" style="background: #fff; border-radius: 16px; padding: 24px; border: 1px solid rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
        <div style="display: flex; align-items: center; gap: 18px;">
            <div style="position: relative;">
                <?php if ($photo_path): ?>
                    <img src="../<?php echo $photo_path; ?>" alt="<?php echo htmlspecialchars($guest['full_name']); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #B99A61;">
                <?php else: ?>
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: #0B2B21; color: #B99A61; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 700; border: 3px solid #B99A61;">
                        <?php echo getAvatarInitials($guest['full_name']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <h2 style="font-size: 22px; font-weight: 700; color: #0B2B21; margin: 0;"><?php echo htmlspecialchars($guest['full_name']); ?></h2>
                <p style="color: #666; font-size: 13px; margin: 4px 0 8px 0;">Member ID: <strong><?php echo htmlspecialchars($guest['member_id']); ?></strong></p>
                <?php echo renderTierBadge($guest['membership_level']); ?>
            </div>
        </div>

        <a href="card.php" class="btn-gold" style="padding: 10px 18px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-qrcode"></i> Digital Card
        </a>
    </div>

    <!-- Personal Profile Details Form -->
    <div class="card mb-4" style="background: #fff; border-radius: 16px; padding: 24px; border: 1px solid rgba(0,0,0,0.06);">
        <h3 style="font-size: 18px; font-weight: 700; color: #0B2B21; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-user-edit" style="color: #B99A61;"></i> Account Details
        </h3>

        <form action="profile.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action_update_profile" value="1">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #0B2B21; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Full Name</label>
                    <input type="text" name="full_name" required value="<?php echo htmlspecialchars($guest['full_name']); ?>" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #E5E7EB; font-size: 14px;">
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #0B2B21; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Phone Number</label>
                    <input type="tel" name="phone" required value="<?php echo htmlspecialchars($guest['phone']); ?>" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #E5E7EB; font-size: 14px;">
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #0B2B21; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Email Address</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($guest['email']); ?>" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #E5E7EB; font-size: 14px;">
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #0B2B21; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Update Profile Picture</label>
                    <input type="file" name="photo" accept="image/*" style="width: 100%; padding: 8px; border-radius: 8px; border: 1px solid #E5E7EB; font-size: 13px;">
                </div>
            </div>

            <button type="submit" class="btn-gold" style="padding: 12px 24px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer;">Save Changes</button>
        </form>
    </div>

    <!-- Security Form -->
    <div class="card" style="background: #fff; border-radius: 16px; padding: 24px; border: 1px solid rgba(0,0,0,0.06);">
        <h3 style="font-size: 18px; font-weight: 700; color: #0B2B21; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-lock" style="color: #B99A61;"></i> Security & Password
        </h3>

        <form action="profile.php" method="POST">
            <input type="hidden" name="action_update_password" value="1">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #0B2B21; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Current Password</label>
                    <input type="password" name="current_password" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #E5E7EB; font-size: 14px;">
                </div>

                <div>
                    <label style="display: block; font-size: 12px; font-weight: 700; color: #0B2B21; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">New Password</label>
                    <input type="password" name="new_password" required style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #E5E7EB; font-size: 14px;">
                </div>
            </div>

            <button type="submit" class="btn-outline" style="padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer;">Update Password</button>
        </form>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>