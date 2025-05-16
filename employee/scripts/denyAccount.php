<?php
//includes file with db connection
require_once '../../db_connect.php';

//gets session info
session_start();

$acctNum = trim($_POST['decline']);

// Begin transaction
$db->begin_transaction();

try {
  // Delete account that was denied
  $stmt = $db->prepare("DELETE FROM accounts WHERE bankAccountNumber = ? AND status = 'pending approval'");
  $stmt->bind_param("s", $acctNum);
  $result = $stmt->execute();

  if ($stmt->affected_rows > 0) {
    // If account was found and deleted
    $db->commit();
    $_SESSION['account_approval'] = 'denied';
  } else {
    // If account wasn't found or wasn't pending approval
    $db->rollback();
    $_SESSION['registration_failed'] = 'randerr';
  }
} catch (Exception $e) {
  // If any error occurs, rollback the transaction
  $db->rollback();
  error_log("Account denial failed: " . $e->getMessage());
  $_SESSION['registration_failed'] = 'randerr';
} finally {
  // Close prepared statement
  if (isset($stmt)) $stmt->close();
  $db->close();
  header('Location: ../account_requests.php');
  exit();
}
