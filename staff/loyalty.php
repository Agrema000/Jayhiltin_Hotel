<?php
// staff/loyalty.php — Loyalty Program Management & Rewards Catalog
require_once '../includes/staff_auth.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

$message = '';$error   = '';

// Action: Handle Voucher Verification / Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) &&$_POST['action'] === 'update_voucher') {
    $redemption_id = (int)($_POST['redemption_id'] ?? 0);
    $new_status    = trim($_POST['status'] ?? '');

    if ($redemption_id > 0 && in_array($new_status, ['Active', 'Used', 'Cancelled'])) {
        try {
            $u_stmt =$pdo->prepare("UPDATE redemptions SET status = ? WHERE id = ?");
            $u_stmt->execute([$new_status, $redemption_id]);$message = "Voucher status updated to '{$new_status}' successfully!";
        } catch (Exception $e) {$error = "Failed to update voucher status.";
        }
    }
}

$query = trim($_GET['q'] ?? '');

// Fetch Overview Stats
$total_points    = 0;
$total_guests    = 0;
$active_vouchers = 0;

try {
    $stats_points =$pdo->query("SELECT SUM(points) AS total_pts, COUNT(*) AS cnt FROM guests")->fetch();
    $total_points = (int)($stats_points['total_pts'] ?? 0);
    $total_guests = (int)($stats_points['cnt'] ?? 0);

    $stats_vouchers =$pdo->query("SELECT COUNT(*) AS cnt FROM redemptions WHERE LOWER(status) = 'active'")->fetch();
    $active_vouchers = (int)($stats_vouchers['cnt'] ?? 0); } catch (Exception$e) {
    // Fallback if empty
}

// 1. Fetch All Catalog Rewards from `rewards` Table
$catalog_rewards = [];
try {
    $r_stmt =$pdo->query("SELECT * FROM rewards ORDER BY points_cost ASC");
    $catalog_rewards =$r_stmt->fetchAll();
} catch (Exception $e) {$catalog_rewards = [];
}

// 2. Fetch Claimed Redemptions / Vouchers Joined with `rewards` and `guests`
$sql = "
    SELECT red.*, 
           g.id AS guest_db_id, g.full_name AS guest_name, g.phone AS guest_phone, 
           g.member_id, g.membership_level, g.photo AS guest_photo,
           rw.title AS reward_title, rw.description AS reward_desc, rw.points_cost, rw.category
    FROM redemptions red
    LEFT JOIN guests g ON red.guest_id = g.id
    LEFT JOIN rewards rw ON red.reward_id = rw.id
    WHERE 1=1
";

$params = [];

if ($query !== '') {
    $like = "\%{$query}%";
    $sql .= " AND (g.full_name LIKE ? OR g.member_id LIKE ? OR rw.title LIKE ? OR red.voucher_code LIKE ?)";
    $params[] =$like;
    $params[] =$like;
    $params[] =$like;
    $params[] =$like;
}

