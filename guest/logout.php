<?php
// guest/logout.php
require_once '../config/db.php';

unset($_SESSION['guest_id']);
unset($_SESSION['guest_name']);
session_destroy();

header('Location: login.php');
exit;