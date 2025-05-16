<?php
//includes file with db connection
require_once '../db_connect.php';

//gets session info
session_start();

//takes input passed from form and assigns to variables
$user = strtolower(trim($_POST['user']));
$pass = trim($_POST['pass']);

//checks if all inputs have been passed
if (!$user || !$pass) {
  $_SESSION['login_failed'] = 'invalid_input';
  header('Location: ../login.php');
  $db->close();
  exit();
}

// Check employee login first
$stmt = $db->prepare("SELECT eUsername, ePassword FROM employee WHERE eUsername = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  if (password_verify($pass, $row['ePassword'])) {
    $_SESSION['loggedin'] = true;
    $_SESSION['user'] = $user;
    $_SESSION['login_time_stamp'] = time();
    header('Location: ../employee/homepage_admin.php');
    $stmt->close();
    $db->close();
    exit();
  }
}
$stmt->close();

// If not an employee, check customer login
try {
  $stmt = $db->prepare("SELECT * FROM customer WHERE cUsername = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
    $_SESSION['login_failed'] = 'userdne';
        header('Location: ../login.php');
        $stmt->close();
        $db->close();
        exit();
    }
    
    $row = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($pass, $row['cPassword'])) {
    $_SESSION['login_failed'] = 'wrong_password';
        header('Location: ../login.php');
        $stmt->close();
        $db->close();
        exit();
    }
    
    // Set session variables
    $_SESSION['loggedin'] = true;
    $_SESSION['user'] = $user;
    $_SESSION['login_time_stamp'] = time();
    
    // Redirect to homepage
    header('Location: ../homepage.php');

} catch (Exception $e) {
    error_log("Login failed: " . $e->getMessage());
    $_SESSION['login_failed'] = 'randerr';
    header('Location: ../login.php');
} finally {
    if (isset($stmt)) $stmt->close();
    $db->close();
    exit();
}