<?php
// sessions must be started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simple check
if (!isset($_SESSION['isLoggedIn']) || $_SESSION['isLoggedIn'] !== true) {
    header("Location: login.php");
    exit;
}
?>
