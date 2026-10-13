<?php
require_once 'config/db.php';

echo "✅ Connected to database successfully!<br>";

$stmt = $pdo->query("SELECT full_name, points FROM guests ORDER BY id LIMIT 3");
foreach ($stmt as $row) {
    echo $row['full_name'] . ' — ' . $row['points'] . ' points<br>';
}

echo 'Next Member ID would be: ' . generateMemberId($pdo);