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
$bankAccountNum = trim($_POST['account_num']);
$status = "pending deletion";

if (!$bankAccountNum) {
  $_SESSION['registration_failed'] = 'invalid_input';
  header('Location: ../homepage.php');
  $db->close();
  exit();
}

// Get customer info
$stmt = $db->prepare("SELECT customerID, numOfAccounts FROM customer WHERE cUsername = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$numOfAccts = $row['numOfAccounts'] - 1;
$cID = $row['customerID'];
$stmt->close();

// Begin transaction
$db->begin_transaction();

try {
  // Update account status to pending deletion
  $stmt1 = $db->prepare("UPDATE accounts SET status = ? WHERE ownerID = ? AND bankAccountNumber = ?");
  $stmt1->bind_param("sss", $status, $cID, $bankAccountNum);
  $result = $stmt1->execute();

  if ($stmt1->affected_rows > 0) {
    // If account was found and updated
    $db->commit();
    $_SESSION['acctDeletionDone'] = 'done';
    header('Location: ../homepage.php');
  } else {
    // If account wasn't found or not owned by user
    $db->rollback();
    $_SESSION['registration_failed'] = 'randerr';
    header('Location: ../homepage.php');
  }
} catch (Exception $e) {
  // If any query fails, roll back the transaction
  $db->rollback();
  $_SESSION['registration_failed'] = 'randerr';
  header('Location: ../homepage.php');
} finally {
  // Close all prepared statements
  if (isset($stmt1)) $stmt1->close();
  $db->close();
  exit();
}
