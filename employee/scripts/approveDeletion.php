<?php
//includes file with db connection
require_once '../../db_connect.php';

//gets session info
session_start();

$bankAccountNum = trim($_POST['accept']);

// Begin transaction
$db->begin_transaction();

try {
  // Delete account
  $stmt = $db->prepare("DELETE FROM accounts WHERE bankAccountNumber = ?");
  $stmt->bind_param("s", $bankAccountNum);
  $result = $stmt->execute();

  if ($stmt->affected_rows > 0) {
    // If account was found and deleted
    $db->commit();
    $_SESSION['acctDeletionDone'] = 'done';
  } else {
    // If account wasn't found
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
