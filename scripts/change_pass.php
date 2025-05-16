<?php
//includes file with db connection
require_once '../db_connect.php';

//gets session info
session_start();

//takes input passed from form and assigns to variables
$oldPass = trim($_POST['oldpass']);
$newPass = trim($_POST['newpass']);
$conPass = trim($_POST['conpass']);

//checks if all inputs have been passed
if (!$oldPass || !$newPass || !$conPass) {
  $_SESSION['registration_failed'] = 'invalid_input';
  header('Location: ../account.php');
  $db->close();
  exit();
}

//checks if new password is at least 6 characters
if (strlen($newPass) < 6) {
  $_SESSION['registration_failed'] = 'invalid_password';
  header('Location: ../account.php');
  $db->close();
  exit();
}

//checks if password and confirm password inputs match
if ($newPass != $conPass) {
  $_SESSION['registration_failed'] = 'pwdnotmatch';
  header('Location: ../account.php');
  $db->close();
  exit();
}

// Begin transaction
$db->begin_transaction();

try {
  // Check if user is employee
  $stmt = $db->prepare("SELECT ePassword FROM employee WHERE eUsername = ?");
  $stmt->bind_param("s", $_SESSION['user']);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    // Employee password change
    $row = $result->fetch_assoc();
    if (password_verify($oldPass, $row['ePassword'])) {
      // Hash new password
      $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);

      // Update employee password
      $stmt = $db->prepare("UPDATE employee SET ePassword = ? WHERE eUsername = ?");
      $stmt->bind_param("ss", $hashedPassword, $_SESSION['user']);
      $stmt->execute();

      $db->commit();
      $_SESSION['passwordChanged'] = true;
      header('Location: ../employee/emp_settings.php');
      exit();
    } else {
      $db->rollback();
      $_SESSION['passwordInput_failed'] = 'pwdnotmatch';
      header('Location: ../employee/emp_settings.php');
      exit();
    }
  } else {
    // Check if user is customer
    $stmt = $db->prepare("SELECT cPassword FROM customer WHERE cUsername = ?");
    $stmt->bind_param("s", $_SESSION['user']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      // Customer password change
      $row = $result->fetch_assoc();
      if (password_verify($oldPass, $row['cPassword'])) {
        // Hash new password
        $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);

        // Update customer password
        $stmt = $db->prepare("UPDATE customer SET cPassword = ? WHERE cUsername = ?");
        $stmt->bind_param("ss", $hashedPassword, $_SESSION['user']);
        $stmt->execute();

        $db->commit();
        $_SESSION['passwordChanged'] = true;
        header('Location: ../account.php');
        exit();
      } else {
        $db->rollback();
        $_SESSION['passwordInput_failed'] = 'pwdnotmatch';
        header('Location: ../account.php');
        exit();
      }
    }
  }
} catch (Exception $e) {
  // If any error occurs, rollback the transaction
  $db->rollback();
  error_log("Password change failed: " . $e->getMessage());
  $_SESSION['registration_failed'] = 'randerr';
  header('Location: ../account.php');
} finally {
  if (isset($stmt)) $stmt->close();
  $db->close();
  exit();
}
