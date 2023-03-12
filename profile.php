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

// Process the bill payment form submission
if (isset($_POST["pay_bill"])) {

    // Retrieve the form data
    $amount = $_POST["amount"];
    $bill_type = $_POST["bill_type"];

    // Retrieve the user's current balance from the database
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = :user_id");
    $stmt->bindValue(":user_id", $_SESSION["user_id"]);
    $stmt->execute();
    $balance = $stmt->fetchColumn();


    // Validate the form data
    $errors = [];
    if (!is_numeric($amount) || $amount <= 0) {
        $errors[] = "Please enter a valid amount.";
    }

    if (!in_array($bill_type, ["electricity", "water", "internet"])) {
        $errors[] = "Please select a valid bill type.";
    }

    // Check if the user has sufficient balance
    if ($amount > $balance) {
        $errors[] = "Insufficient balance. Please add funds to your account.";
    }

    // Retrieve the value of the autopay checkbox
    $autopay = isset($_POST["autopay"]) ? true : false;

    // If there are no errors, update the database with the bill payment details and balance
    if (empty($errors)) {
        // Subtract the bill amount from the user's balance
        $new_balance = $balance - $amount;

        // Update the user's balance in the database with the new balance
        $stmt = $db->prepare("UPDATE users SET balance = :new_balance WHERE id = :user_id");
        $stmt->bindValue(":new_balance", $new_balance);
        $stmt->bindValue(":user_id", $_SESSION["user_id"]);
        $stmt->execute();

        // Insert the bill payment details into the database
        $stmt = $db->prepare("INSERT INTO bill_payments (user_id, amount, bill_type, date, is_autopay) VALUES (:user_id, :amount, :bill_type, :date, :is_autopay)");
        $stmt->bindValue(":user_id", $_SESSION["user_id"]);
        $stmt->bindValue(":amount", $amount);
        $stmt->bindValue(":bill_type", $bill_type);
        $stmt->bindValue(":date", date("Y-m-d H:i:s"));
        $stmt->bindValue(":is_autopay", $autopay);
        $stmt->execute();

		// If autopay is selected, insert the payment information into the autopayments table with the autopay boolean set to true
		if ($autopay) {
			$stmt = $db->prepare("INSERT INTO autopay (user_id, bill_type, amount, next_payment_date) VALUES (:user_id, :bill_type, :amount, :next_payment_date)");
			$stmt->bindValue(":user_id", $_SESSION["user_id"]);
			$stmt->bindValue(":bill_type", $bill_type);
			$stmt->bindValue(":amount", $amount);
			// Calculate the date for the next payment
			$next_payment_date = date('Y-m-d', strtotime('+1 month'));
			$stmt->bindValue(":next_payment_date", $next_payment_date);
			$stmt->execute();
		}

		header("Location: payment_confirmation.php?amount=$amount&bill_type=$bill_type&username=$first_name");
		exit();
	} else {
		$message = implode("<br>", $errors);
	}
}
// Process the deposit form submission
if (isset($_POST["deposit_submit"])) {
    // Retrieve the form data
    $amount = $_POST["deposit"];
    $date = $_POST["deposit_date"];

    // Validate the form data
    $errors = [];
    if (!is_numeric($amount) || $amount <= 0) {
        $errors[] = "Please enter a valid amount.";
    }

    if (empty($date)) {
        $errors[] = "Please select a date.";
    }

    // If there are no errors, update the database with the deposit details and balance
    if (empty($errors)) {
        // Retrieve the user's current balance from the database
        $stmt = $db->prepare("SELECT balance FROM users WHERE id = :user_id");
        $stmt->bindValue(":user_id", $_SESSION["user_id"]);
        $stmt->execute();
        $balance = $stmt->fetchColumn();

        // Add the deposit amount to the user's balance
        $new_balance = $balance + $amount;

        // Update the user's balance in the database with the new balance
        $stmt = $db->prepare("UPDATE users SET balance = :new_balance WHERE id = :user_id");
        $stmt->bindValue(":new_balance", $new_balance);
        $stmt->bindValue(":user_id", $_SESSION["user_id"]);
        $stmt->execute();

        // Insert the deposit details into the database
        $stmt = $db->prepare("INSERT INTO deposits (user_id, amount, date) VALUES (:user_id, :amount, :date)");
        $stmt->bindValue(":user_id", $_SESSION["user_id"]);
        $stmt->bindValue(":amount", $amount);
        $stmt->bindValue(":date", $date);
        $stmt->execute();

        header("Location: deposit_confirmation.php?amount=$amount&username=$first_name");
        exit();
    } else {
        $message = implode("<br>", $errors);
    }
}

