<?php
// staff/reports.php — Executive Hotel Operations & Financial Report
require_once '../includes/staff_auth.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

// 1. Revenue & Booking Financial Metrics
$total_revenue    = 0;
$total_bookings   = 0;
$booking_counts   = ['confirmed' => 0, 'completed' => 0, 'cancelled' => 0];
$booking_revenues = ['confirmed' => 0, 'completed' => 0, 'cancelled' => 0];

try {
    $fin_stmt = $pdo->query("SELECT status, COUNT(*) AS count, SUM(total_paid) AS total FROM bookings GROUP BY LOWER(status)");
    while ($row = $fin_stmt->fetch()) {
        $st = strtolower($row['status']);
        $cnt = (int)$row['count'];
        $amt = (float)($row['total'] ?? 0);

        $booking_counts[$st]   = $cnt;
        $booking_revenues[$st] = $amt;
        $total_bookings       += $cnt;
        $total_revenue        += $amt;
    }
} catch (Exception $e) {}

// 2. Room Inventory & Occupancy Status
$total_rooms     = 0;
$rooms_available = 0;
$rooms_booked    = 0;
$rooms_maint     = 0;

try {
    $room_stmt = $pdo->query("SELECT status, COUNT(*) AS count FROM rooms GROUP BY status");
    while ($row = $room_stmt->fetch()) {
        $st  = $row['status'];
        $cnt = (int)$row['count'];
        $total_rooms += $cnt;

        if (strcasecmp($st, 'Available') === 0) $rooms_available = $cnt;
        elseif (strcasecmp($st, 'Booked') === 0) $rooms_booked = $cnt;
        elseif (strcasecmp($st, 'Maintenance') === 0) $rooms_maint = $cnt;
    }
} catch (Exception $e) {}

$occupancy_rate = ($total_rooms > 0) ? round(($rooms_booked / $total_rooms) * 100) : 0;

// 3. Guest & Tier Breakdown
$total_guests = 0;
$total_points = 0;
$tier_counts  = ['bronze' => 0, 'silver' => 0, 'gold' => 0, 'platinum' => 0];

try {
    $guest_stmt = $pdo->query("SELECT membership_level, COUNT(*) AS count, SUM(points) AS total_pts FROM guests GROUP BY LOWER(membership_level)");
    while ($row = $guest_stmt->fetch()) {
        $tier = strtolower($row['membership_level'] ?? 'bronze');
        $cnt  = (int)$row['count'];
        $tier_counts[$tier] = $cnt;
        $total_guests      += $cnt;
        $total_points      += (int)($row['total_pts'] ?? 0);
    }
} catch (Exception $e) {}

// 4. Loyalty Voucher Activity
$total_redemptions = 0;
$vouchers_active   = 0;
$vouchers_used     = 0;

