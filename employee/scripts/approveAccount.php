<?php
//includes file with db connection
require_once '../../db_connect.php';

//gets session info
session_start();

$acctNum = trim($_POST['accept']);
$status = "approved";

// Begin transaction
$db->begin_transaction();

try {
  // Update account status to approved
  $stmt = $db->prepare("UPDATE accounts SET status = ? WHERE bankAccountNumber = ? AND status = 'pending approval'");
  $stmt->bind_param("ss", $status, $acctNum);
  $result = $stmt->execute();

  if ($stmt->affected_rows > 0) {
    // If account was found and updated
    $db->commit();
    $_SESSION['account_approval'] = 'approved';
  } else {
    // If account wasn't found or wasn't pending approval
    $db->rollback();
    $_SESSION['registration_failed'] = 'randerr';
  }
} catch (Exception $e) {
  // If any error occurs, rollback the transaction
  $db->rollback();
  error_log("Account approval failed: " . $e->getMessage());
  $_SESSION['registration_failed'] = 'randerr';
} finally {
  // Close prepared statement
  if (isset($stmt)) $stmt->close();
  $db->close();
  header('Location: ../account_requests.php');
  exit();
}
