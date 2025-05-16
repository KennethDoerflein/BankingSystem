<?php
//includes file with db connection
require_once '../../db_connect.php';

//gets session info
session_start();

//takes input passed from form and assigns to variables
$user = strtolower(trim($_POST['user']));
$pass = trim($_POST['pass']);

//checks if all inputs have been passed
if (!$user || !$pass) {
    $_SESSION['login_failed'] = 'invalid_input';
    header('Location: ../emp_login.php');
    $db->close();
    exit();
}

try {
  // Check employee credentials
  $stmt = $db->prepare("SELECT * FROM employee WHERE eUsername = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['login_failed'] = 'nouser';
        header('Location: ../emp_login.php');
        $stmt->close();
        $db->close();
        exit();
    }
    
    $row = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($pass, $row['ePassword'])) {
        $_SESSION['login_failed'] = 'wrongpass';
        header('Location: ../emp_login.php');
        $stmt->close();
        $db->close();
        exit();
    }
    
    // Set session variables
    $_SESSION['loggedin'] = true;
    $_SESSION['user'] = $user;
    $_SESSION['login_time_stamp'] = time();
    
    // Redirect to admin homepage
    header('Location: ../homepage_admin.php');

} catch (Exception $e) {
    error_log("Login failed: " . $e->getMessage());
    $_SESSION['login_failed'] = 'randerr';
    header('Location: ../emp_login.php');
} finally {
    if (isset($stmt)) $stmt->close();
    $db->close();
    exit();
}
