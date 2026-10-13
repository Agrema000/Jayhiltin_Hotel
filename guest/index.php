<?php
// guest/index.php
require_once '../includes/header.php';
?>

<section class="hero" style="background: linear-gradient(rgba(11, 43, 33, 0.85), rgba(11, 43, 33, 0.85)), url('../config/images/cover.jpg') center/cover no-repeat;">
    <div class="hero-content">
        <p class="hero-kicker">MORE STAYS. MORE REWARDS.</p>
        <h1 class="hero-title">More Stays.<br>More Rewards.</h1>
        <p class="hero-sub">Join Jay Hiltin Rewards and be part of something special. Earn points on every stay and unlock exclusive member privileges.</p>
        
        <div class="hero-buttons">
            <?php if (isset($_SESSION['guest_id'])): ?>
                <a href="dashboard.php" class="btn-gold">Go to Dashboard</a>
                <a href="rewards.php" class="btn-ghost">View Available Rewards</a>
            <?php else: ?>
                <a href="register.php" class="btn-gold">Join Now</a>
                <a href="login.php" class="btn-ghost">Member Login</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="features">
    <h2>Why Join Jay Hiltin Rewards?</h2>
    
    <div class="features-grid">
        <div class="feature-card">
            <span class="feature-icon">⭐</span>
            <h3>Earn Points</h3>
            <p>Earn points for every qualifying stay and room booking across our suites.</p>
        </div>

        <div class="feature-card">
            <span class="feature-icon">🎁</span>
            <h3>Redeem Rewards</h3>
            <p>Redeem rewards for room credits, complimentary meals, and exclusive perks.</p>
        </div>

        <div class="feature-card">
            <span class="feature-icon">💎</span>
            <h3>Exclusive Offers</h3>
            <p>Access exclusive offers, room upgrades, and priority late checkouts.</p>
        </div>

        <div class="feature-card">
            <span class="feature-icon">👤</span>
            <h3>A More Personalized Experience</h3>
            <p>Enjoy a tailored hospitality experience designed around your preferences.</p>
        </div>
    </div>

    <?php if (!isset($_SESSION['guest_id'])): ?>
        <div class="features-cta">
            <a href="register.php" class="btn-gold">Create Free Account</a>
        </div>
    <?php endif; ?>
</section>

<?php require_once '../includes/footer.php'; ?>