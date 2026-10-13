<?php
// staff/bookings.php — Reservations & Stay Management
require_once '../includes/staff_auth.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

$query         = trim($_GET['q'] ?? '');
$status_filter = trim($_GET['status'] ?? 'all');

// Build SQL dynamically based on your exact database table columns
$sql = "
    SELECT b.id, b.guest_id, b.room_type, b.check_in, b.check_out, 
           b.total_paid, b.status, b.points_earned, b.created_at,
           g.full_name AS guest_name, g.phone AS guest_phone, 
           g.member_id, g.membership_level, g.photo AS guest_photo
    FROM bookings b
    LEFT JOIN guests g ON b.guest_id = g.id
    WHERE 1=1
";

$params = [];

if ($query !== '') {
    $like = "%{$query}%";
    $sql .= " AND (g.full_name LIKE ? OR b.room_type LIKE ? OR g.phone LIKE ? OR g.member_id LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($status_filter !== 'all') {
    $sql .= " AND LOWER(b.status) = ?";
    $params[] = strtolower($status_filter);
}

$sql .= " ORDER BY b.check_in DESC, b.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {
    $bookings = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations & Bookings — Jay Hiltin</title>
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
            <a href="bookings.php" class="active">🛏️ Bookings</a>
            <a href="loyalty.php" title="Coming in phase 2">🎁 Loyalty Program</a>
            <a href="reports.php" title="Coming in phase 2">📊 Reports</a>
            <a href="settings.php" title="Coming in phase 2">⚙️ Settings</a>
        </nav>
        <a href="logout.php" class="sidebar-logout">⬅ Log Out</a>
    </aside>

    <!-- MAIN -->
    <main class="staff-main">
        
        <!-- Search & Filter Bar -->
        <div class="search-bar" style="margin-bottom: 20px;">
            <form method="GET" action="bookings.php" style="display: flex; gap: 10px; width: 100%;">
                <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>"
                       placeholder="Search by guest name, room type, phone, or member ID..." style="flex: 1;">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <button type="submit" class="btn-green">Search Bookings</button>
            </form>
        </div>

        <!-- Status Filter Tabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap;">
            <?php 
                $tabs = [
                    'all'       => 'All Reservations', 
                    'confirmed' => 'Confirmed', 
                    'completed' => 'Completed', 
                    'cancelled' => 'Cancelled'
                ];
                foreach ($tabs as $key => $label):
                    $is_active = ($status_filter === $key);
                    $tab_url = "bookings.php?q=" . urlencode($query) . "&status=" . $key;
            ?>
                <a href="<?php echo $tab_url; ?>" style="text-decoration: none; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; <?php echo $is_active ? 'background: #0B2B21; color: #fff;' : 'background: #fff; color: #4B5563; border: 1px solid #E5E7EB;'; ?>">
                    <?php echo $label; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (count($bookings) === 0): ?>
            <div class="empty-state">
                <h2>No Bookings Found</h2>
                <p>No reservation records match your search criteria or status filter.</p>
            </div>
        <?php else: ?>
            <h2 class="results-title">Guest Reservations (<?php echo count($bookings); ?>)</h2>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($bookings as $b):
                    $photo    = guestPhotoUrl($b['guest_photo'] ?? null, $b['guest_name'] ?? '');
                    $ref      = '#JH-BK-' . str_pad($b['id'], 4, '0', STR_PAD_LEFT);
                    $amount   = (float)($b['total_paid'] ?? 0);
                    $status   = strtolower($b['status'] ?? 'confirmed');
                    $points   = (int)($b['points_earned'] ?? 0);

                    // Status badge colors
                    if ($status === 'completed') {
                        $badge_bg = '#ECFDF5'; $badge_color = '#047857';
                    } elseif ($status === 'cancelled') {
                        $badge_bg = '#FEE2E2'; $badge_color = '#991B1B';
                    } else {
                        $badge_bg = '#FEF3C7'; $badge_color = '#92400E'; // Confirmed
                    }

                    // Night calculation
                    $in_time  = strtotime($b['check_in']);
                    $out_time = strtotime($b['check_out']);
                    $nights   = max(1, round(($out_time - $in_time) / (60 * 60 * 24)));
                ?>
                <div class="guest-result" style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
                    
                    <!-- Guest Photo & Name -->
                    <div style="display: flex; align-items: center; gap: 15px; min-width: 240px;">
                        <div class="guest-result-photo" style="width: 60px; height: 60px; flex-shrink: 0;">
                            <?php if ($photo): ?>
                                <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($b['guest_name'] ?? 'Guest'); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                            <?php else: ?>
                                <div class="avatar-initials" style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: #0B2B21; color: #B99A61; font-weight: 700;">
                                    <?php echo initials($b['guest_name'] ?? 'Guest'); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <h3 style="font-size: 16px; font-weight: 700; color: #0B2B21; margin-bottom: 2px;">
                                <?php echo htmlspecialchars($b['guest_name'] ?? 'Registered Guest'); ?>
                            </h3>
                            <p style="font-size: 12px; color: #666; margin-bottom: 4px;">
                                ID: <strong><?php echo htmlspecialchars($b['member_id'] ?? 'N/A'); ?></strong> &bull; 📞 <?php echo htmlspecialchars($b['guest_phone'] ?? 'N/A'); ?>
                            </p>
                            <span style="display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; background: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>; text-transform: capitalize;">
                                ● <?php echo htmlspecialchars($b['status'] ?? 'Confirmed'); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Room & Booking Reference -->
                    <div style="min-width: 180px;">
                        <span style="font-size: 11px; color: #B99A61; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;"><?php echo $ref; ?></span>
                        <h4 style="font-size: 15px; font-weight: 700; color: #0B2B21; margin: 2px 0;">
                            <?php echo htmlspecialchars($b['room_type'] ?? 'Standard Room'); ?>
                        </h4>
                        <p style="font-size: 12px; color: #047857; font-weight: 600;">
                            +<?php echo $points; ?> Points Earned
                        </p>
                    </div>

                    <!-- Entering & Leaving Dates -->
                    <div style="min-width: 220px; background: #FAF8F5; padding: 10px 14px; border-radius: 8px; border: 1px solid rgba(185,154,97,0.2);">
                        <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                            <span style="color: #6B7280;">Entering (In):</span>
                            <strong style="color: #0B2B21;"><?php echo date('d M Y', $in_time); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px;">
                            <span style="color: #6B7280;">Leaving (Out):</span>
                            <strong style="color: #0B2B21;"><?php echo date('d M Y', $out_time); ?></strong>
                        </div>
                        <div style="font-size: 11px; color: #B99A61; font-weight: 700; text-align: right;">
                            Duration: <?php echo $nights; ?> Night<?php echo $nights > 1 ? 's' : ''; ?>
                        </div>
                    </div>

                    <!-- Total Paid & Profile View -->
                    <div style="text-align: right; min-width: 140px;">
                        <span style="font-size: 11px; color: #666; display: block;">Total Paid</span>
                        <strong style="font-size: 18px; color: #0B2B21; display: block; margin-bottom: 6px;">₦<?php echo number_format($amount, 2); ?></strong>
                        
                        <?php if (!empty($b['guest_id'])): ?>
                            <a href="guest_view.php?id=<?php echo (int)$b['guest_id']; ?>" class="btn-outline" style="font-size: 12px; padding: 6px 12px; text-decoration: none;">View Profile</a>
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