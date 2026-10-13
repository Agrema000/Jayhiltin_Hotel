<?php
// staff/guest_view.php — Detailed Guest Profile & History
require_once '../includes/staff_auth.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

$guest_id = (int)($_GET['id'] ?? 0);
$guest    = null;

if ($guest_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM guests WHERE id = ?");
    $stmt->execute([$guest_id]);
    $guest = $stmt->fetch();
}

// Fetch guest bookings history
$bookings = [];
if ($guest) {
    try {
        $b_stmt = $pdo->prepare("
            SELECT b.*, r.room_number, r.room_type, r.price
            FROM bookings b
            LEFT JOIN rooms r ON b.room_id = r.id
            WHERE b.guest_id = ?
            ORDER BY b.id DESC
        ");
        $b_stmt->execute([$guest['id']]);
        $bookings = $b_stmt->fetchAll();
    } catch (Exception $e) {
        $bookings = [];
    }
}

// Fetch guest rewards redemption history
$redemptions = [];
if ($guest) {
    try {
        $r_stmt = $pdo->prepare("
            SELECT red.*, rw.title, rw.description
            FROM redemptions red
            LEFT JOIN rewards rw ON red.reward_id = rw.id
            WHERE red.guest_id = ?
            ORDER BY red.id DESC
        ");
        $r_stmt->execute([$guest['id']]);
        $redemptions = $r_stmt->fetchAll();
    } catch (Exception $e) {
        $redemptions = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $guest ? htmlspecialchars($guest['full_name']) . ' — Profile' : 'Guest Not Found'; ?> — Jay Hiltin</title>
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
            <a href="search.php" class="active">🔍 Guest Search</a>
            <a href="bookings.php" title="Coming in phase 2">🛏️ Bookings</a>
            <a href="loyalty.php" title="Coming in phase 2">🎁 Loyalty Program</a>
            <a href="reports.php" title="Coming in phase 2">📊 Reports</a>
            <a href="settings.php" title="Coming in phase 2">⚙️ Settings</a>
        </nav>
        <a href="logout.php" class="sidebar-logout">⬅ Log Out</a>
    </aside>

    <!-- MAIN -->
    <main class="staff-main">
        <?php if (!$guest): ?>
            <div class="empty-state">
                <h2>Guest Not Found</h2>
                <p>The requested guest profile could not be retrieved or does not exist.</p>
                <br>
                <a href="search.php" class="btn-green" style="text-decoration: none; padding: 10px 20px; border-radius: 8px;">← Back to Guest Search</a>
            </div>
        <?php else: 
            $photo = guestPhotoUrl($guest['photo'] ?? null, $guest['full_name'] ?? '');
            $member_since = !empty($guest['created_at']) ? date('M Y', strtotime($guest['created_at'])) : 'Recent';
        ?>
            <!-- Back Action Header -->
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <a href="search.php" style="color: #0B2B21; font-weight: 600; text-decoration: none; font-size: 14px;">
                    ← Back to Guest Directory
                </a>
                <span style="font-size: 13px; color: #666;">Member Since: <strong><?php echo $member_since; ?></strong></span>
            </div>

            <!-- Profile Summary Hero Card -->
            <div class="guest-result" style="margin-bottom: 25px; background: #fff; padding: 25px; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06);">
                <div class="guest-result-photo" style="width: 90px; height: 90px;">
                    <?php if ($photo): ?>
                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($guest['full_name']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    <?php else: ?>
                        <div class="avatar-initials" style="width: 100%; height: 100%; font-size: 28px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: #0B2B21; color: #B99A61;">
                            <?php echo initials($guest['full_name']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="guest-result-info">
                    <h2 style="font-size: 24px; font-weight: 700; color: #0B2B21; margin-bottom: 4px;">
                        <?php echo htmlspecialchars($guest['full_name']); ?>
                    </h2>
                    <p class="member-id" style="font-size: 14px; color: #666; margin-bottom: 8px;">
                        Member ID: <strong><?php echo htmlspecialchars($guest['member_id']); ?></strong>
                    </p>
                    <span class="badge badge-<?php echo strtolower($guest['membership_level'] ?? 'bronze'); ?>" style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;">
                        <?php echo htmlspecialchars($guest['membership_level'] ?? 'Bronze'); ?> Member
                    </span>
                    <div style="margin-top: 12px; font-size: 13px; color: #444; line-height: 1.6;">
                        <span>📞 <?php echo htmlspecialchars($guest['phone'] ?? 'N/A'); ?></span> &nbsp;|&nbsp; 
                        <span>✉️ <?php echo htmlspecialchars($guest['email'] ?? 'N/A'); ?></span>
                    </div>
                </div>

                <div class="guest-result-points" style="text-align: right;">
                    <span style="font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 1px;">Loyalty Balance</span>
                    <strong style="font-size: 28px; color: #B99A61; display: block; margin: 4px 0;"><?php echo number_format($guest['points'] ?? 0); ?> pts</strong>
                    <span style="font-size: 12px; color: #0B2B21; font-weight: 600;">Total Stays: <?php echo count($bookings); ?></span>
                </div>
            </div>

            <!-- Booking History Section -->
            <div style="background: #fff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06); padding: 22px; margin-bottom: 25px;">
                <h3 style="font-size: 18px; font-weight: 700; color: #0B2B21; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    🛏️ Stay & Reservation History (<?php echo count($bookings); ?>)
                </h3>

                <?php if (!empty($bookings)): ?>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                            <thead>
                                <tr style="border-bottom: 2px solid #F3F4F6; color: #6B7280; font-size: 12px; text-transform: uppercase;">
                                    <th style="padding: 10px;">Ref Code</th>
                                    <th style="padding: 10px;">Room</th>
                                    <th style="padding: 10px;">Check-In</th>
                                    <th style="padding: 10px;">Check-Out</th>
                                    <th style="padding: 10px;">Amount</th>
                                    <th style="padding: 10px;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b): 
                                    $ref = $b['reference'] ?? $b['booking_ref'] ?? ('#JH-' . $b['id']);
                                    $amount = $b['total_amount'] ?? $b['amount'] ?? 0;
                                ?>
                                    <tr style="border-bottom: 1px solid #F3F4F6;">
                                        <td style="padding: 12px 10px; font-weight: 700; color: #0B2B21;"><?php echo htmlspecialchars($ref); ?></td>
                                        <td style="padding: 12px 10px;"><?php echo htmlspecialchars($b['room_type'] ?? 'Standard'); ?> (Room <?php echo htmlspecialchars($b['room_number'] ?? 'N/A'); ?>)</td>
                                        <td style="padding: 12px 10px;"><?php echo date('d M Y', strtotime($b['check_in'])); ?></td>
                                        <td style="padding: 12px 10px;"><?php echo date('d M Y', strtotime($b['check_out'])); ?></td>
                                        <td style="padding: 12px 10px; font-weight: 600;">₦<?php echo number_format($amount, 2); ?></td>
                                        <td style="padding: 12px 10px;">
                                            <span style="padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: #ECFDF5; color: #059669;">
                                                <?php echo htmlspecialchars($b['status'] ?? 'Confirmed'); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: #888; font-size: 14px; margin: 0;">No reservation records found for this guest.</p>
                <?php endif; ?>
            </div>

            <!-- Claimed Rewards & Voucher History -->
            <div style="background: #fff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06); padding: 22px;">
                <h3 style="font-size: 18px; font-weight: 700; color: #0B2B21; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    🎁 Claimed Vouchers & Loyalty Perks (<?php echo count($redemptions); ?>)
                </h3>

                <?php if (!empty($redemptions)): ?>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 15px;">
                        <?php foreach ($redemptions as $red): 
                            $v_code = $red['voucher_code'] ?? $red['code'] ?? $red['voucher'] ?? ('JH-RWD-' . $red['id']);
                            $v_status = $red['status'] ?? 'Active';
                        ?>
                            <div style="background: #FAF8F5; border: 1px dashed rgba(185,154,97,0.5); padding: 14px; border-radius: 10px;">
                                <strong style="font-size: 14px; color: #0B2B21; display: block; margin-bottom: 4px;">
                                    <?php echo htmlspecialchars($red['title'] ?? 'Reward Voucher'); ?>
                                </strong>
                                <span style="font-family: monospace; font-weight: 700; color: #B99A61; background: #fff; padding: 2px 8px; border-radius: 4px; border: 1px solid #E5E7EB; font-size: 13px;">
                                    <?php echo htmlspecialchars($v_code); ?>
                                </span>
                                <span style="float: right; font-size: 11px; font-weight: 700; color: <?php echo strtolower($v_status) === 'active' ? '#10B981' : '#6B7280'; ?>;">
                                    ● <?php echo htmlspecialchars($v_status); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #888; font-size: 14px; margin: 0;">No reward vouchers claimed by this guest yet.</p>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>