</main>

<footer style="margin-top: auto; background: #0B2B21; color: rgba(255,255,255,0.7); text-align: center; padding: 24px 20px; font-size: 13px; border-top: 1px solid rgba(255,255,255,0.08); width: 100%;">
    <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; gap: 8px;">
        <div>
            <strong style="color: #ffffff;">Jay Hiltin Hotel & Suites</strong> &mdash; Excellence in Hospitality
        </div>
        <div>
            &copy; <?php echo date('Y'); ?> Jay Hiltin Hotel & Suites. All rights reserved.
            <a href="<?php echo $base; ?>staff/login.php" title="Staff Access" aria-label="Staff Access" style="color: rgba(255,255,255,0.2); text-decoration: none; margin-left: 8px; padding: 6px; display: inline-block;">log-in as STAFF</a>
        </div>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const mobileNav = document.getElementById('mobileNav');

    if (hamburgerBtn && mobileNav) {
        hamburgerBtn.addEventListener('click', function() {
            mobileNav.classList.toggle('open');
            const icon = hamburgerBtn.querySelector('i');
            if (icon) {
                if (mobileNav.classList.contains('open')) {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                } else {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            }
        });
    }
});
</script>
</body>
</html>