if (isset($_POST['submit'])) {
    // Retrieve the form data
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $number = $_POST['number'];

    // Validate the form data
    $errors = [];
    if (empty($username)) {
        $errors[] = "Please enter a username.";
    }
    if (empty($email)) {
        $errors[] = "Please enter an email.";
    }
    if (!empty($password) && $password !== $confirm_password) {
        $errors[] = "The passwords you entered do not match.";
    }

    // If there are no errors, update the user's information in the database
    if (empty($errors)) {
        // Hash the new password, if one was provided
        $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

        // Update the user's information in the database
        $stmt = $db->prepare("UPDATE users SET username = :username, email = :email, password = :password, number = :number WHERE id = :user_id");
        $stmt->bindValue(":username", $username);
        $stmt->bindValue(":email", $email);
        $stmt->bindValue(":password", $hashed_password);
        $stmt->bindValue(":number", $number);
        $stmt->bindValue(":user_id", $_SESSION['user_id']);
        $stmt->execute();

        // Redirect the user to a confirmation page
        header("Location: profile_updated.php");
        exit();
    } else {
        // If there were errors, display them to the user
        $message = implode("<br>", $errors);
    }
}

// Retrieve the deposits from the database
$stmt = $db->prepare("SELECT amount, date FROM deposits WHERE user_id = :user_id");
$stmt->bindValue(":user_id", $_SESSION["user_id"]);
$stmt->execute();
$deposits = $stmt->fetchAll();

// Retrieve the withdrawals from the database
$stmt = $db->prepare("SELECT amount, date FROM withdraw WHERE user_id = :user_id");
$stmt->bindValue(":user_id", $_SESSION["user_id"]);
$stmt->execute();
$withdrawals = $stmt->fetchAll();

// Retrieve the bill payments from the database
$stmt = $db->prepare("SELECT amount, date, bill_type FROM bill_payments WHERE user_id = :user_id");
$stmt->bindValue(":user_id", $_SESSION["user_id"]);
$stmt->execute();
$bill_payments = $stmt->fetchAll();

// Process the withdrawal form submission
if (isset($_POST["withdraw_submit"])) {
    // Retrieve the form data
    $amount = $_POST["withdraw"];
    $date = $_POST["withdraw_date"];

    // Validate the form data
    $errors = [];
    if (!is_numeric($amount) || $amount <= 0) {
        $errors[] = "Please enter a valid amount.";
    }

    if (empty($date)) {
        $errors[] = "Please select a date.";
    }

    // If there are no errors, update the database with the deposit details and balance
    if (empty($errors)) {
        // Retrieve the user's current balance from the database
        $stmt = $db->prepare("SELECT balance FROM users WHERE id = :user_id");
        $stmt->bindValue(":user_id", $_SESSION["user_id"]);
        $stmt->execute();
        $balance = $stmt->fetchColumn();

        // Subtract the withdrawal amount to the user's balance
        $new_balance = $balance - $amount;

        // Update the user's balance in the database with the new balance
        $stmt = $db->prepare("UPDATE users SET balance = :new_balance WHERE id = :user_id");
        $stmt->bindValue(":new_balance", $new_balance);
        $stmt->bindValue(":user_id", $_SESSION["user_id"]);
        $stmt->execute();

        // Insert the deposit details into the database
        $stmt = $db->prepare("INSERT INTO withdraw (user_id, amount, date) VALUES (:user_id, :amount, :date)");
        $stmt->bindValue(":user_id", $_SESSION["user_id"]);
        $stmt->bindValue(":amount", $amount);
        $stmt->bindValue(":date", $date);
        $stmt->execute();

        header("Location: withdraw_confirmation.php?amount=$amount&username=$first_name");
        exit();
    } else {
        $message = implode("<br>", $errors);
    }
}


