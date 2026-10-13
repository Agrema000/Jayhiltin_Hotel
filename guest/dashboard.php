<?php
// guest/dashboard.php
require_once '../includes/header.php';
require_once '../config/db.php';
require_once '../includes/functions.php';
if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit;
}

$guest = getGuestFullProfile($pdo, $_SESSION['guest_id']);
$photo_path = getProfilePhotoPath($guest['photo']);
?>

<div class="container" style="max-width: 600px;">

    <div class="dashboard-card" style="display: flex; align-items: center; justify-content: space-between;">
        <div>
            <h2 style="font-size: 22px;">Hello, <?php echo htmlspecialchars($guest['full_name']); ?></h2>
            <p class="member-id">Member ID: <strong><?php echo htmlspecialchars($guest['member_id']); ?></strong></p>
            <div style="margin-top: 6px;">
                <?php echo renderTierBadge($guest['membership_level']); ?>
            </div>
        </div>

        <div class="profile-photo">
            <?php if ($photo_path): ?>
                <img src="../<?php echo $photo_path; ?>" alt="<?php echo htmlspecialchars($guest['full_name']); ?>" style="width: 68px; height: 68px; border-radius: 50%; object-fit: cover;">
            <?php else: ?>
                <div class="avatar-initials" style="width: 68px; height: 68px; font-size: 20px;">
                    <?php echo getAvatarInitials($guest['full_name']); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="dashboard-card" style="background: #FAF8F5; border: 1px solid rgba(185,154,97,0.3);">
        <p style="font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px;">Your Loyalty Points</p>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 6px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 28px; color: var(--gold);">⭐</span>
                <span class="points-display"><?php echo number_format($guest['points']); ?></span>
            </div>
            <a href="rewards.php" class="btn-gold" style="padding: 8px 16px; font-size: 13px;">View Rewards</a>
        </div>
    </div>

    <div class="dashboard-card">
        <div class="profile-stats" style="justify-content: space-between; margin-top: 0;">
            <div class="stat">
                <span>Total Stays</span>
                <strong><?php echo $guest['total_stays']; ?></strong>
            </div>
            <div class="stat">
                <span>Last Stay</span>
                <strong><?php echo $guest['last_stay'] ? date('d M Y', strtotime($guest['last_stay'])) : 'No stays yet'; ?></strong>
            </div>
            <div class="stat">
                <span>Member Since</span>
                <strong><?php echo date('M Y', strtotime($guest['created_at'])); ?></strong>
            </div>
        </div>
    </div>

    <div class="panel" style="padding: 10px 0;">
        <div style="display: flex; flex-direction: column;">
            <a href="bookings.php" class="history-item" style="padding: 16px 20px; text-decoration: none; color: inherit;">
                <span style="display: flex; align-items: center; gap: 12px; font-weight: 600;">
                    <i class="fas fa-calendar-alt" style="color: var(--green);"></i> My Bookings & Stay History
                </span>
                <i class="fas fa-chevron-right" style="color: var(--muted); font-size: 12px;"></i>
            </a>

            <a href="rewards.php" class="history-item" style="padding: 16px 20px; text-decoration: none; color: inherit;">
                <span style="display: flex; align-items: center; gap: 12px; font-weight: 600;">
                    <i class="fas fa-gift" style="color: var(--green);"></i> Available Rewards & Points
                </span>
                <i class="fas fa-chevron-right" style="color: var(--muted); font-size: 12px;"></i>
            </a>

            <a href="profile.php" class="history-item" style="padding: 16px 20px; text-decoration: none; color: inherit;">
                <span style="display: flex; align-items: center; gap: 12px; font-weight: 600;">
                    <i class="fas fa-id-card" style="color: var(--green);"></i> Digital Membership Card
                </span>
                <i class="fas fa-chevron-right" style="color: var(--muted); font-size: 12px;"></i>
            </a>
        </div>
    </div>

    <div style="margin-top: 24px; text-align: center;">
        <a href="logout.php" class="btn-outline" style="width: 100%; border-color: var(--error); color: var(--error);">Log Out</a>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>