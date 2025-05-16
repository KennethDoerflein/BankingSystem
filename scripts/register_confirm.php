<?php
//includes file with db connection
require_once '../db_connect.php';

//gets session info
session_start();

//takes input passed from form and assigns to variables
$user = strtolower(trim($_POST['user']));
$pass = trim($_POST['pass']);
$conpass = trim($_POST['conpass']);
$email = trim($_POST['email']);
$phone_num = trim($_POST['phone_num']);
$fname = trim($_POST['fname']);
$lname = trim($_POST['lname']);
$stadd = trim($_POST['stadd']);
$city = trim($_POST['city']);
$state = trim($_POST['state']);
$zip = trim($_POST['zip']);
$numAccts = 0;

//checks if all inputs have been passed
if (!$user || !$pass || !$conpass || !$fname || !$lname || !$email || !$stadd || !$city || !$state || !$zip || !$phone_num) {
  $_SESSION['registration_failed'] = 'invalid_input';
  header('Location: ../register.php');
  $db->close();
  exit();
}

//checks if password is at least 6 characters
if (strlen($pass) < 6) {
  $_SESSION['registration_failed'] = 'invalid_password';
  header('Location: ../register.php');
  $db->close();
  exit();
}

//checks if password and confirm password inputs match
if ($pass != $conpass) {
  $_SESSION['registration_failed'] = 'pwdnotmatch';
  header('Location: ../register.php');
  $db->close();
  exit();
}

// Begin transaction
$db->begin_transaction();

try {
  // Check if username exists in customer table
  $stmt = $db->prepare("SELECT cUsername FROM customer WHERE cUsername = ?");
  $stmt->bind_param("s", $user);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $_SESSION['registration_failed'] = 'usertaken';
    header('Location: ../register.php');
    $stmt->close();
    $db->close();
    exit();
  }
  $stmt->close();

  // Check if username exists in employee table
  $stmt = $db->prepare("SELECT eUsername FROM employee WHERE eUsername = ?");
  $stmt->bind_param("s", $user);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $_SESSION['registration_failed'] = 'usertaken';
    header('Location: ../register.php');
    $stmt->close();
    $db->close();
    exit();
  }
  $stmt->close();

  // Check if email exists
  $stmt = $db->prepare("SELECT cEmail FROM customer WHERE cEmail = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $_SESSION['registration_failed'] = 'emailtaken';
    header('Location: ../register.php');
    $stmt->close();
    $db->close();
    exit();
  }
  $stmt->close();

  // Check if phone number exists
  $stmt = $db->prepare("SELECT phoneNumber FROM customer WHERE phoneNumber = ?");
  $stmt->bind_param("s", $phone_num);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $_SESSION['registration_failed'] = 'phonenumbertaken';
    header('Location: ../register.php');
    $stmt->close();
    $db->close();
    exit();
  }
  $stmt->close();

  // Generate unique customer ID
  $custid = mt_rand(100000, 999999);
  $stmt = $db->prepare("SELECT customerID FROM customer WHERE customerID = ?");
  do {
    $stmt->bind_param("s", $custid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
      $custid = mt_rand(100000, 999999);
    }
  } while ($result->num_rows > 0);
  $stmt->close();

  // Concatenate address
  $address = $stadd . ' ' . $city . ', ' . $state . ' ' . $zip;

  // Hash password
  $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);

  // Insert new customer
  $stmt = $db->prepare("INSERT INTO customer VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param("ssssssssi", $custid, $user, $hashedPassword, $email, $fname, $lname, $address, $phone_num, $numAccts);
  $stmt->execute();

  // If successful, commit and redirect
  $db->commit();
  $_SESSION['regdone'] = true;
  header('Location: ../login.php');
} catch (Exception $e) {
  // If any error occurs, rollback the transaction
  $db->rollback();
  error_log("Customer registration failed: " . $e->getMessage());
  $_SESSION['registration_failed'] = 'randerr';
  header('Location: ../register.php');
} finally {
  if (isset($stmt)) $stmt->close();
  $db->close();
  exit();
}
