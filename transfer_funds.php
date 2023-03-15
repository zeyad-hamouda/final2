<?php
session_start();
if (!isset($_SESSION["user_id"])) {
	header("Location: login.php");
	exit();
}

require_once "db_init.php";
function transfer_funds($sender_id, $recipient_id, $amount, $db) {
    $db->beginTransaction();
  
    try {
      // Get sender's balance
      $stmt = $db->prepare("SELECT balance FROM users WHERE id = ?");
      $stmt->execute([$sender_id]);
      $sender_balance = $stmt->fetchColumn();
      $stmt->closeCursor();
  
      // Check if sender has enough balance
      if ($sender_balance < $amount) {
        throw new Exception("Insufficient balance");
      }
  
      // Deduct the amount from sender's balance
      $stmt = $db->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
      $stmt->execute([$amount, $sender_id]);
      $stmt->closeCursor();
  
      // Add the amount to recipient's balance
      $stmt = $db->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
      $stmt->execute([$amount, $recipient_id]);
      $stmt->closeCursor();
  
      // Commit the transaction
      $db->commit();
      return true;
    } catch (Exception $e) {
      // Roll back the transaction if there was an error
      $db->rollBack();
      return false;
    }
  }
  ?>