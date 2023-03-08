<?php
session_start();
if (!isset($_SESSION["user_id"])) {
	header("Location: login.php");
	exit();
}

require_once "db_init.php";

// Retrieve the user's information from the database
$stmt = $db->prepare("SELECT username, email, dob FROM users WHERE id = :id");
$stmt->bindValue(":id", $_SESSION["user_id"]);
$stmt->execute();
$user = $stmt->fetch();

// Extract the first name from the username
$first_name = explode(" ", $user["username"])[0];
?>

<!DOCTYPE html>
<html>
<head>
	<title>Welcome</title>
</head>
<body>
	<h1>Welcome, <?php echo $first_name; ?>!</h1>
	<p>You are logged in as <?php echo $user["username"]; ?>.</p>
	<p>Your date of birth is <?php echo $user["dob"]; ?>.</p>
    <a href="add_debit.php?user_id=<?php echo $_SESSION['user_id']; ?>">Add Debit</a>

	<p><a href="logout.php">Log out</a></p>
</body>
</html>