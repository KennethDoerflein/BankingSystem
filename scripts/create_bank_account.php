<?php
//includes file with db connection
require_once '../db_connect.php';

//gets session info
session_start();

if (isset($_SESSION["loggedin"])) {
  if (time() - $_SESSION["login_time_stamp"] > 600) {
    session_unset();
    session_destroy();
    header("Location: login.php");
  }
}

//takes input passed from form and assigns to variables
$acctType = strtolower(trim($_POST['acct']));
$deposit = trim($_POST['initDeposit']);
date_default_timezone_set("America/New_York");
$date = date("Y/m/d");
$transactionDate = date("Y/m/d H:i:s");
$transactionType = "initial deposit";
$status = "pending approval";
$numTransactions = 0;

// Get customer info
$stmt = $db->prepare("SELECT customerID, numOfAccounts FROM customer WHERE cUsername = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$numAccounts = $row['numOfAccounts'] + 1;
$cID = $row['customerID'];
$stmt->close();

// Generate unique bank account number
$bankAcctNum = mt_rand(400000000000, 499999999999);
$stmt = $db->prepare("SELECT bankAccountNumber FROM accounts WHERE bankAccountNumber = ?");
do {
  $stmt->bind_param("s", $bankAcctNum);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $bankAcctNum = mt_rand(400000000000, 499999999999);
  }
} while ($result->num_rows > 0);
$stmt->close();

// Generate unique transaction ID
$transactionid = mt_rand(10000000000, 20000000000);
$stmt = $db->prepare("SELECT transactionID FROM transactions WHERE transactionID = ?");
do {
  $stmt->bind_param("s", $transactionid);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $transactionid = mt_rand(10000000000, 20000000000);
  }
} while ($result->num_rows > 0);
$stmt->close();

if (!$deposit) {
  $deposit = 0.0;
}

if (!$acctType) {
  $_SESSION['registration_failed'] = 'invalid_input';
  header('Location: ../homepage.php');
  $db->close();
  exit();
}

// Begin transaction
$db->begin_transaction();

try {
  // Create account
  $stmt1 = $db->prepare("INSERT INTO accounts VALUES (?, ?, ?, ?, ?, ?, ?)");
  $stmt1->bind_param("ssdssss", $bankAcctNum, $acctType, $deposit, $cID, $date, $numTransactions, $status);
  $stmt1->execute();

  // Update customer's account count
  $stmt2 = $db->prepare("UPDATE customer SET numOfAccounts = ? WHERE customerID = ?");
  $stmt2->bind_param("is", $numAccounts, $cID);
  $stmt2->execute();

  // Record initial deposit transaction if amount is greater than 0
  if ($deposit > 0) {
    $stmt3 = $db->prepare("INSERT INTO transactions VALUES (?, ?, ?, ?, ?)");
    $stmt3->bind_param("ssdss", $transactionDate, $transactionType, $deposit, $bankAcctNum, $transactionid);
    $stmt3->execute();
  }

  // If all queries succeed, commit the transaction
  $db->commit();

  $_SESSION['acctRegDone'] = 'done';
  header('Location: ../homepage.php');
} catch (Exception $e) {
  // If any query fails, roll back the transaction
  $db->rollback();
  $_SESSION['registration_failed'] = 'randerr';
  header('Location: ../homepage.php');
} finally {
  // Close all prepared statements
  if (isset($stmt1)) $stmt1->close();
  if (isset($stmt2)) $stmt2->close();
  if (isset($stmt3)) $stmt3->close();
  $db->close();
  exit();
}
