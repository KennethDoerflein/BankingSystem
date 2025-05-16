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

$deposit = -1 * trim($_POST['withdrawal']);
$acctNum = intval(trim($_POST['account_num']));
date_default_timezone_set("America/New_York");
$date = date("Y/m/d H:i:s");
$transactionType = "withdrawal";

// Generate unique transaction ID
$transactionid = mt_rand(10000000000, 20000000000);
$stmt = $db->prepare("SELECT transactionID FROM transactions WHERE transactionID = ?");
$stmt->bind_param("s", $transactionid);
do {
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $transactionid = mt_rand(10000000000, 20000000000);
  }
} while ($result->num_rows > 0);
$stmt->close();

// Get account details
$stmt = $db->prepare("SELECT balance, ownerID, numOfTransactions FROM accounts WHERE bankAccountNumber = ?");
$stmt->bind_param("s", $acctNum);
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
$currentBalance = $row['balance'];
$acctOwner = $row['ownerID'];
$numTransactions = $row['numOfTransactions'] + 1;
$stmt->close();

// Calculate new balance
$newBalance = round(doubleval($currentBalance) + doubleval($deposit), 2);

// Begin transaction
$db->begin_transaction();

try {
  // Update account balance
  $stmt1 = $db->prepare("UPDATE accounts SET balance = ? WHERE bankAccountNumber = ?");
  $stmt1->bind_param("ds", $newBalance, $acctNum);
  $stmt1->execute();

  // Record transaction
  $stmt2 = $db->prepare("INSERT INTO transactions VALUES (?, ?, ?, ?, ?)");
  $stmt2->bind_param("ssdss", $date, $transactionType, $deposit, $acctNum, $transactionid);
  $stmt2->execute();

  // Update number of transactions
  $stmt3 = $db->prepare("UPDATE accounts SET numOfTransactions = ? WHERE bankAccountNumber = ?");
  $stmt3->bind_param("is", $numTransactions, $acctNum);
  $stmt3->execute();

  // If all queries succeed, commit the transaction
  $db->commit();

  $_SESSION['withdrawalSuccess'] = 'successful';
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
  $db->close();
  exit();
}
