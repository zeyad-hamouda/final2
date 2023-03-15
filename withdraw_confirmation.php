<?php
session_start();
if (!isset($_SESSION["user_id"])) {
	header("Location: login.php");
	exit();
}

require_once "db_init.php";

$user_id = $_SESSION["user_id"];
$amount = $_GET["amount"];


// retrieve the user's current balance from the database
$balance = $db->query("SELECT balance FROM users WHERE id = $user_id")->fetchColumn();

// display the deposit confirmation message with the updated balance
echo "Thank you for withdrawing $amount. Your current balance is now $balance.";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Withdrawal Confirmation</title>
    <meta http-equiv="refresh" content="5;url=profile.php">
</head>
</html>
