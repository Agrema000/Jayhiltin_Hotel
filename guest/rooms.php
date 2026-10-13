<?php
// guest/rooms.php
require_once '../includes/header.php';

if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit;
}

$guest = getGuestFullProfile($pdo,$_SESSION['guest_id']);
$message = '';$error = '';

// Helper function to dynamically discover table columns in MySQL
function getTableColumns($pdo,$table) {
    try {
        $stmt =$pdo->query("DESCRIBE `{$table}`");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Exception $e) {
        return [];
    }
}

$room_cols    = getTableColumns($pdo, 'rooms');
$booking_cols = getTableColumns($pdo, 'bookings');

// Find actual price column in rooms table
$room_price_col = 'price';
foreach (['price', 'rate', 'price_per_night', 'cost', 'amount'] as $c) {
    if (in_array($c,$room_cols)) {
        $room_price_col =$c;
        break;
    }
}

// Find actual room type/name column in rooms table
$room_type_col = 'room_type';
foreach (['room_type', 'type', 'category', 'title', 'name'] as $c) {
    if (in_array($c,$room_cols)) {
        $room_type_col =$c;
        break;
    }
}

// Handle Room Booking Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_room') {
        $room_id     = (int)($_POST['room_id'] ?? 0);
        $check_in    = trim($_POST['check_in'] ?? '');
        $check_out   = trim($_POST['check_out'] ?? '');
        $guests_cnt  = (int)($_POST['guests'] ?? 1);
        $special_req = trim($_POST['special_requests'] ?? '');

        if (!$room_id || empty($check_in) || empty($check_out)) {
            $error = "Please select check-in and check-out dates.";
        } else {
            $in_date  = new DateTime($check_in);
            $out_date = new DateTime($check_out);
            $today    = new DateTime(date('Y-m-d'));
        if ($in_date < $today) {$error = "Check-in date cannot be in the past.";
        } elseif ($out_date <= $in_date) {$error = "Check-out date must be after check-in date.";
        } else {
            // Fetch selected room
            $stmt =$pdo->prepare("SELECT * FROM rooms WHERE id = ?");
            $stmt->execute([$room_id]);
            $room =$stmt->fetch();

            if (!$room) {$error = "Selected room was not found.";
            } else {
                // Calculate nights & total price
                $nights = $in_date->diff($out_date)->days;
                $price_per_night = (float)($room[$room_price_col] ?? 0);$total_amount = $nights * $price_per_night;

                // Unique booking reference
                $booking_ref = 'JH-BK-' . strtoupper(substr(md5(uniqid()), 0, 6));

                // Build dynamic insert values based on existing columns in bookings table
                $data = [];
                if (in_array('guest_id', $booking_cols))   $data['guest_id']  =$guest['id'];
                if (in_array('room_id', $booking_cols))    $data['room_id']   =$room_id;
                if (in_array('check_in', $booking_cols))   $data['check_in']  =$check_in;
                if (in_array('check_out', $booking_cols))  $data['check_out'] =$check_out;
                
                // Amount column
                foreach (['total_amount', 'amount', 'total_price', 'price'] as $amt_c) {
                    if (in_array($amt_c, $booking_cols)) {$data[$amt_c] =$total_amount;
                        break;
                    }
                }

                // Reference column
                foreach (['reference', 'booking_ref', 'ref_code', 'code'] as $ref_c) {
                    if (in_array($ref_c, $booking_cols)) {$data[$ref_c] =$booking_ref;
                        break;
                    }
                }

                // Status column
                if (in_array('status', $booking_cols)) {$data['status'] = 'Confirmed';
                }

                // Guest count column
                foreach (['guests', 'guest_count', 'no_of_guests'] as $g_c) {
                    if (in_array($g_c, $booking_cols)) {$data[$g_c] =$guests_cnt;
                        break;
                    }
                }

                // Special requests column
                foreach (['special_requests', 'notes', 'requests'] as $req_c) {
                    if (in_array($req_c, $booking_cols)) {$data[$req_c] =$special_req;
                        break;
                    }
                }

                try {
                    $fields = implode('`, `', array_keys($data));
                    $placeholders = implode(', ', array_fill(0, count($data), '?'));

                    $insert_stmt =$pdo->prepare("INSERT INTO `bookings` (`{$fields}`) VALUES ({$placeholders})");
                    $insert_stmt->execute(array_values($data));

                    $message = "Reservation confirmed! Your booking reference is <strong>{$booking_ref}</strong>. Total ({$nights} night" . ($nights > 1 ? 's' : '') . "): <strong>₦" . number_format($total_amount, 2) . "</strong>.";
                } catch (Throwable $e) {$error = "Booking failed: " . htmlspecialchars($e->getMessage());
                }
            }
        }
    }
}

