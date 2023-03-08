<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
	<title>Login and Registration</title>
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
		input[type="password"],
		input[type="email"],
		input[type="date"] {
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

		.register-link {
			text-align: center;
			margin-top: 20px;
			font-size: 14px;
			cursor: pointer;
			color: blue;
			text-decoration: underline;
		}

		#register-form {
			display: none;
		}

		.active {
			display: block;
		}
	</style>
</head>
<body>

<form id="login-form" class="active" method="post" action="login_handler.php">
	<label>Username:</label>
	<input type="text" name="username" required>
	<label>Password:</label>
	<input type="password" name="password" required>
	<input type="submit" value="Login">
	<?php if (isset($_SESSION["login-error"])): ?>
		<p class="error"><?php echo $_SESSION["login-error"]; ?></p>
		<?php unset($_SESSION["login-error"]); ?>
	<?php endif; ?>
	<p class="register-link" id="register-toggle">Don't have an account?</p>
</form>

<form id="register-form" method="post" action="register_handler.php">
	<label>Username:</label>
	<input type="text" name="username" required>
	<label>Email:</label>
	<input type="email" name="email" required>
	<label>Date of Birth:</label>
	<input type="date" name="dob" required>
	<label>Password:</label>
	<input type="password" name="password" required>
	<label>Confirm Password:</label>
	<input type="password" name="password-confirm" required>
	<input type="submit" value="Register">
	<?php if (isset($_SESSION["register-error"])): ?>
		<p class="error"><?php echo $_SESSION["register-error"]; ?></p>
		<?php unset($_SESSION["register-error"]); ?>
	<?php endif; ?>
</form>

<script>
	var loginForm = document.getElementById("login-form");
	var registerForm = document.getElementById("register-form");
	var registerLink = document.getElementById("register-toggle");

	registerLink.addEventListener("click", function() {
		toggleForm("register");
	});

	function toggleForm(form) {
		if (form == "login") {
			loginForm.style.display = "block";
			registerForm.style.display = "none";
		} else if (form == "register") {
			loginForm.style.display = "none";
			registerForm.style.display = "block";
		}
	}
</script>
</body>