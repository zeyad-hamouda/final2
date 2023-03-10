<?php
session_start();
require_once "db_init.php";

// Check if token is valid
if (isset($_GET["token"])) {
  $token = $_GET["token"];

  $stmt = $db->prepare("SELECT * FROM users WHERE token = ?");
  $stmt->execute([$token]);
  $user = $stmt->fetch();

  if (!$user) {
    $_SESSION["reset-password-error"] = "Invalid reset token.";
    header("Location: forgot_password.php");
    exit();
  }
} else {
  $_SESSION["reset-password-error"] = "Reset token not provided.";
  header("Location: forgot_password.php");
  exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $password = $_POST["password"];
  $confirm_password = $_POST["confirm_password"];

  // Validate input
  if ($password != $confirm_password) {
    $_SESSION["reset-password-error"] = "Passwords do not match.";
    header("Location: reset_password.php?token=$token");
    exit();
  }

  // Update the user's password in the database
  $password_hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $db->prepare("UPDATE users SET password = ?, token = NULL WHERE email = ?");
  $stmt->execute([$password_hash, $user["email"]]);

  $_SESSION["reset-password-success"] = "Password reset successfully.";
  header("Location: login.php");
  exit();
}
?>

<style>
		body {
			font-family: Arial, sans-serif;
		}

		form {
			background-color: #f2f2f2;
			padding: 20px;
			border-radius: 10px;
			max-width: 500px;
			margin: 0 auto;
			display: block;
		}

		input[type="text"],
		input[type="password"] {
			padding: 10px;
			width: 100%;
			border: none;
			border-radius: 5px;
			margin-bottom: 10px;
			box-sizing: border-box;
			font-size: 16px;
		}

		label {
			display: block;
			margin-bottom: 10px;
			font-size: 16px;
			font-weight: bold;
		}

		input[type="submit"] {
			background-color: #4CAF50;
			color: white;
			padding: 12px 20px;
			border: none;
			border-radius: 5px;
			cursor: pointer;
			font-size: 16px;
			margin-top: 10px;
			width: 100%;
			transition: background-color 0.3s ease;
		}

		input[type="submit"]:hover {
			background-color: #3e8e41;
		}

		.error {
			color: red;
			margin-bottom: 10px;
		}

		.active {
			display: block;
		}
        h2{
            text-align: center;
        }
	</style>
<h2>Reset Password</h2>

<form method="post" action="reset_password.php?token=<?php echo $token; ?>">
  <label for="password">New Password:</label>
  <input type="password" name="password" required>
  <label for="confirm_password">Confirm Password:</label>
  <input type="password" name="confirm_password" required>
  <input type="submit" value="Reset Password">
</form>