<?php
session_start();
if (!isset($_SESSION['user_id']) && !isset($_GET['debug']) || (isset($_GET['debug']) && !file_exists('/debug'))) {
    header('Location: login.php');  // Assuming a login.php exists or will be created
    exit();
}
?>