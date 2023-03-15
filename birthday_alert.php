<?php

require_once "db_init.php";
// Get the upcoming family birthdays
$family_birthday_stmt = $db->prepare("SELECT firstname, lastname, dob FROM users WHERE DATE(dob) >= DATE('now') AND DATE(dob) <= DATE('now', '+2 day') AND parent_id = :id AND is_sub_user = 1 AND notification_sent = '0'");
$family_birthday_stmt->bindValue(":id", $_SESSION["user_id"]);

if (!$family_birthday_stmt->execute()) {
    $error_info = $family_birthday_stmt->errorInfo();
    echo "Error executing query: " . $error_info[2];
}

$family_birthdays = $family_birthday_stmt->fetchAll();

if (!empty($family_birthdays)) {
    foreach ($family_birthdays as $family_birthday) {
        $birthday_message = "Upcoming birthday: " . $family_birthday["firstname"] . " " . $family_birthday["lastname"] . " on " . $family_birthday["dob"];
        echo "<script>alert('$birthday_message');</script>";

        // Set notification_sent to 1 to avoid duplicate notifications
        $update_stmt = $db->prepare("UPDATE users SET notification_sent = '1' WHERE TRIM(firstname) = :firstname AND TRIM(lastname) = :lastname AND DATE(dob) = DATE(:dob)");
		$update_stmt->bindValue(":firstname", trim($family_birthday["firstname"]));
		$update_stmt->bindValue(":lastname", trim($family_birthday["lastname"]));
		$update_stmt->bindValue(":dob", $family_birthday["dob"]);
		$update_stmt->execute();

        if (!$update_stmt->execute()) {
            $error_info = $update_stmt->errorInfo();
            echo "Error updating notification_sent column: " . $error_info[2];
        }
    }
} else {
    echo "No upcoming birthdays";
}


?>