$sql .= " ORDER BY red.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $redemptions =$stmt->fetchAll();
} catch (Exception $e) {$redemptions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Program & Rewards — Jay Hiltin</title>
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
            <a href="loyalty.php" class="active">🎁 Loyalty Program</a>
            <a href="reports.php" title="Coming in phase 2">📊 Reports</a>
            <a href="settings.php" title="Coming in phase 2">⚙️ Settings</a>
        </nav>
        <a href="logout.php" class="sidebar-logout">⬅ Log Out</a>
    </aside>

    <!-- MAIN -->
    <main class="staff-main">

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

        <!-- Loyalty Overview Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 28px;">
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
                <span style="font-size: 12px; color: #6B7280; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Active Members</span>
                <strong style="font-size: 26px; color: #0B2B21; display: block; margin-top: 4px;"><?php echo number_format($total_guests); ?></strong>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
                <span style="font-size: 12px; color: #6B7280; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Total Member Points</span>
                <strong style="font-size: 26px; color: #B99A61; display: block; margin-top: 4px;"><?php echo number_format($total_points); ?> pts</strong>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
                <span style="font-size: 12px; color: #6B7280; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Active Vouchers</span>
                <strong style="font-size: 26px; color: #059669; display: block; margin-top: 4px;"><?php echo number_format($active_vouchers); ?></strong>
            </div>
        </div>

        <!-- Available Rewards Catalog (Loaded directly from `rewards` table) -->
        <div style="margin-bottom: 32px;">
            <h2 class="results-title" style="margin-bottom: 14px;">🎁 Active Reward Catalog (<?php echo count($catalog_rewards); ?>)</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 16px;">
                <?php foreach ($catalog_rewards as$rw): ?>
                    <div style="background: #fff; padding: 18px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <span style="font-size: 11px; font-weight: 700; color: #B99A61; background: #FAF8F5; padding: 2px 8px; border-radius: 4px; text-transform: uppercase;">
                                <?php echo htmlspecialchars($rw['category'] ?? 'General'); ?>
                            </span>
                            <h3 style="font-size: 16px; font-weight: 700; color: #0B2B21; margin: 8px 0 4px 0;">
                                <?php echo htmlspecialchars($rw['title']); ?>
                            </h3>
                            <p style="font-size: 13px; color: #666; line-height: 1.4; margin-bottom: 12px;">
                                <?php echo htmlspecialchars($rw['description']); ?>
                            </p>
                        </div>
                        <div style="border-top: 1px solid #F3F4F6; pt-12px; padding-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                            <span style="font-size: 12px; color: #888;">Cost</span>
                            <strong style="font-size: 16px; color: #0B2B21;"><?php echo number_format($rw['points_cost']); ?> pts</strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Search Bar for Redemptions -->
        <div class="search-bar" style="margin-bottom: 20px;">
            <form method="GET" action="loyalty.php" style="display: flex; gap: 10px; width: 100%;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>"
                       placeholder="Search claimed vouchers by guest name, Member ID, or voucher code..." style="flex: 1;">
                <button type="submit" class="btn-green">Verify / Search</button>
            </form>
        </div>

        <!-- Redemptions List -->
        <?php if (count($redemptions) === 0): ?>
            <div class="empty-state">
                <h2>No Voucher Redemptions Found</h2>
                <p>No reward redemption records match your search query.</p>
            </div>
        <?php else: ?>
            <h2 class="results-title">Claimed Guest Vouchers (<?php echo count($redemptions); ?>)</h2>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($redemptions as$red):
                    $photo  = guestPhotoUrl($red['guest_photo'] ?? null, $red['guest_name'] ?? '');$code   = $red['voucher_code'] ?? ('JH-VOUCHER-' .$red['id']);
                    $status = strtolower($red['status'] ?? 'active');

                    if ($status === 'used') {
                        $badge_bg = '#F3F4F6'; $badge_color = '#4B5563';
                    } elseif ($status === 'cancelled') {
                        $badge_bg = '#FEE2E2'; $badge_color = '#991B1B';
                    } else {
                        $badge_bg = '#ECFDF5'; $badge_color = '#047857'; // Active
                    }
                ?>
                <div class="guest-result" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
                    
                    <!-- Guest Details -->
                    <div style="display: flex; align-items: center; gap: 15px; min-width: 240px;">
                        <div class="guest-result-photo" style="width: 55px; height: 55px; flex-shrink: 0;">
                            <?php if ($photo): ?>
                                <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($red['guest_name'] ?? 'Guest'); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <div class="avatar-initials" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: #0B2B21; color: #B99A61; font-weight: 700;">
                                    <?php echo initials($red['guest_name'] ?? 'Guest'); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h3 style="font-size: 16px; font-weight: 700; color: #0B2B21; margin-bottom: 2px;">
                                <?php echo htmlspecialchars($red['guest_name'] ?? 'Registered Guest'); ?>
                            </h3>
                            <p style="font-size: 12px; color: #666; margin-bottom: 4px;">
                                ID: <strong><?php echo htmlspecialchars($red['member_id'] ?? 'N/A'); ?></strong> &bull; 📞 <?php echo htmlspecialchars($red['guest_phone'] ?? 'N/A'); ?>
                            </p>
                            <span style="font-size: 11px; font-weight: 700; color: #B99A61;">
                                <?php echo htmlspecialchars($red['membership_level'] ?? 'Bronze'); ?> Tier
                            </span>
                        </div>
                    </div>

                    <!-- Reward Title & Code -->
                    <div style="min-width: 200px;">
                        <span style="font-size: 11px; color: #6B7280; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;"><?php echo htmlspecialchars($red['category'] ?? 'Reward'); ?></span>
                        <h4 style="font-size: 15px; font-weight: 700; color: #0B2B21; margin: 2px 0;">
                            <?php echo htmlspecialchars($red['reward_title'] ?? 'Special Perk'); ?>
                        </h4>
                        <div style="margin-top: 4px;">
                            <span style="font-family: monospace; font-size: 13px; font-weight: 700; color: #0B2B21; background: #FAF8F5; padding: 3px 8px; border-radius: 4px; border: 1px dashed #B99A61;">
                                🎫 <?php echo htmlspecialchars($code); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Points Cost & Status -->
                    <div style="min-width: 140px;">
                        <span style="font-size: 11px; color: #666; display: block;">Cost</span>
                        <strong style="font-size: 16px; color: #B99A61; display: block; margin-bottom: 4px;">
                            <?php echo number_format($red['points_cost'] ?? $red['points_spent'] ?? 0); ?> pts
                        </strong>
                        <span style="display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; background: <?php echo $badge_bg; ?>; color: <?php echo$badge_color; ?>; text-transform: capitalize;">
                            ● <?php echo htmlspecialchars($red['status'] ?? 'Active'); ?>
                        </span>
                    </div>

                    <!-- Quick Action Form -->
                    <div style="text-align: right; min-width: 150px;">
                        <?php if ($status === 'active'): ?>
                            <form method="POST" action="loyalty.php" onsubmit="return confirm('Mark this voucher as USED by guest?');">
                                <input type="hidden" name="action" value="update_voucher">
                                <input type="hidden" name="redemption_id" value="<?php echo (int)$red['id']; ?>">
                                <input type="hidden" name="status" value="Used">
                                <button type="submit" class="btn-green" style="font-size: 12px; padding: 6px 12px; cursor: pointer;">
                                    ✓ Redeem Voucher
                                </button>
                            </form>
                        <?php else: ?>
                            <a href="guest_view.php?id=<?php echo (int)$red['guest_db_id']; ?>" class="btn-outline" style="font-size: 12px; padding: 6px 12px; text-decoration: none;">
                                View Profile
                            </a>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>