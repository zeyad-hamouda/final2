<!DOCTYPE html>
<html>
<head>
    <title>Payment Confirmation</title>
    <meta http-equiv="refresh" content="5;url=profile.php">
</head>
<body>
    <h1>Payment Confirmation</h1>

    <?php
    session_start();
    if (!isset($_SESSION["user_id"])) {
        header("Location: login.php");
        exit();
    }

    require_once "db_init.php";
    // Retrieve the user's current balance from the database
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = :user_id");
    $stmt->bindValue(":user_id", $_SESSION["user_id"]);
    $stmt->execute();
    $balance = $stmt->fetchColumn();
    ?>

    <p>Your current balance is: $<?= number_format($balance, 2) ?></p>

</body>
</html>