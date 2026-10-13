<?php
// includes/guest_auth.php — guard for guest-side pages
// Put at the TOP of any page only logged-in guests should see.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['guest_id'])) {
    header('Location: login.php');
    exit;   // stop the page from loading any further — this line is CRITICAL
}