try {
    $red_stmt = $pdo->query("SELECT status, COUNT(*) AS count FROM redemptions GROUP BY LOWER(status)");
    while ($row = $red_stmt->fetch()) {
        $st  = strtolower($row['status']);
        $cnt = (int)$row['count'];
        $total_redemptions += $cnt;

        if ($st === 'active') $vouchers_active = $cnt;
        elseif ($st === 'used') $vouchers_used = $cnt;
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Operations Report — Jay Hiltin</title>
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
            <a href="reports.php" class="active">📊 Reports</a>
            <a href="settings.php" title="Coming in phase 2">⚙️ Settings</a>
        </nav>
        <a href="logout.php" class="sidebar-logout">⬅ Log Out</a>
    </aside>

    <!-- MAIN -->
    <main class="staff-main">

        <!-- Report Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h2 style="font-size: 22px; font-weight: 800; color: #0B2B21; margin-bottom: 4px;">Overall Operations & Financial Report</h2>
                <p style="font-size: 13px; color: #666;">Real-time performance metrics and system summary as of <?php echo date('d M Y, h:i A'); ?></p>
            </div>
            <button onclick="window.print();" class="btn-outline" style="padding: 8px 16px; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                🖨️ Print / Export PDF
            </button>
        </div>

        <!-- KPI Metric Highlights -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 28px;">
            
            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); border-left: 4px solid #B99A61;">
                <span style="font-size: 11px; color: #888; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Total Recognized Revenue</span>
                <strong style="font-size: 24px; color: #0B2B21; display: block; margin-top: 4px;">₦<?php echo number_format($total_revenue, 2); ?></strong>
                <span style="font-size: 11px; color: #059669; font-weight: 600;">Across <?php echo number_format($total_bookings); ?> Bookings</span>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); border-left: 4px solid #0B2B21;">
                <span style="font-size: 11px; color: #888; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Current Occupancy</span>
                <strong style="font-size: 24px; color: #0B2B21; display: block; margin-top: 4px;"><?php echo $occupancy_rate; ?>%</strong>
                <span style="font-size: 11px; color: #666; font-weight: 600;"><?php echo $rooms_booked; ?> of <?php echo $total_rooms; ?> Rooms Occupied</span>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); border-left: 4px solid #3B82F6;">
                <span style="font-size: 11px; color: #888; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Registered Guests</span>
                <strong style="font-size: 24px; color: #0B2B21; display: block; margin-top: 4px;"><?php echo number_format($total_guests); ?></strong>
                <span style="font-size: 11px; color: #B99A61; font-weight: 600;"><?php echo number_format($total_points); ?> Total Points Held</span>
            </div>

            <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); border-left: 4px solid #10B981;">
                <span style="font-size: 11px; color: #888; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Loyalty Vouchers Claimed</span>
                <strong style="font-size: 24px; color: #0B2B21; display: block; margin-top: 4px;"><?php echo number_format($total_redemptions); ?></strong>
                <span style="font-size: 11px; color: #059669; font-weight: 600;"><?php echo $vouchers_active; ?> Currently Active / Pending</span>
            </div>

        </div>

        <!-- Breakdown Grid -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 28px;">
            
            <!-- 1. Room Availability & Inventory Status -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); padding: 22px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #0B2B21; margin-bottom: 16px; display: flex; justify-content: space-between;">
                    <span>🏨 Room Inventory Breakdown</span>
                    <small style="color: #666; font-weight: 400; font-size: 12px;">Total: <?php echo $total_rooms; ?></small>
                </h3>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                            <span style="color: #059669; font-weight: 600;">● Available Rooms</span>
                            <strong><?php echo $rooms_available; ?> Rooms</strong>
                        </div>
                        <div style="background: #F3F4F6; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: #10B981; height: 100%; width: <?php echo $total_rooms > 0 ? ($rooms_available / $total_rooms) * 100 : 0; ?>%;"></div>
                        </div>
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                            <span style="color: #D97706; font-weight: 600;">● Booked / Occupied</span>
                            <strong><?php echo $rooms_booked; ?> Rooms</strong>
                        </div>
                        <div style="background: #F3F4F6; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: #F59E0B; height: 100%; width: <?php echo $total_rooms > 0 ? ($rooms_booked / $total_rooms) * 100 : 0; ?>%;"></div>
                        </div>
                    </div>

                    <div>
                        <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                            <span style="color: #DC2626; font-weight: 600;">● Attention / Maintenance Required</span>
                            <strong><?php echo $rooms_maint; ?> Rooms</strong>
                        </div>
                        <div style="background: #F3F4F6; height: 8px; border-radius: 4px; overflow: hidden;">
                            <div style="background: #EF4444; height: 100%; width: <?php echo $total_rooms > 0 ? ($rooms_maint / $total_rooms) * 100 : 0; ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Reservation & Payment Status -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); padding: 22px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #0B2B21; margin-bottom: 16px; display: flex; justify-content: space-between;">
                    <span>🛏️ Booking & Payment Status</span>
                    <small style="color: #666; font-weight: 400; font-size: 12px;">Total: <?php echo $total_bookings; ?></small>
                </h3>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #FAF8F5; border-radius: 8px;">
                        <div>
                            <strong style="font-size: 13px; color: #0B2B21; display: block;">Completed Stays</strong>
                            <span style="font-size: 11px; color: #666;"><?php echo $booking_counts['completed'] ?? 0; ?> Stays Recorded</span>
                        </div>
                        <strong style="font-size: 14px; color: #059669;">₦<?php echo number_format($booking_revenues['completed'] ?? 0, 2); ?></strong>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #FAF8F5; border-radius: 8px;">
                        <div>
                            <strong style="font-size: 13px; color: #0B2B21; display: block;">Confirmed / Upcoming</strong>
                            <span style="font-size: 11px; color: #666;"><?php echo $booking_counts['confirmed'] ?? 0; ?> Active Bookings</span>
                        </div>
                        <strong style="font-size: 14px; color: #D97706;">₦<?php echo number_format($booking_revenues['confirmed'] ?? 0, 2); ?></strong>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #FAF8F5; border-radius: 8px;">
                        <div>
                            <strong style="font-size: 13px; color: #0B2B21; display: block;">Cancelled Bookings</strong>
                            <span style="font-size: 11px; color: #666;"><?php echo $booking_counts['cancelled'] ?? 0; ?> Cancelled</span>
                        </div>
                        <strong style="font-size: 14px; color: #DC2626;">₦<?php echo number_format($booking_revenues['cancelled'] ?? 0, 2); ?></strong>
                    </div>
                </div>
            </div>

            <!-- 3. Guest Membership Tier Distribution -->
            <div style="background: #fff; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); padding: 22px;">
                <h3 style="font-size: 16px; font-weight: 700; color: #0B2B21; margin-bottom: 16px; display: flex; justify-content: space-between;">
                    <span>👑 Guest Tier Distribution</span>
                    <small style="color: #666; font-weight: 400; font-size: 12px;"><?php echo $total_guests; ?> Members</small>
                </h3>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <div style="background: #FDF6EC; padding: 12px; border-radius: 8px; border: 1px solid #FBE8D0;">
                        <span style="font-size: 11px; font-weight: 700; color: #B45309; text-transform: uppercase;">Bronze Tier</span>
                        <strong style="font-size: 18px; color: #0B2B21; display: block; margin-top: 2px;"><?php echo $tier_counts['bronze'] ?? 0; ?></strong>
                    </div>

                    <div style="background: #F3F4F6; padding: 12px; border-radius: 8px; border: 1px solid #E5E7EB;">
                        <span style="font-size: 11px; font-weight: 700; color: #4B5563; text-transform: uppercase;">Silver Tier</span>
                        <strong style="font-size: 18px; color: #0B2B21; display: block; margin-top: 2px;"><?php echo $tier_counts['silver'] ?? 0; ?></strong>
                    </div>

                    <div style="background: #FEF3C7; padding: 12px; border-radius: 8px; border: 1px solid #FDE68A;">
                        <span style="font-size: 11px; font-weight: 700; color: #92400E; text-transform: uppercase;">Gold Tier</span>
                        <strong style="font-size: 18px; color: #0B2B21; display: block; margin-top: 2px;"><?php echo $tier_counts['gold'] ?? 0; ?></strong>
                    </div>

                    <div style="background: #EFF6FF; padding: 12px; border-radius: 8px; border: 1px solid #BFDBFE;">
                        <span style="font-size: 11px; font-weight: 700; color: #1E40AF; text-transform: uppercase;">Platinum Tier</span>
                        <strong style="font-size: 18px; color: #0B2B21; display: block; margin-top: 2px;"><?php echo $tier_counts['platinum'] ?? 0; ?></strong>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>