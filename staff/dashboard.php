<?php
// staff/dashboard.php — staff home + guest search
require_once '../includes/staff_auth.php';
require_once '../config/db.php';
require_once '../includes/functions.php';

$query   = trim($_GET['q'] ?? '');

$query   = trim($_GET['q'] ?? '');
$results = [];
$searched = ($query !== '');

if ($searched) {
    $like = "%{$query}%";
    $stmt = $pdo->prepare(
        "SELECT * FROM guests
         WHERE full_name LIKE ? OR phone LIKE ? OR member_id LIKE ?
         ORDER BY full_name"
    );
    $stmt->execute([$like, $like, $like]);
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard — Jay Hiltin</title>
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
            <a href="dashboard.php" class="active">🏠 Dashboard</a>
            <a href="search.php" >🔍 Guest Search</a>
            <a href="bookings.php" title="Coming in phase 2">🛏️ Bookings</a>
            <a href="loyalty.php" title="Coming in phase 2">🎁 Loyalty Program</a>
            <a href="reports.php" title="Coming in phase 2">📊 Reports</a>
            <a href="settings.php" title="Coming in phase 2">⚙️ Settings</a>
        </nav>
        <a href="logout.php" class="sidebar-logout">⬅ Log Out</a>
    </aside>

    <!-- MAIN -->
    <main class="staff-main">
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>"
                       placeholder="Search by name, phone number or Member ID...">
                <button type="submit" class="btn-green">Search</button>
            </form>
        </div>

        <?php if (!$searched): ?>
            <div class="empty-state">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['staff_name']); ?> 👋</h2>
                <p>Search for a guest by name, phone number, or Member ID to view their profile.</p>
            </div>

        <?php elseif (count($results) === 0): ?>
            <div class="empty-state">
                <h2>No guests found</h2>
                <p>Nothing matches "<?php echo htmlspecialchars($query); ?>". Check the spelling or try a phone number.</p>
            </div>

        <?php else: ?>
            <h2 class="results-title">Guest Search Results (<?php echo count($results); ?>)</h2>

            <?php foreach ($results as $g):
                $photo = guestPhotoUrl($g['photo']);
            ?>
            <div class="guest-result">
                <div class="guest-result-photo">
                    <?php if ($photo): ?>
                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($g['full_name']); ?>">
                    <?php else: ?>
                        <div class="avatar-initials"><?php echo initials($g['full_name']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="guest-result-info">
                    <h3><?php echo htmlspecialchars($g['full_name']); ?></h3>
                    <p class="member-id">Member ID: <?php echo htmlspecialchars($g['member_id']); ?></p>
                    <span class="badge badge-<?php echo strtolower($g['membership_level']); ?>">
                        <?php echo htmlspecialchars($g['membership_level']); ?> Member
                    </span>
                    <p class="guest-contact">📞 <?php echo htmlspecialchars($g['phone']); ?>
                       &nbsp; ✉️ <?php echo htmlspecialchars($g['email']); ?></p>
                </div>

                <div class="guest-result-points">
                    <span>Total Points</span>
                    <strong><?php echo number_format($g['points']); ?></strong>
                    <a href="guest_view.php?id=<?php echo (int)$g['id']; ?>" class="btn-outline">View Profile</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>