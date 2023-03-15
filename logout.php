<?php
session_start();
require_once "db_init.php";
// Logout functionality here

// Reset notification_sent for all users
$reset_notification_stmt = $db->prepare("UPDATE users SET notification_sent = '0' WHERE parent_id = :id AND is_sub_user = 1");
$reset_notification_stmt->bindValue(":id", $_SESSION["user_id"]);

if (!$reset_notification_stmt->execute()) {
    $error_info = $reset_notification_stmt->errorInfo();
    echo "Error resetting notification_sent column: " . $error_info[2];
}

// Destroy session and redirect to login page
session_destroy();
header("Location: login.php");
exit();
?>