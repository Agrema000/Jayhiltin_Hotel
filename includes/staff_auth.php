<?php
// includes/staff_auth.php — guard for staff-side pages
// Put at the TOP of any page only logged-in staff should see.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}