// Category & Price Sorting Filters
$category_filter =$_GET['category'] ?? 'all';
$sort_order      =$_GET['sort'] ?? 'asc';

// Build query dynamically
$sql = "SELECT * FROM rooms WHERE 1=1";
$params = [];

if ($category_filter === 'budget') {
    $sql .= " AND {$room_price_col} < 30000";
} elseif ($category_filter === 'normal') {
    $sql .= " AND {$room_price_col} BETWEEN 30000 AND 70000";
} elseif ($category_filter === 'expensive') {
    $sql .= " AND {$room_price_col} > 70000";
}

$sql .= " ORDER BY {$room_price_col} " . ($sort_order === 'desc' ? 'DESC' : 'ASC');

$rooms_stmt = $pdo->prepare($sql);
$rooms_stmt->execute($params);
$rooms =$rooms_stmt->fetchAll();
?>

<div class="container py-4" style="max-width: 1050px;">

    <!-- Top Hero Header -->
    <div class="card mb-4" style="background: linear-gradient(135deg, #0B2B21 0%, #154536 100%); color: #fff; padding: 28px; border-radius: 16px; box-shadow: 0 10px 30px rgba(11,43,33,0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <span style="text-transform: uppercase; letter-spacing: 1.5px; font-size: 11px; padding: 4px 10px; border-radius: 20px; background: rgba(185,154,97,0.2); color: #B99A61; border: 1px solid rgba(185,154,97,0.4);">Jay Hiltin Suites</span>
                <h2 style="font-size: 28px; font-weight: 700; margin-top: 8px; color: #fff;">Explore Accommodations</h2>
                <p style="color: rgba(255,255,255,0.7); font-size: 14px; margin: 0;">Find the perfect room tailored to your comfort and budget preferences.</p>
            </div>
            <div>
                <a href="dashboard.php" style="background: rgba(255,255,255,0.1); color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; border: 1px solid rgba(255,255,255,0.2);">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success mb-4" style="background: rgba(16,185,129,0.1); border: 1px solid #10B981; color: #065F46; padding: 14px 18px; border-radius: 10px;">
            <i class="fas fa-check-circle" style="margin-right: 8px;"></i> <?php echo $message; ?>
            <div style="margin-top: 8px;">
                <a href="dashboard.php" style="color: #065F46; font-weight: 700; text-decoration: underline;">View in your reservations list →</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error mb-4" style="background: rgba(239,68,68,0.1); border: 1px solid #EF4444; color: #991B1B; padding: 14px 18px; border-radius: 10px;">
            <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Filter & Tier Navigation Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; background: #fff; padding: 16px 20px; border-radius: 12px; border: 1px solid rgba(0,0,0,0.06); box-shadow: 0 4px 12px rgba(0,0,0,0.02);">
        
        <!-- Category Filter Pills -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="rooms.php?category=all&sort=<?php echo $sort_order; ?>" style="text-decoration: none; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; <?php echo $category_filter === 'all' ? 'background: #0B2B21; color: #fff;' : 'background: #F3F4F6; color: #4B5563;'; ?>">
                All Tiers
            </a>
            <a href="rooms.php?category=budget&sort=<?php echo $sort_order; ?>" style="text-decoration: none; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; <?php echo $category_filter === 'budget' ? 'background: #0B2B21; color: #fff;' : 'background: #F3F4F6; color: #4B5563;'; ?>">
                Budget (< ₦30k)
            </a>
            <a href="rooms.php?category=normal&sort=<?php echo $sort_order; ?>" style="text-decoration: none; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; <?php echo $category_filter === 'normal' ? 'background: #0B2B21; color: #fff;' : 'background: #F3F4F6; color: #4B5563;'; ?>">
                Executive / Normal (₦30k - ₦70k)
            </a>
            <a href="rooms.php?category=expensive&sort=<?php echo $sort_order; ?>" style="text-decoration: none; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; <?php echo $category_filter === 'expensive' ? 'background: #0B2B21; color: #fff;' : 'background: #F3F4F6; color: #4B5563;'; ?>">
                Luxury / Expensive (> ₦70k)
            </a>
        </div>

        <!-- Price Sort Dropdown -->
        <div>
            <form action="rooms.php" method="GET" style="display: flex; align-items: center; gap: 8px; margin: 0;">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                <label style="font-size: 12px; color: #6B7280; font-weight: 600;">Sort By:</label>
                <select name="sort" onchange="this.form.submit()" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #D1D5DB; font-size: 13px; color: #374151; background: #fff; cursor: pointer;">
                    <option value="asc" <?php echo $sort_order === 'asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="desc" <?php echo $sort_order === 'desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
            </form>
        </div>

    </div>

    <!-- Rooms Display Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px;">
        <?php if (!empty($rooms)): ?>
            <?php foreach ($rooms as$room): ?>
                <?php 
                    $price = (float)($room[$room_price_col] ?? 0);$type  = $room[$room_type_col] ?? 'Standard Room';
                    $room_num =$room['room_number'] ?? $room['number'] ?? $room['id'];
                    $status =$room['status'] ?? 'Available';

                    if ($price > 70000) {
                        $badge_label = 'Luxury Tier';$badge_bg = '#FEF3C7';
                        $badge_color = '#92400E';
                    } elseif ($price >= 30000) {
                        $badge_label = 'Executive Tier';$badge_bg = '#E0E7FF';
                        $badge_color = '#3730A3';
                    } else {
                        $badge_label = 'Budget Tier';$badge_bg = '#E1F5FE';
                        $badge_color = '#0277BD';
                    }
                ?>
                <div style="background: #fff; border-radius: 14px; border: 1px solid rgba(0,0,0,0.06); overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between;">
                    
                    <div>
                        <!-- Header Banner -->
                        <div style="background: linear-gradient(135deg, #154536 0%, #0B2B21 100%); padding: 22px; color: #fff; position: relative;">
                            <span style="position: absolute; top: 16px; right: 16px; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 12px; background: <?php echo $badge_bg; ?>; color: <?php echo$badge_color; ?>;">
                                <?php echo $badge_label; ?>
                            </span>
                            <div style="font-size: 12px; color: #B99A61; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px;">
                                Room <?php echo htmlspecialchars($room_num); ?>
                            </div>
                            <h3 style="font-size: 20px; font-weight: 700; margin: 0; color: #fff;">
                                <?php echo htmlspecialchars($type); ?>
                            </h3>
                        </div>

                        <!-- Content Body -->
                        <div style="padding: 20px;">
                            <div style="display: flex; align-items: baseline; gap: 6px; margin-bottom: 16px;">
                                <span style="font-size: 26px; font-weight: 800; color: #0B2B21;">₦<?php echo number_format($price, 2); ?></span>
                                <span style="font-size: 13px; color: #6B7280;">/ night</span>
                            </div>

                            <p style="font-size: 13px; color: #666; line-height: 1.5; margin-bottom: 16px;">
                                <?php echo htmlspecialchars($room['description'] ?? 'Features climate control, high-speed Wi-Fi, premium bedding, and 24/7 room service.'); ?>
                            </p>

                            <!-- Amenities -->
                            <div style="display: flex; gap: 12px; flex-wrap: wrap; font-size: 12px; color: #4B5563; margin-bottom: 20px;">
                                <span style="background: #F3F4F6; padding: 4px 10px; border-radius: 6px;"><i class="fas fa-wifi" style="color: #B99A61;"></i> Wi-Fi</span>
                                <span style="background: #F3F4F6; padding: 4px 10px; border-radius: 6px;"><i class="fas fa-tv" style="color: #B99A61;"></i> Smart TV</span>
                                <span style="background: #F3F4F6; padding: 4px 10px; border-radius: 6px;"><i class="fas fa-snowflake" style="color: #B99A61;"></i> AC</span>
                                <span style="background: #F3F4F6; padding: 4px 10px; border-radius: 6px;"><i class="fas fa-utensils" style="color: #B99A61;"></i> Room Service</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div style="padding: 0 20px 20px 20px;">
                        <button type="button" onclick="openBookingModal('<?php echo $room['id']; ?>', '<?php echo addslashes($type); ?>', '<?php echo $price; ?>', '<?php echo$room_num; ?>')" style="width: 100%; background: #0B2B21; color: #fff; padding: 12px; border: none; border-radius: 8px; font-weight: 600; font-size: 14px; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px;">
                            <i class="fas fa-calendar-check"></i> Book Room
                        </button>
                    </div>

                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="grid-column: 1 / -1; background: #fff; padding: 40px; border-radius: 12px; text-align: center; color: #6B7280;">
                <p style="font-size: 16px; margin-bottom: 10px;">No rooms found matching your filter criteria.</p>
                <a href="rooms.php?category=all" style="color: #B99A61; font-weight: 700; text-decoration: none;">Reset Room Filters →</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Booking Modal Overlay -->
