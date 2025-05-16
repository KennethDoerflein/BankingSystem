<?php
//includes file with db connection
require_once '../db_connect.php';

//gets session info
session_start();

// Begin transaction
$db->begin_transaction();

try {
  // Get posted data
  $deposit = floatval(trim($_POST['deposit']));
  $acctNum = trim($_POST['account_num']);

  // Verify account ownership and get current balance
  $stmt = $db->prepare("SELECT a.balance, c.customerID 
                         FROM accounts a 
                         JOIN customer c ON a.ownerID = c.customerID 
                         WHERE a.bankAccountNumber = ? AND c.cUsername = ? AND a.status = 'approved'
                         FOR UPDATE");
  $stmt->bind_param("ss", $acctNum, $_SESSION['user']);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
    $_SESSION['transaction_failed'] = 'doesntOwnAcct';
    throw new Exception('Account not found or not owned by user');
  }

  $row = $result->fetch_assoc();
  $currentBalance = $row['balance'];
  $customerID = $row['customerID'];
  $stmt->close();

  // Update account balance
  $newBalance = $currentBalance + $deposit;
  $stmt = $db->prepare("UPDATE accounts SET balance = ? WHERE bankAccountNumber = ?");
  $stmt->bind_param("ds", $newBalance, $acctNum);
  $stmt->execute();
  $stmt->close();

  // Generate unique 11-digit transaction ID
  do {
    $transactionID = strval(mt_rand(10000000000, 99999999999));
    $stmt = $db->prepare("SELECT transactionID FROM transactions WHERE transactionID = ?");
    $stmt->bind_param("s", $transactionID);
    $stmt->execute();
    $result = $stmt->get_result();
  } while ($result->num_rows > 0);
  $stmt->close();

  // Record transaction (correct column order)
  $stmt = $db->prepare("INSERT INTO transactions (transactionID, dateOfTransaction, transactionType, changeInBalance, bankAccountNumber) VALUES (?, CURRENT_TIMESTAMP, ?, ?, ?)");
  $type = "deposit";
  $stmt->bind_param("ssds", $transactionID, $type, $deposit, $acctNum);
  $stmt->execute();

  // If successful, commit and redirect
  $db->commit();
  $_SESSION['depositSuccess'] = 'successful';
  header('Location: ../homepage.php');
} catch (Exception $e) {
  // If any error occurs, rollback the transaction
  $db->rollback();
  error_log("Deposit failed: " . $e->getMessage());
  $_SESSION['transaction_failed'] = 'randerr';
  header('Location: ../homepage.php');
} finally {
  if (isset($stmt)) $stmt->close();
  $db->close();
  exit();
}
