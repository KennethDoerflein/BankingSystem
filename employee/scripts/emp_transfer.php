<?php
//includes file with db connection
require_once '../../db_connect.php';

//gets session info
session_start();

if (isset($_SESSION["loggedin"])) {
  if (time() - $_SESSION["login_time_stamp"] > 600) {
    session_unset();
    session_destroy();
    header("Location: login.php");
  }
}

$transfer = trim($_POST['transfer']);
$senderAcctNum = intval(trim($_POST['sender_account_num']));
$receiverAcctNum = trim($_POST['receiver_account_num']);
$transferType = trim($_POST['transferType']);
date_default_timezone_set("America/New_York");
$sDate = date("Y/m/d H:i:s");
$rDate = date("Y/m/d H:i:s");
$rTransactionType = "transfer received";
$sTransactionType = "transfer sent";

// Generate unique transaction IDs
$senderTransactionid = mt_rand(10000000000, 20000000000);
$receiverTransactionid = mt_rand(10000000000, 20000000000);

// Verify transaction IDs are unique
$stmt = $db->prepare("SELECT transactionID FROM transactions WHERE transactionID = ? OR transactionID = ?");
do {
  $stmt->bind_param("ss", $senderTransactionid, $receiverTransactionid);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $senderTransactionid = mt_rand(10000000000, 20000000000);
    $receiverTransactionid = mt_rand(10000000000, 20000000000);
  }
} while ($result->num_rows > 0);
$stmt->close();

// Get sender account details
$stmt = $db->prepare("SELECT balance, ownerID, numOfTransactions FROM accounts WHERE bankAccountNumber = ?");
$stmt->bind_param("s", $senderAcctNum);
$stmt->execute();
$result = $stmt->get_result();

if (!$result->num_rows) {
  $_SESSION['transaction_failed'] = 'doesntOwnAcct';
  header('Location: ../addTransaction.php');
  $stmt->close();
  $db->close();
  exit();
}

$row = $result->fetch_assoc();
$senderCurrentBalance = $row['balance'];
$senderOwnerID = $row['ownerID'];
$numTransactions = $row['numOfTransactions'] + 1;
$stmt->close();

// For internal transfers, get receiver account details
if ($transferType == "internal") {
  $stmt = $db->prepare("SELECT balance, ownerID, numOfTransactions FROM accounts WHERE bankAccountNumber = ?");
  $stmt->bind_param("s", $receiverAcctNum);
  $stmt->execute();
  $result = $stmt->get_result();

  if (!$result->num_rows) {
    $_SESSION['transaction_failed'] = 'doesntOwnAcct';
    header('Location: ../addTransaction.php');
    $stmt->close();
    $db->close();
    exit();
  }

  $row = $result->fetch_assoc();
  $receiverCurrentBalance = $row['balance'];
  $receiverOwnerID = $row['ownerID'];
  $recNumTransactions = $row['numOfTransactions'] + 1;
  $stmt->close();
}

// Check sufficient balance
if ($senderCurrentBalance < $transfer) {
  $_SESSION['transaction_failed'] = 'insufficentBalance';
  header('Location: ../addTransaction.php');
  $db->close();
  exit();
}

// Calculate balances
$senderNewBalance = round((doubleval($senderCurrentBalance) - (doubleval($transfer)) * 1.03), 2);
$rtransfer = $transfer;
$stransfer = round((-$transfer * 1.03), 2);

if ($transferType == "internal") {
  $receiverNewBalance = round(doubleval($receiverCurrentBalance) + doubleval($transfer), 2);
}

// Begin transaction
$db->begin_transaction();

try {
  // Update sender balance
  $stmt1 = $db->prepare("UPDATE accounts SET balance = ? WHERE bankAccountNumber = ?");
  $stmt1->bind_param("ds", $senderNewBalance, $senderAcctNum);
  $stmt1->execute();

  // For internal transfers, update receiver balance
  if ($transferType == "internal") {
    $stmt2 = $db->prepare("UPDATE accounts SET balance = ? WHERE bankAccountNumber = ?");
    $stmt2->bind_param("ds", $receiverNewBalance, $receiverAcctNum);
    $stmt2->execute();
  }

  // Record sender's transaction
  $stmt3 = $db->prepare("INSERT INTO transactions VALUES (?, ?, ?, ?, ?)");
  $stmt3->bind_param("ssdss", $sDate, $sTransactionType, $stransfer, $senderAcctNum, $senderTransactionid);
  $stmt3->execute();

  // For internal transfers, record receiver's transaction
  if ($transferType == "internal") {
    $stmt4 = $db->prepare("INSERT INTO transactions VALUES (?, ?, ?, ?, ?)");
    $stmt4->bind_param("ssdss", $rDate, $rTransactionType, $rtransfer, $receiverAcctNum, $receiverTransactionid);
    $stmt4->execute();
  }

  // Update sender's transaction count
  $stmt5 = $db->prepare("UPDATE accounts SET numOfTransactions = ? WHERE bankAccountNumber = ?");
  $stmt5->bind_param("is", $numTransactions, $senderAcctNum);
  $stmt5->execute();

  // For internal transfers, update receiver's transaction count
  if ($transferType == "internal") {
    $stmt6 = $db->prepare("UPDATE accounts SET numOfTransactions = ? WHERE bankAccountNumber = ?");
    $stmt6->bind_param("is", $recNumTransactions, $receiverAcctNum);
    $stmt6->execute();
  }

  // If all queries succeed, commit the transaction
  $db->commit();

  $_SESSION['transferSuccess'] = 'successful';
  header('Location: ../addTransaction.php');
} catch (Exception $e) {
  // If any query fails, roll back the transaction
  $db->rollback();
  $_SESSION['registration_failed'] = 'randerr';
  header('Location: ../addTransaction.php');
} finally {
  // Close all prepared statements
  if (isset($stmt1)) $stmt1->close();
  if (isset($stmt2)) $stmt2->close();
  if (isset($stmt3)) $stmt3->close();
  if (isset($stmt4)) $stmt4->close();
  if (isset($stmt5)) $stmt5->close();
  if (isset($stmt6)) $stmt6->close();
  $db->close();
  exit();
}
