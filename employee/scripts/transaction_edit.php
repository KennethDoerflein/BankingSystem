<?php
//includes file with db connection
require_once '../../db_connect.php'; // Ensure the path is correct

//gets session info
session_start();

// Function to safely close DB and exit
function cleanup_and_exit($db, $stmt = null, $location = '../emp_search.php')
{
  if ($stmt instanceof mysqli_stmt) {
    $stmt->close();
  }
  if ($db instanceof mysqli) {
    $db->close();
  }
  header("Location: " . $location);
  exit();
}

// Validate input
$transactionID_input = $_POST['transactionID'] ?? null;
$amount_input = $_POST['amount'] ?? null;

if ($transactionID_input === null || $amount_input === null) {
  $_SESSION['transaction_edit_failed'] = 'Missing input.';
  cleanup_and_exit($db);
}

$transactionID = trim($transactionID_input);
$amount = trim($amount_input);

// Basic validation: Ensure transaction ID and amount are numeric
if (!is_numeric($transactionID) || !is_numeric($amount)) {
  $_SESSION['transaction_edit_failed'] = 'Invalid input format. Transaction ID and Amount must be numeric.';
  cleanup_and_exit($db);
}

// Convert amount to float/decimal type for calculations
$newTransactionAmount = floatval($amount);

// Fetch original transaction details using prepared statements
$originalAmount = null;
$accountNum = null;
$transactionType = null;

$query1 = "SELECT changeInBalance, bankAccountNumber, transactionType FROM transactions WHERE transactionID = ?";
$stmt1 = $db->prepare($query1);

if ($stmt1) {
  $stmt1->bind_param("s", $transactionID);
  $stmt1->execute();
  $result1 = $stmt1->get_result();

  if ($result1->num_rows === 1) {
    $row1 = $result1->fetch_assoc();
    $originalAmount = floatval($row1['changeInBalance']);
    $accountNum = $row1['bankAccountNumber'];
    $transactionType = $row1['transactionType'];
  } else {
    $_SESSION['transaction_edit_failed'] = 'Transaction not found.';
    $stmt1->close();
    cleanup_and_exit($db);
  }
  $result1->free();
  $stmt1->close();
} else {
  error_log("Prepare failed (query1): (" . $db->errno . ") " . $db->error);
  $_SESSION['transaction_edit_failed'] = 'Database error fetching transaction.';
  cleanup_and_exit($db);
}

// --- Prevent modification of transfers (as per original logic) ---
// This should have ideally been checked in emp_search.php before showing the edit form
if ($transactionType == "transfer sent" || $transactionType == "transfer received") {
  $_SESSION['transaction_edit_failed'] = 'Cannot modify transfer transactions.';
  cleanup_and_exit($db);
}

// Fetch current account balance using prepared statements
$currentBalance = null;
$query2 = "SELECT balance FROM accounts WHERE bankAccountNumber = ?";
$stmt2 = $db->prepare($query2);

if ($stmt2) {
  $stmt2->bind_param("s", $accountNum);
  $stmt2->execute();
  $result2 = $stmt2->get_result();

  if ($result2->num_rows === 1) {
    $row2 = $result2->fetch_assoc();
    $currentBalance = floatval($row2['balance']);
  } else {
    // This case should ideally not happen if transaction was found, but good to check
    $_SESSION['transaction_edit_failed'] = 'Associated account not found.';
    $stmt2->close();
    cleanup_and_exit($db);
  }
  $result2->free();
  $stmt2->close();
} else {
  error_log("Prepare failed (query2): (" . $db->errno . ") " . $db->error);
  $_SESSION['transaction_edit_failed'] = 'Database error fetching account balance.';
  cleanup_and_exit($db);
}

// Calculate the adjustment needed for the account balance
// Adjustment = (New Transaction Amount) - (Original Transaction Amount)
$balanceAdjustment = $newTransactionAmount - $originalAmount;

// Calculate the new account balance
$newBalance = $currentBalance + $balanceAdjustment;

// --- Start Database Transaction ---
$db->begin_transaction();

try {
  // 1. Update transactions table
  $sql_update_trans = "UPDATE transactions SET changeInBalance = ? WHERE transactionID = ?";
  $stmt_update_trans = $db->prepare($sql_update_trans);
  if (!$stmt_update_trans) throw new Exception("Prepare failed (update trans): " . $db->error);

  // Bind parameters: new amount (double/decimal), transaction ID (string/int)
  if (!$stmt_update_trans->bind_param("ds", $newTransactionAmount, $transactionID)) {
    throw new Exception("Binding failed (update trans): " . $stmt_update_trans->error);
  }
  if (!$stmt_update_trans->execute()) {
    throw new Exception("Execute failed (update trans): " . $stmt_update_trans->error);
  }
  $stmt_update_trans->close();

  // 2. Update accounts table
  $sql_update_acct = "UPDATE accounts SET balance = ? WHERE bankAccountNumber = ?";
  $stmt_update_acct = $db->prepare($sql_update_acct);
  if (!$stmt_update_acct) throw new Exception("Prepare failed (update acct): " . $db->error);

  // Bind parameters: new balance (double/decimal), account number (string/int)
  if (!$stmt_update_acct->bind_param("ds", $newBalance, $accountNum)) {
    throw new Exception("Binding failed (update acct): " . $stmt_update_acct->error);
  }
  if (!$stmt_update_acct->execute()) {
    throw new Exception("Execute failed (update acct): " . $stmt_update_acct->error);
  }
  $stmt_update_acct->close();

  // If both updates succeeded, commit the transaction
  $db->commit();
  $_SESSION['transaction_edit_success'] = 'Transaction successfully modified.'; // Use a success message

} catch (Exception $e) {
  // An error occurred, rollback the transaction
  $db->rollback();
  error_log("Transaction failed: " . $e->getMessage());
  $_SESSION['transaction_edit_failed'] = 'Failed to modify transaction due to a database error.';
}

// Redirect back to the search page
cleanup_and_exit($db, null, '../emp_search.php');