<div id="bookingModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; backdrop-filter: blur(4px); justify-content: center; align-items: center;">
    <div style="background: #fff; width: 90%; max-width: 500px; border-radius: 16px; padding: 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.25); position: relative;">
        
        <button type="button" onclick="closeBookingModal()" style="position: absolute; top: 16px; right: 16px; background: none; border: none; font-size: 20px; cursor: pointer; color: #888;">&times;</button>

        <h3 style="font-size: 20px; font-weight: 700; color: #0B2B21; margin-bottom: 4px;">Confirm Reservation</h3>
        <p id="modalRoomTitle" style="font-size: 14px; color: #B99A61; font-weight: 600; margin-bottom: 20px;"></p>

        <form action="rooms.php" method="POST">
            <input type="hidden" name="action" value="book_room">
            <input type="hidden" name="room_id" id="modalRoomId">
            <input type="hidden" id="modalRoomPrice">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Check-In Date</label>
                    <input type="date" name="check_in" id="modalCheckIn" required min="<?php echo date('Y-m-d'); ?>" onchange="calculateModalTotal()" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #D1D5DB; font-size: 14px;">
                </div>
                <div>
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Check-Out Date</label>
                    <input type="date" name="check_out" id="modalCheckOut" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" onchange="calculateModalTotal()" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #D1D5DB; font-size: 14px;">
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Number of Guests</label>
                <select name="guests" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #D1D5DB; font-size: 14px;">
                    <option value="1">1 Guest</option>
                    <option value="2">2 Guests</option>
                    <option value="3">3 Guests</option>
                    <option value="4">4 Guests</option>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">Special Requests (Optional)</label>
                <textarea name="special_requests" rows="2" placeholder="e.g. Quiet room, extra towels, late check-in" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #D1D5DB; font-size: 13px;"></textarea>
            </div>

            <div style="background: #FAF8F5; border: 1px dashed rgba(185,154,97,0.5); padding: 15px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <span style="font-size: 12px; color: #6B7280; display: block;">Calculated Total</span>
                    <span id="modalNightsCount" style="font-size: 11px; color: #B99A61; font-weight: 700;">Select dates</span>
                </div>
                <div style="font-size: 20px; font-weight: 800; color: #0B2B21;" id="modalTotalAmount">
                    ₦0.00
                </div>
            </div>

            <button type="submit" style="width: 100%; background: #0B2B21; color: #fff; padding: 12px; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer;">
                Confirm & Complete Booking
            </button>
        </form>
    </div>