if (isset($_POST["add_family_member"])) {
    // Sanitize user input
	$username = htmlspecialchars($_POST["username"], ENT_QUOTES, 'UTF-8');
	$firstname = htmlspecialchars($_POST["firstname"], ENT_QUOTES, 'UTF-8');
	$lastname = htmlspecialchars($_POST["lastname"], ENT_QUOTES, 'UTF-8');
	$number = htmlspecialchars($_POST["number"], ENT_QUOTES, 'UTF-8');
	$dob = htmlspecialchars($_POST["dob"], ENT_QUOTES, 'UTF-8');
	$privilege = htmlspecialchars($_POST["privilege"], ENT_QUOTES, 'UTF-8');

	// Check if the username already exists
	$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
	$stmt->bindValue(":username", $username);
	$stmt->execute();
	$count = $stmt->fetchColumn();
    
    if ($count > 0) {
        $error = "Username already exists";
    } else {
        // Add the family member to the database
        $stmt = $db->prepare("INSERT INTO users (username, firstname, lastname, number, dob, is_sub_user, parent_id) VALUES (:username, :firstname, :lastname, :number, :dob, :is_sub_user, :parent_id)");
        $stmt->bindValue(":username", $username);
        $stmt->bindValue(":firstname", $firstname);
        $stmt->bindValue(":lastname", $lastname);
		$stmt->bindValue(":number", $number);
        $stmt->bindValue(":dob", $dob);
        $stmt->bindValue(":is_sub_user", $privilege == "sub_user" ? 1 : 0);
        $stmt->bindValue(":parent_id", $_SESSION["user_id"]);
        $stmt->execute();
        
        $success = "Family member added successfully";
    }
}
// Handle editing a family member
if (isset($_POST["edit_family_member_submit"])) {
    $id = $_POST["id"];
    $username = $_POST["username"];
    $password = $_POST["password"];
    $privilege = $_POST["privilege"];
    
    // Update the family member in the database
    $stmt = $db->prepare("UPDATE users SET username = :username, password = :password, is_sub_user = :is_sub_user WHERE id = :id");
    $stmt->bindValue(":id", $id);
    $stmt->bindValue(":username", $username);
    $stmt->bindValue(":password", password_hash($password, PASSWORD_DEFAULT));
    $stmt->bindValue(":is_sub_user", $privilege == "sub_user" ? 1 : 0);
    $stmt->execute();
    
    $success = "Family member edited successfully";
	
}
if (isset($_POST["delete_family_member"])) {
    $id = $_POST["id"];
    // Delete the family member from the database
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindValue(":id", $id);
    $stmt->execute();

    $success = "Family member deleted successfully";
}


// Get the upcoming family birthdays
$family_birthday_stmt = $db->prepare("SELECT firstname, lastname, dob FROM users WHERE DATE(dob) >= DATE('now') AND DATE(dob) <= DATE('now', '+2 day') AND parent_id = :id AND is_sub_user = 1 AND notification_sent = '0'");
$family_birthday_stmt->bindValue(":id", $_SESSION["user_id"]);
$family_birthday_stmt->execute();
$family_birthdays = $family_birthday_stmt->fetchAll();

foreach ($family_birthdays as $family_birthday) {
    $message = "Upcoming birthday: " . $family_birthday["firstname"] . " " . $family_birthday["lastname"] . " on " . $family_birthday["dob"];
    echo "<script>alert('$message');</script>";
    
    // Set notification_sent to 1 to avoid duplicate notifications
    $update_stmt = $db->prepare("UPDATE users SET notification_sent = '1' WHERE firstname = :firstname AND lastname = :lastname AND dob = :dob");
    $update_stmt->bindValue(":firstname", $family_birthday["firstname"]);
    $update_stmt->bindValue(":lastname", $family_birthday["lastname"]);
    $update_stmt->bindValue(":dob", $family_birthday["dob"]);
    $update_stmt->execute();
}


?>

