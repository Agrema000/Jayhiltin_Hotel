<?php
// includes/header.php
$base = file_exists('config/db.php') ? '' : '../';
require_once $base . 'config/db.php';
require_once $base . 'includes/functions.php';

$is_guest_logged_in = isset($_SESSION['guest_id']);
$current_guest = null;

if ($is_guest_logged_in) {
    $current_guest = getGuestFullProfile($pdo, $_SESSION['guest_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jay Hiltin Hotel & Suites | Loyalty Rewards</title>
    <link rel="stylesheet" href="<?php echo $base; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- Header-only fixes: remove brand underline + mobile menu toggle --- */
        .nav-brand,
        .nav-brand:hover,
        .nav-brand:visited,
        .nav-brand * {
            text-decoration: none;
        }

        .site-nav {
            position: relative; /* anchors the mobile dropdown below */
        }

        .nav-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--gold-light, #d4af37);
            font-size: 22px;
            cursor: pointer;
            padding: 4px 8px;
        }

        @media (max-width: 768px) {
            .nav-toggle {
                display: block;
            }

            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                flex-direction: column;
                background: #0d2b21;
                z-index: 999;
            }

            .nav-links.active {
                display: flex;
            }

            .nav-links a {
                width: 100%;
                padding: 14px 20px;
                border-top: 1px solid rgba(255, 255, 255, 0.08);
            }
        }
    </style>
</head>
<body>

<nav class="site-nav">
    <a href="<?php echo $base; ?>index.php" class="nav-brand">
        <div class="brand-monogram tiny">JH</div>
        <div>
            <strong>JAY HILTIN</strong><br>
            <span style="font-size: 8px; color: var(--gold-light); letter-spacing: 1px;">HOTEL & SUITES</span>
        </div>
    </a>

    <button type="button" class="nav-toggle" aria-label="Toggle menu">
        <i class="fas fa-bars"></i>
    </button>

    <div class="nav-links">
        <a href="<?php echo $base; ?>index.php">Home</a>
        <a href="<?php echo $base; ?>guest/rewards.php">Rewards</a>
        <?php if ($is_guest_logged_in): ?>
            <a href="<?php echo $base; ?>guest/dashboard.php">Dashboard</a>
            <a href="<?php echo $base; ?>guest/bookings.php">My Bookings</a>
            <a href="<?php echo $base; ?>guest/profile.php">Profile</a>
            <a href="<?php echo $base; ?>guest/logout.php" style="color: var(--gold-light);">Logout</a>
        <?php else: ?>
            <a href="<?php echo $base; ?>guest/login.php">Guest Login</a>
            <a href="<?php echo $base; ?>guest/register.php" class="btn-gold nav-book">Join Rewards</a>
        <?php endif; ?>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () {
            navLinks.classList.toggle('active');
            const icon = navToggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            }
        });
    }
});
</script>