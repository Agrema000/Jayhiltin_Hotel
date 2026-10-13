<?php
// includes/functions.php


if (!function_exists('initials')) {
    function initials($name) {
        $words = array_filter(explode(' ', trim($name)));
        $initials = '';
        foreach ($words as $w) {
            $initials .= strtoupper($w[0]);
        }
        return substr($initials, 0, 2) ?: 'G';
    }
}

/**
 * Returns the proper image path for a guest photo or null if none exists.
 */
if (!function_exists('guestPhotoUrl')) {
    function guestPhotoUrl($photo = null, $fullName = '') {
        $baseDir = __DIR__ . '/../assets/uploads/';
        $webPath = '../assets/uploads/';

        // 1. If explicit photo path or filename exists in database
        if (!empty($photo)) {
            if (strpos($photo, 'http://') === 0 || strpos($photo, 'https://') === 0) {
                return $photo;
            }
            $filename = basename($photo);
            if (file_exists($baseDir . $filename)) {
                return $webPath . $filename;
            }
        }

        // 2. Fallback: Match by guest's first name (e.g., amina.jpg, david.jpg, emeka.jpg)
        if (!empty($fullName)) {
            $firstName = strtolower(trim(explode(' ', $fullName)[0]));
            
            if (file_exists($baseDir . $firstName . '.jpg')) {
                return $webPath . $firstName . '.jpg';
            }
            if (file_exists($baseDir . $firstName . '.png')) {
                return $webPath . $firstName . '.png';
            }
            if (file_exists($baseDir . $firstName . '.jpeg')) {
                return $webPath . $firstName . '.jpeg';
            }
        }

        return null;
    }
}
if (!function_exists('getGuestFullProfile')) {
    function getGuestFullProfile($pdo, $user_id) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
// Generate unique Member ID (e.g. JH-000124)
function generateMemberID($pdo) {
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM guests");
    $row = $stmt->fetch();
    $next_id = ($row['max_id'] ?? 0) + 124;
    return 'JH-' . str_pad($next_id, 6, '0', STR_PAD_LEFT);
}

// Fetch complete profile data of a guest with aggregated stats
function getGuestFullProfile($pdo, $guest_id) {
    $stmt = $pdo->prepare("
        SELECT g.*, 
        COUNT(b.id) AS total_stays,
        MAX(b.check_in) AS last_stay
        FROM guests g
        LEFT JOIN bookings b ON g.id = b.guest_id AND b.status = 'Completed'
        WHERE g.id = ?
        GROUP BY g.id
    ");
    $stmt->execute([$guest_id]);
    return $stmt->fetch();
}

// Update membership tier dynamically based on completed stays & points
function updateMembershipLevel($pdo, $guest_id) {
    $profile = getGuestFullProfile($pdo, $guest_id);
    if (!$profile) return;

    $total_stays = $profile['total_stays'] ?? 0;
    $points = $profile['points'] ?? 0;

    $level = 'Bronze';
    if ($total_stays >= 10 || $points >= 4000) {
        $level = 'Platinum';
    } elseif ($total_stays >= 5 || $points >= 2000) {
        $level = 'Gold';
    } elseif ($total_stays >= 2 || $points >= 1000) {
        $level = 'Silver';
    }

    $update = $pdo->prepare("UPDATE guests SET membership_level = ? WHERE id = ?");
    $update->execute([$level, $guest_id]);
}

// Render membership tier badge HTML
function renderTierBadge($level) {
    $badgeClass = strtolower($level);
    return "<span class='badge badge-{$badgeClass}'>{$level} Member</span>";
}

// Extract initials when profile photo is absent
function getAvatarInitials($name) {
    $words = explode(' ', trim($name));
    $initials = '';
    if (count($words) >= 2) {
        $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } else {
        $initials = strtoupper(substr($name, 0, 2));
    }
    return $initials;
}

// Resolve uploaded profile photo path safely
function getProfilePhotoPath($photo) {
    if (!empty($photo) && file_exists(__DIR__ . '/../assets/uploads/' . $photo)) {
        return 'assets/uploads/' . $photo;
    }
    return null;
}
?>