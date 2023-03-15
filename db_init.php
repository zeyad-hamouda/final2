<?php
$db = new PDO('sqlite:users.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    firstname TEXT,
    lastname TEXT,
    number INTEGER,
    email TEXT UNIQUE,
    password TEXT,
    dob TEXT,
    token TEXT,
    notification_sent INTEGER DEFAULT 0,
    is_sub_user INTEGER DEFAULT 0,
    parent_id INTEGER DEFAULT 0,
    balance	DECIMAL
)");

$db->exec("CREATE TABLE IF NOT EXISTS debits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    amount FLOAT,
    title TEXT,
    description TEXT,
    payment_installment FLOAT,
    final_date TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");
$db->exec("CREATE TABLE IF NOT EXISTS bill_payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    amount FLOAT,
    bill_type TEXT,
    date TEXT,
    is_autopay BOOLEAN,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");
$db->exec("CREATE TABLE IF NOT EXISTS autopay(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    bill_type TEXT,
    amount FLOAT,
    next_payment_date TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (bill_type) REFERENCES bill_payments(bill_type),
    FOREIGN KEY (amount) REFERENCES bill_payments(amount)
)");
$db->exec("CREATE TABLE IF NOT EXISTS deposits (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    amount FLOAT,
    date TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");
$db->exec("CREATE TABLE IF NOT EXISTS withdraw (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    amount FLOAT,
    date TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

?>

