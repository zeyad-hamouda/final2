<h2>Add Debit</h2>

<?php
session_start();
if (isset($_SESSION["debit-error"])) {
    echo "<div class='error'>" . $_SESSION["debit-error"] . "</div>";
    unset($_SESSION["debit-error"]);
}
?>

<form action="add_debit_handler.php" method="post">
    <div>
        <label for="title">Title:</label>
        <input type="text" name="title" required>
    </div>
    <div>
        <label for="amount">Amount:</label>
        <input type="number" name="amount" required>
    </div>
    <div>
        <label for="payment_installment">Payment Installment:</label>
        <input type="number" name="payment_installment" required>
    </div>
    <div>
        <label for="final_date">Final Date:</label>
        <input type="date" name="final_date" required>
    </div>
    <div>
        <label for="description">Description:</label>
        <textarea name="description" required></textarea>
    </div>
    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
    <input type="submit" value="Add Debit">
</form>