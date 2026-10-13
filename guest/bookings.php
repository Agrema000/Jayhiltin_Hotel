<?php
// guest/bookings.php
require_once '../includes/header.php';

if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit;
}

$guest_id = $_SESSION['guest_id'];

// Fetch guest bookings
$stmt = $pdo->prepare("
    SELECT * FROM bookings 
    WHERE guest_id = ? 
    ORDER BY check_in DESC
");
$stmt->execute([$guest_id]);
$bookings = $stmt->fetchAll();
?>

<div class="container py-4" style="max-width: 900px;">

    <!-- Header Section -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="font-size: 26px; font-weight: 700; color: #0B2B21; margin: 0;">My Stay History</h2>
            <p style="color: #666; font-size: 14px; margin-top: 4px;">Track your reservations and earned loyalty points from every stay.</p>
        </div>
        <a href="../guest/rooms.php#book" class="btn-gold" style="padding: 10px 18px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600;">
            <i class="fas fa-plus" style="margin-right: 6px;"></i> Book New Stay
        </a>
    </div>

    <!-- Booking List Cards -->
    <div style="display: flex; flex-direction: column; gap: 16px;">
        <?php if (!empty($bookings)): ?>
            <?php foreach ($bookings as $b): ?>
                <?php 
                    // Dynamic badge styling based on booking status
                    $status_color = '#10B981'; // Green for Completed
                    $status_bg = 'rgba(16,185,129,0.1)';
                    if ($b['status'] === 'Confirmed' || $b['status'] === 'Checked-In') {
                        $status_color = '#3B82F6'; // Blue
                        $status_bg = 'rgba(59,130,246,0.1)';
                    } elseif ($b['status'] === 'Cancelled') {
                        $status_color = '#EF4444'; // Red
                        $status_bg = 'rgba(239,68,68,0.1)';
                    }
                ?>
                <div class="card" style="background: #fff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06); padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.03);">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid #F3F4F6; padding-bottom: 14px;">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #888; font-weight: 600;">Booking Reference</span>
                            <h4 style="font-size: 18px; font-weight: 700; color: #0B2B21; margin-top: 2px;">
                                #<?php echo htmlspecialchars($b['booking_ref'] ?? 'JH-' . $b['id']); ?>
                            </h4>
                        </div>
                        <span style="padding: 6px 14px; border-radius: 30px; font-size: 12px; font-weight: 700; background: <?php echo $status_bg; ?>; color: <?php echo $status_color; ?>;">
                            ● <?php echo htmlspecialchars($b['status']); ?>
                        </span>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 16px;">
                        <div>
                            <span style="font-size: 12px; color: #666; display: block;"><i class="fas fa-bed" style="color: #B99A61; margin-right: 6px;"></i> Room Type</span>
                            <strong style="font-size: 14px; color: #0B2B21; font-weight: 600;"><?php echo htmlspecialchars($b['room_type'] ?? 'Executive Suite'); ?></strong>
                        </div>

                        <div>
                            <span style="font-size: 12px; color: #666; display: block;"><i class="fas fa-calendar-check" style="color: #B99A61; margin-right: 6px;"></i> Check-In</span>
                            <strong style="font-size: 14px; color: #0B2B21; font-weight: 600;"><?php echo date('d M Y', strtotime($b['check_in'])); ?></strong>
                        </div>

                        <div>
                            <span style="font-size: 12px; color: #666; display: block;"><i class="fas fa-calendar-times" style="color: #B99A61; margin-right: 6px;"></i> Check-Out</span>
                            <strong style="font-size: 14px; color: #0B2B21; font-weight: 600;"><?php echo date('d M Y', strtotime($b['check_out'])); ?></strong>
                        </div>

                        <div>
                            <span style="font-size: 12px; color: #666; display: block;"><i class="fas fa-star" style="color: #B99A61; margin-right: 6px;"></i> Points Earned</span>
                            <strong style="font-size: 14px; color: #10B981; font-weight: 700;">+<?php echo number_format($b['points_earned'] ?? 100); ?> pts</strong>
                        </div>
                    </div>

                    <?php if (!empty($b['total_amount'])): ?>
                        <div style="background: #FAF8F5; padding: 10px 14px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 13px;">
                            <span style="color: #666;">Total Paid:</span>
                            <strong style="color: #0B2B21; font-size: 15px;">₦<?php echo number_format($b['total_amount'], 2); ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card" style="background: #fff; padding: 40px; border-radius: 14px; text-align: center; border: 1px solid rgba(0,0,0,0.06);">
                <div style="font-size: 40px; color: #D1D5DB; margin-bottom: 12px;"><i class="fas fa-calendar-minus"></i></div>
                <h3 style="font-size: 18px; font-weight: 700; color: #0B2B21; margin-bottom: 6px;">No Stays Recorded Yet</h3>
                <p style="color: #666; font-size: 14px; max-width: 400px; margin: 0 auto 20px;">Book your first stay with Jay Hiltin Hotel & Suites to start accumulating rewards points.</p>
                <a href="../index.php" class="btn-gold" style="padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 13px;">Explore Rooms</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once '../includes/footer.php'; ?>