<!DOCTYPE html>
<html>
<head>
	<title>Welcome</title>
	<style>
	.navbar {
		position: fixed;
		top: 0;
		left: 0;
		width: 100px;
		display: flex;
		flex-direction: column;
		align-items: center;
		background-color: #333;
		height: 100vh;
		color: white;
		padding-top: 20px;
		transition: width 0.5s ease;
		z-index: 1;
	}

	nav a {
		color: white;
		text-decoration: none;
		margin-top: 10px;
		padding: 5px;
		border-radius: 5px;
		transition: background-color 0.3s ease;
	}

	nav a:hover {
		background-color: #555;
		cursor: pointer;
	}

	.show-link{
		color: white;
		text-decoration: none;
		margin-top: 10px;
		padding: 5px;
		border-radius: 5px;
		transition: background-color 0.3s ease;
	}

	.show-link:hover{
		background-color: #555;
		cursor: pointer;
	}
	.statement-link{
		color: white;
		text-decoration: none;
		margin-top: 10px;
		padding: 5px;
		border-radius: 5px;
		transition: background-color 0.3s ease;
	}
	.statement-link:hover{
		background-color: #555;
		cursor: pointer;
	}
	.settings-link{
		color: white;
		text-decoration: none;
		margin-top: 10px;
		padding: 5px;
		border-radius: 5px;
		transition: background-color 0.3s ease;
	}
	.settings-link:hover{
		background-color: #555;
		cursor: pointer;
	}
	.withdraw-link{
		color: white;
		text-decoration: none;
		margin-top: 10px;
		padding: 5px;
		border-radius: 5px;
		transition: background-color 0.3s ease;
	}

	.withdraw-link:hover{
		background-color: #555;
		cursor: pointer;
	}

	.bills-link{
		color: white;
		text-decoration: none;
		margin-top: 10px;
		padding: 5px;
		border-radius: 5px;
		transition: background-color 0.3s ease;
	}

	.bills-link:hover{
		background-color: #555;
		cursor: pointer;
	}

	.add-link{
		color: white;
		text-decoration: none;
		margin-top: 10px;
		padding: 5px;
		border-radius: 5px;
		transition: background-color 0.3s ease;
	}

	.add-link:hover{
		background-color: #555;
		cursor: pointer;
	}
	.deposit-link{
		color: white;
		text-decoration: none;
		margin-top: 10px;
		padding: 5px;
		border-radius: 5px;
		transition: background-color 0.3s ease;
	}

	.deposit-link:hover{
		background-color: #555;
		cursor: pointer;
	}

	.container {
		margin-left: 120px;
		padding: 20px;
	}

	h1 {
		margin-bottom: 20px;
	}

	form {
		display: flex;
		flex-direction: column;
		max-width: 400px;
		margin-bottom: 20px;
	}

	label {
			display: block;
			
			font-weight: bold;
			margin-bottom: 10px;
		}
		input[type=text], 
		input[type=password], 
		input[type=number], 
		input[type=date] {
			width: 95%;
			padding: 10px;
			margin-bottom: 20px;
			border-radius: 5px;
			border: none;
			box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
		}

		select {
			width: 100%;
			padding: 10px;
			margin-bottom: 20px;
			border-radius: 5px;
			border: none;
			box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
		}

		input[type=submit] {
			background-color: #000080;
				color: white;
				padding: 12px 20px;
				border: none;
				border-radius: 5px;
				cursor: pointer;
				
				margin-top: 10px;
				width: 100%;
				transition: 0.3s ease;
 
		}
		
		input[type=submit]:hover {
			background-color: #4169E1;
				color: #000000; 
		}

	.error {
		color: red;
		margin-bottom: 10px;
	}

	.success {
		color: green;
		margin-bottom: 10px;
	}

	table {
		border-collapse: collapse;
		margin-bottom: 20px;
	}

	th, td {
		border: 1px solid black;
		padding: 10px;
		text-align: center;
	}

	#add-form {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-items: center; 
			background-color: #DCDCDC;
			padding: 20px;
			border-radius: 10px;
			box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
			width: 100%;
			margin-bottom: 30px;
			display: none;
		}

		#show-form {
			background-color: #DCDCDC;
			padding: 20px;
			border-radius: 10px;
			max-width: 900px;
			margin: 0 auto;
			display: block;
			align-items: center; 
			justify-content: center; 
			display: none;
			}

			table {
			border-collapse: collapse;
			width: 100%;
			max-width: 600px;
			margin: 0 auto;
			margin-bottom: 20px;
			font-size: 16px;
			}

			thead {background-color: #ddd;}

			th {
			font-weight: bold;
			padding: 10px;
			text-align: center;
			border: 1px solid black;
			background-color: #f2f2f2;
			color: #333;
			}

			td {
			border: 1px solid #ddd;
			padding: 10px;
			text-align: center;
			}

			td:first-child {font-weight: bold;}

			form button {
			background-color: #4CAF50;
			border: none;
			color: white;
			padding: 10px 20px;
			text-align: center;
			text-decoration: none;
			display: inline-block;
			margin: 4px 2px;
			cursor: pointer;
			}

			form button:hover {background-color: #3e8e41;}

	#bills-form{
		display: none;
	}
	#deposit-form{
		display:none;
	}
	#withdraw-form{
		display:none;
	}
	#statement-form{
		display:none;
	}
	#settings-form{
		display:none;
	}

	@media (max-width: 767px) {
		.navbar {
			width: 50px;
		}

		.navbar a {
			display: none;
		}

		.navbar:hover a {
			display: block;
		}

		#add-toggle, #show-toggle, #bills-toggle {
			display: block;
			width: 100%;
			text-align: center;
		}

		#add-toggle:hover, #show-toggle:hover, #bills-toggle:hover {
			background-color: #555;
			cursor: pointer;
		}
	}
	</style>
	</head>
	<body>
	<nav class="navbar">
		<h3>Welcome <?php echo $first_name; ?></h3>
		<p class="add-link" id="add-toggle">Add Family Members</p>
		<p class="show-link" id="show-toggle">Show Family Members</p>
		<p class="bills-link" id="bills-toggle">Pay Bills</p>
		<p class="deposit-link" id="deposit-toggle">Deposit</p>
		<p class="withdraw-link" id="withdraw-toggle">Withdraw</p>
		<p class="statement-link" id="statement-toggle">Statement</p>
		<p class="settings-link" id="settings-toggle">Account Settings</p>
		<a href="logout.php">Logout</a>
	</nav>
	<div class="container">
		
		<form method="post" id ="add-form">
			<h1>Add a family member</h1>
			<label for="username">Username:</label>
			<input type="text" id="username" name="username" required>
			<label for="firstname">First name:</label>
			<input type="text" id="firstname" name="firstname" required>
			<label for="lastname">Last name:</label>
			<input type="text" id="lastname" name="lastname" required>
			<label for="number">Phone Number:</label>
			<input type="number" id="number" name="number" required>
			<label for="dob">Date of birth:</label>
			<input type="date" id="dob" name="dob" required>
			<label for="privilege">Privilege:</label>
			<select id="privilege" name="privilege">
				<option value="sub_user">Sub-user</option>
				<option value="normal_user">Normal user</option>
			</select>
			<input type="submit" name="add_family_member" value="Add family member">
		</form>
		<?php if (isset($error)): ?>
			<div class="error"><?php echo $error; ?></div>
		<?php endif; ?>
		<?php if (isset($success)): ?>
			<div class="success"><?php echo $success; ?></div>
		<?php endif; ?>
		<form id="show-form">
    		<h1>Family members</h1>
			<?php
            	// Retrieve the user's family members from the database
            	$stmt = $db->prepare("SELECT id, username, firstname, lastname, number, dob, is_sub_user FROM users WHERE parent_id = :id");
            	$stmt->bindValue(":id", $_SESSION["user_id"]);
            	$stmt->execute();
            	$family_members = $stmt->fetchAll();
				if(empty($family_members)){
					echo "You currently do not have any family members added";
				} else{
            		foreach ($family_members as $family_member) {
                		$name = $family_member["firstname"] . " " . $family_member["lastname"];
                		$privilege = $family_member["is_sub_user"] ? "Sub user" : "Main user";
				}
            	?>
    		<table>
        		<thead>
            	<tr>
                	<th>Username</th>
                	<th>Name</th>
                	<th>Phone Number</th>
                	<th>Date of birth</th>
                	<th>Privilege</th>
                	<th>Edit</th>
                	<th>Delete</th>
            	</tr>
        		</thead>
        	<tbody>
            	
            	<tr>
                	<td><?php echo $family_member["username"]; ?></td>
                	<td><?php echo $name; ?></td>
                	<td><?php echo $family_member["number"]; ?></td>
                	<td><?php echo $family_member["dob"]; ?></td>
                	<td><?php echo $privilege; ?></td>
                	<td>
                    	<form method="post">
                        	<input type="hidden" name="id" value="<?php echo $family_member['id']; ?>">
                        	<button type="submit" name="edit_family_member_<?php echo $family_member['id']; ?>">Edit</button>
                    	</form>
                	</td>
                	<td>
                    	<form method="post">
                        	<input type="hidden" name="id" value="<?php echo $family_member['id']; ?>">
                        	<button type="submit" name="delete_family_member">Delete</button>
                    	</form>
                	</td>
            	</tr>
            	<?php } ?>
        	</tbody>
    		</table>
		</form>
		<form id="edit-form" method="post" style="display:none;">
    		<input type="hidden" name="id" id="edit-id">
    		<label for="edit-username">Username:</label>
    		<input type="text" name="username" id="edit-username">
    		<label for="edit-password">Password:</label>
    		<input type="password" name="password" id="edit-password">
    		<label for="edit-privilege">Privilege:</label>
    		<select name="privilege" id="edit-privilege">
        		<option value="main_user">Main user</option>
        		<option value="sub_user">Sub user</option>
    		</select>
    		<input type="submit" name="edit_family_member_submit" value="Edit">
		</form>
		<form method="post" id="bills-form">
			<h1>Pay utility bills</h1>
			<label for="bill_type">Select Bill Type:</label>
			<select id="bill_type" name="bill_type">
				<option value="electricity">Electricity</option>
				<option value="water">Water</option>
				<option value="internet">Internet</option>
			</select><br><br>
			<label for="amount">Amount:</label>
			<input type="number" id="amount" name="amount"><br><br>
			<label for="due_date">Due Date:</label>
			<input type="date" id="due_date" name="due_date"><br><br>
			<label for="autopay">Autopay:</label>
			<input type="checkbox" name="autopay" id="autopay"><br><br>
			<input type="submit" name="pay_bill" value="Pay Bill">
		</form>
		<form method="post" id="settings-form">
    		<label for="username">Username:</label>
    		<input type="text" name="username" id="username" value="<?php echo $user['username']; ?>">
    		<br><br>
    		<label for="email">Email:</label>
    		<input type="email" name="email" id="email" value="<?php echo $user['email']; ?>">
    		<br><br>
    		<label for="password">New Password:</label>
    		<input type="password" name="password" id="password">
    		<br><br>
    		<label for="confirm_password">Confirm Password:</label>
    		<input type="password" name="confirm_password" id="confirm_password">
    		<br><br>
    		<label for="number">Phone Number:</label>
			<input type="text" name="number" id="number" value="<?php echo isset($user['number']) ? $user['number'] : ''; ?>">

    		<br><br>
    		<input type="submit" name="submit" value="Update">
		</form>
		<form method="post" id="deposit-form">
			<h1>Deposit</h1>
        	<label for="deposit">Deposit Amount:</label>
        	<input type="number" id="deposit" name="deposit" required><br><br>
        	<label for="deposit_date">Date:</label>
        	<input type="date" id="deposit_date" name="deposit_date" required><br><br>
        	<input type="submit" name="deposit_submit" value="Deposit">
    	</form>
		<form method="post" id="withdraw-form">
			<h1>Withdraw</h1>
        	<label for="withdraw">Withdraw Amount:</label>
        	<input type="number" id="withdraw" name="withdraw" required><br><br>
        	<label for="withdraw_date">Date:</label>
        	<input type="date" id="withdraw_date" name="withdraw_date" required><br><br>
        	<input type="submit" name="withdraw_submit" value="Withdraw">
    	</form>
		<form id="statement-form">
  <h1>Bank Statement</h1>
  <?php
  $stmt = $db->prepare("SELECT date, 'Deposit' as type, amount, 'Deposit to Account' as description FROM deposits WHERE user_id = :user_id 
                        UNION ALL 
                        SELECT date, 'Withdrawal' as type, amount, 'Withdrawal from Account' as description FROM withdraw WHERE user_id = :user_id 
                        UNION ALL 
                        SELECT date, bill_type as type, amount, 'Bill Payment' as description FROM bill_payments WHERE user_id = :user_id");
  $stmt->bindValue(":user_id", $_SESSION["user_id"]);
  $stmt->execute();
  $transactions = $stmt->fetchAll();
  ?>
  <?php if (empty($transactions)): ?>
    <p>There are no transactions made yet</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Type</th>
          <th>Description</th>
          <th>Amount</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $transaction): ?>
          <tr>
            <td><?php echo $transaction["date"]; ?></td>
            <td><?php echo $transaction["type"]; ?></td>
            <td><?php echo $transaction["description"]; ?></td>
            <td><?php echo $transaction["amount"]; ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</form>
		<div id="message"></div>
	</div>
	<script>
	const editForm = document.getElementById('edit-form');
    const editIdInput = document.getElementById('edit-id');
	var addForm = document.getElementById("add-form");
	var addLink = document.getElementById("add-toggle");
	var billsForm = document.getElementById("bills-form");
	var billsLink = document.getElementById("bills-toggle");
	var showForm = document.getElementById("show-form");
	var showLink = document.getElementById("show-toggle");
	var depositForm = document.getElementById("deposit-form");
	var depositLink = document.getElementById("deposit-toggle");
	var withdrawForm = document.getElementById("withdraw-form");
	var withdrawLink = document.getElementById("withdraw-toggle");
	var statementForm = document.getElementById("statement-form");
	var statementLink = document.getElementById("statement-toggle");
	var settingsForm = document.getElementById("settings-form");
	var settingsLink = document.getElementById("settings-toggle");

	const messageDiv = document.getElementById("message");
  	<?php if (isset($message)): ?>
    	messageDiv.textContent = "<?php echo $message; ?>";
  	<?php endif; ?>

	const editButtons = document.querySelectorAll('button[name^="edit_family_member_"]');
    editButtons.forEach(button => {
        button.addEventListener('click', (event) => {
            // Get the ID from the button's name attribute
            const id = button.name.replace('edit_family_member_', '');

            // Set the ID in the edit form's ID input field
            editIdInput.value = id;

            // Show the edit form
            editForm.style.display = 'block';

            // Prevent the default form submission
            event.preventDefault();
        });
    });
	addLink.addEventListener("click", function() {
		toggleForm("add");
	});
	showLink.addEventListener("click", function() {
		toggleForm("show");
	});
	billsLink.addEventListener("click", function() {
		toggleForm("bills");
	});
	depositLink.addEventListener("click", function() {
		toggleForm("deposit");
	});
	withdrawLink.addEventListener("click", function() {
		toggleForm("withdraw");
	});
	statementLink.addEventListener("click", function() {
		toggleForm("statement");
	});
	settingsLink.addEventListener("click", function() {
		toggleForm("settings");
	});


	function toggleForm(form) {
		if (form == "add") {
			addForm.style.display = "block";
			showForm.style.display = "none";
			billsForm.style.display = "none";
			depositForm.style.display ="none";
			withdrawForm.style.display="none";
			statementForm.style.display="none";
			settingsForm.style.display="none";

		} else if (form == "show") {
			addForm.style.display = "none";
			showForm.style.display = "block";
			billsForm.style.display = "none";
			depositForm.style.display ="none";
			withdrawForm.style.display="none";
			withdrawForm.style.display="none";
			settingsForm.style.display="none";


		}else if (form == "bills") {
			addForm.style.display = "none";
			showForm.style.display = "none";
			billsForm.style.display = "block";
			depositForm.style.display ="none";
			withdrawForm.style.display="none";
			statementForm.style.display="none";
			settingsForm.style.display="none";

		}
		else if (form == "deposit") {
			addForm.style.display = "none";
			showForm.style.display = "none";
			billsForm.style.display = "none";
			depositForm.style.display ="block";
			withdrawForm.style.display="none";
			statementForm.style.display="none";
			settingsForm.style.display="none";


		} else if (form == "withdraw") {
			addForm.style.display = "none";
			showForm.style.display = "none";
			billsForm.style.display = "none";
			depositForm.style.display ="none";
			withdrawForm.style.display="block";
			statementForm.style.display="none";
			settingsForm.style.display="none";

		}
		else if (form == "statement") {
			addForm.style.display = "none";
			showForm.style.display = "none";
			billsForm.style.display = "none";
			depositForm.style.display ="none";
			withdrawForm.style.display="none";
			statementForm.style.display="block";
			settingsForm.style.display="none";

		}
		else if (form == "settings") {
			addForm.style.display = "none";
			showForm.style.display = "none";
			billsForm.style.display = "none";
			depositForm.style.display ="none";
			withdrawForm.style.display="none";
			statementForm.style.display="none";
			settingsForm.style.display="block";

		}
	}
</script>
</body>
</html>