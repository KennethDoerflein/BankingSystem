<?php
//includes file with db connection
require_once '../../db_connect.php';

//gets session info
session_start();

$acctNum = trim($_POST['decline']);
$approvedStatus = "approved";

// Begin transaction
$db->begin_transaction();

try {
  // Update account status back to approved
  $stmt = $db->prepare("UPDATE accounts SET status = ? WHERE bankAccountNumber = ? AND status = 'pending deletion'");
  $stmt->bind_param("ss", $approvedStatus, $acctNum);
  $result = $stmt->execute();

  if ($stmt->affected_rows > 0) {
    // If account was found and updated
    $db->commit();
    $_SESSION['acctDeletionDone'] = 'denied';
  } else {
    // If account wasn't found or wasn't pending deletion
    $db->rollback();
    $_SESSION['registration_failed'] = 'randerr';
  }
} catch (Exception $e) {
  // If any query fails, roll back the transaction
  $db->rollback();
  $_SESSION['registration_failed'] = 'randerr';
} finally {
  // Close prepared statement
  if (isset($stmt)) $stmt->close();
  $db->close();
  header('Location: ../account_requests.php');
  exit();
}