</div>

<script>
function openBookingModal(roomId, roomType, price, roomNum) {
    document.getElementById('modalRoomId').value = roomId;
    document.getElementById('modalRoomPrice').value = price;
    document.getElementById('modalRoomTitle').innerText = roomType + " (Room " + roomNum + ")";
    
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    document.getElementById('modalCheckIn').value = today.toISOString().split('T')[0];
    document.getElementById('modalCheckOut').value = tomorrow.toISOString().split('T')[0];

    document.getElementById('bookingModal').style.display = 'flex';
    calculateModalTotal();
}

function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
}

function calculateModalTotal() {
    const pricePerNight = parseFloat(document.getElementById('modalRoomPrice').value) || 0;
    const inVal = document.getElementById('modalCheckIn').value;
    const outVal = document.getElementById('modalCheckOut').value;

    if (inVal && outVal) {
        const d1 = new Date(inVal);
        const d2 = new Date(outVal);
        const diffTime = d2 - d1;
        const nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (nights > 0) {
            const total = nights * pricePerNight;
            document.getElementById('modalNightsCount').innerText = nights + (nights === 1 ? ' night' : ' nights');
            document.getElementById('modalTotalAmount').innerText = '₦' + total.toLocaleString('en-US', {minimumFractionDigits: 2});
            return;
        }
    }

    document.getElementById('modalNightsCount').innerText = 'Invalid dates';
    document.getElementById('modalTotalAmount').innerText = '₦0.00';
}
</script>

<?php require_once '../includes/footer.php'; ?>