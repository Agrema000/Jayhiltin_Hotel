<?php
// guest/card.php
require_once '../includes/header.php';

if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit;
}

$guest = getGuestFullProfile($pdo, $_SESSION['guest_id']);
$photo_path = getProfilePhotoPath($guest['photo']);
?>

<div class="container py-5" style="max-width: 480px;">

    <!-- Digital Pass Card Widget -->
    <div class="vip-card" style="background: linear-gradient(145deg, #0B2B21 0%, #061B14 100%); border-radius: 20px; padding: 28px; color: #fff; box-shadow: 0 20px 40px rgba(0,0,0,0.25); border: 1px solid rgba(185,154,97,0.3); position: relative; overflow: hidden;">
        
        <!-- Gold foil background accent elements -->
        <div style="position: absolute; top: -50px; right: -50px; width: 180px; height: 180px; border-radius: 50%; background: radial-gradient(circle, rgba(185,154,97,0.15) 0%, rgba(0,0,0,0) 70%);"></div>

        <!-- Pass Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; position: relative; z-index: 2;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: #B99A61; color: #0B2B21; font-weight: 900; display: flex; align-items: center; justify-content: center; font-size: 14px; letter-spacing: 1px;">JH</div>
                <div>
                    <h3 style="font-size: 14px; font-weight: 800; letter-spacing: 1.5px; color: #fff; margin: 0; text-transform: uppercase;">Jay Hiltin</h3>
                    <span style="font-size: 9px; color: #B99A61; letter-spacing: 2px; text-transform: uppercase; display: block;">Hotel & Suites</span>
                </div>
            </div>

            <?php echo renderTierBadge($guest['membership_level']); ?>
        </div>

        <!-- Guest Details Row -->
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 24px; position: relative; z-index: 2;">
            <div>
                <?php if ($photo_path): ?>
                    <img src="../<?php echo $photo_path; ?>" alt="<?php echo htmlspecialchars($guest['full_name']); ?>" style="width: 64px; height: 64px; border-radius: 50%; object-fit: cover; border: 2px solid #B99A61;">
                <?php else: ?>
                    <div style="width: 64px; height: 64px; border-radius: 50%; background: rgba(255,255,255,0.1); color: #B99A61; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; border: 2px solid #B99A61;">
                        <?php echo getAvatarInitials($guest['full_name']); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <span style="font-size: 10px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; display: block;">Cardholder</span>
                <h3 style="font-size: 20px; font-weight: 700; color: #fff; margin: 2px 0 4px 0;"><?php echo htmlspecialchars($guest['full_name']); ?></h3>
                <p style="font-size: 12px; color: #B99A61; font-family: monospace; letter-spacing: 1px; margin: 0;">
                    ID: <?php echo htmlspecialchars($guest['member_id']); ?>
                </p>
            </div>
        </div>

        <!-- Dynamic QR/Barcode Scanning Container -->
        <div style="background: #fff; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 20px; position: relative; z-index: 2;">
            <!-- Render QR Code using standard Google Chart API endpoint for Member ID -->
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($guest['member_id']); ?>" alt="Member QR Code" style="width: 140px; height: 140px; display: block; margin: 0 auto;">
            
            <p style="color: #0B2B21; font-size: 12px; font-weight: 700; font-family: monospace; letter-spacing: 2px; margin-top: 10px; margin-bottom: 0;">
                <?php echo htmlspecialchars($guest['member_id']); ?>
            </p>
            <span style="font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-top: 2px;">Scan at Front Desk or Restaurant</span>
        </div>

        <!-- Pass Footer Info -->
        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: rgba(255,255,255,0.6); position: relative; z-index: 2;">
            <span>Member Since: <strong><?php echo date('M Y', strtotime($guest['created_at'])); ?></strong></span>
            <span>Status: <strong style="color: #10B981;">● Active VIP</strong></span>
        </div>

    </div>

    <!-- Actions -->
    <div style="margin-top: 20px; text-align: center; display: flex; gap: 10px;">
        <a href="dashboard.php" class="btn-outline" style="flex: 1; text-decoration: none; padding: 12px; text-align: center; font-size: 13px;">Back to Dashboard</a>
        <button onclick="window.print();" class="btn-gold" style="flex: 1; padding: 12px; font-size: 13px; cursor: pointer;">Print Pass</button>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>