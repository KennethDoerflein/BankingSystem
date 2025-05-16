<?php
//includes file with db connection
require_once '../db_connect.php';

//gets session info
session_start();

//takes input passed from form and assigns to variables
$newUser = strtolower(trim($_POST['newuser']));
$pass = trim($_POST['pass']);

//checks if all inputs have been passed
if (!$newUser || !$pass) {
  $_SESSION['registration_failed'] = 'invalid_input';
  header('Location: ../account.php');
  $db->close();
  exit();
}

// Begin transaction
$db->begin_transaction();

try {
  // First check if new username already exists in customer table
  $stmt = $db->prepare("SELECT cUsername FROM customer WHERE cUsername = ?");
  $stmt->bind_param("s", $newUser);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $_SESSION['registration_failed'] = 'usertaken';
    header('Location: ../account.php');
    $stmt->close();
    $db->close();
    exit();
  }
  $stmt->close();

  // Then check if new username exists in employee table
  $stmt = $db->prepare("SELECT eUsername FROM employee WHERE eUsername = ?");
  $stmt->bind_param("s", $newUser);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $_SESSION['registration_failed'] = 'usertaken';
    header('Location: ../account.php');
    $stmt->close();
    $db->close();
    exit();
  }
  $stmt->close();

  // Check if user is employee
  $stmt = $db->prepare("SELECT ePassword FROM employee WHERE eUsername = ?");
  $stmt->bind_param("s", $_SESSION['user']);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    // Employee username change
    $row = $result->fetch_assoc();
    if (password_verify($pass, $row['ePassword'])) {
      // Update employee username
      $stmt = $db->prepare("UPDATE employee SET eUsername = ? WHERE eUsername = ?");
      $stmt->bind_param("ss", $newUser, $_SESSION['user']);
      $stmt->execute();

      $db->commit();
      $_SESSION['user'] = $newUser;
      $_SESSION['usernameChanged'] = true;
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
      // Customer username change
      $row = $result->fetch_assoc();
      if (password_verify($pass, $row['cPassword'])) {
        // Update customer username
        $stmt = $db->prepare("UPDATE customer SET cUsername = ? WHERE cUsername = ?");
        $stmt->bind_param("ss", $newUser, $_SESSION['user']);
        $stmt->execute();

        $db->commit();
        $_SESSION['user'] = $newUser;
        $_SESSION['usernameChanged'] = true;
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
  error_log("Username change failed: " . $e->getMessage());
  $_SESSION['registration_failed'] = 'randerr';
  header('Location: ../account.php');
} finally {
  if (isset($stmt)) $stmt->close();
  $db->close();
  exit();
}
