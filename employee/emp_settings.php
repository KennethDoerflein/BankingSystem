<?php
//gets db connection info
require_once '../db_connect.php';

//gets session info
session_start();

// Initialize notice variables
$notice = '';
$notice2 = '';

//checks if user is logged in. if not, redirects to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  $_SESSION['needlog'] = true;
  header('Location: ./emp_login.php');
  $db->close();
  exit();
}

if (isset($_SESSION["loggedin"])) {
  if (time() - $_SESSION["login_time_stamp"] > 600) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    $db->close();
    exit();
  }
}

// Process session messages
if (isset($_SESSION['registration_failed'])) {
  if ($_SESSION['registration_failed'] == 'invalid_input') {
    $notice = 'Missing input. Please try again.';
  } else if ($_SESSION['registration_failed'] == 'invalid_password') {
    $notice = 'Password must be at least 6 characters.';
  } else if ($_SESSION['registration_failed'] == 'pwdnotmatch') {
    $notice = 'Passwords do not match.';
  } else if ($_SESSION['registration_failed'] == 'usertaken') {
    $notice = 'Username is already taken.';
  } else if ($_SESSION['registration_failed'] == 'randerr') {
    $notice = 'An error has occurred. Please try again.';
  }
  $_SESSION['registration_failed'] = '';
}

if (isset($_SESSION['passwordInput_failed']) && $_SESSION['passwordInput_failed'] == 'pwdnotmatch') {
  $notice = 'Current password is incorrect.';
  $_SESSION['passwordInput_failed'] = '';
}

if (isset($_SESSION['passwordChanged']) && $_SESSION['passwordChanged'] === true) {
  $notice = 'Password successfully changed.';
  $_SESSION['passwordChanged'] = false;
}

if (isset($_SESSION['usernameChanged']) && $_SESSION['usernameChanged'] === true) {
  $notice2 = 'Username successfully changed.';
  $_SESSION['usernameChanged'] = false;
}

// Check if user is customer
$stmt = $db->prepare("SELECT cUsername FROM customer WHERE cUsername = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$isUSR = $stmt->get_result();

if ($isUSR->num_rows > 0) {
  $_SESSION['hasAccess'] = false;
  header('Location: ../login.php');
  $stmt->close();
  $db->close();
  exit();
}
$stmt->close();

// Get employee information
$stmt = $db->prepare("SELECT * FROM employee WHERE eUsername = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $username = htmlspecialchars($row['eUsername']);
  $fname = htmlspecialchars($row['eFname']);
  $lname = htmlspecialchars($row['eLname']);
  $email = htmlspecialchars($row['eEmail']);
  $address = htmlspecialchars($row['eAddress']); // FIX: use eAddress
}
$stmt->close();
$db->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style.css">
  <title>Employee Settings - Admin</title>
  <link rel="icon" type="image/x-icon" href="../assets/icon_draft1.png">
</head>

<body>
  <div class="alert alert-info text-center mb-0" role="alert" style="border-radius:0;">
    <strong>Educational Use Only:</strong> This site is for educational purposes. All content, data, and entities are entirely fictitious.
  </div>
  <ul>
    <li><a class="active admin_home" href="./homepage_admin.php">Admin Home</a></li>
    <!--<li><a class="active" href="../homepage.php">Home</a></li>-->
    <!--<form action="search_apparel.php" method="post">-->
    <!--<li style="float:right; display: relative; padding-top: 12px; padding-right: 10px;">-->
    <!--    <input name="searchterm" type="text" size="20">-->
    <!--    <input type="submit" name="submit" value="Search">-->
    <!--</li>-->
    <!--</form>-->
    <li style="float:right"><a class="active" href="../scripts/logout.php">Log Out</a></li>
    <li style="float:right"><a class="active" href="./emp_settings.php">Settings</a></li>
  </ul>
  <style>
    body {
      font-size: 24px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .editusername {
      color: dodgerblue;
    }

    .editpass {
      color: dodgerblue;
    }

    .form-popup {
      display: none;
      position: fixed;
      bottom: 10px;
      right: 10px;
      border: 5px solid #000000;
      z-index: 9;
    }

    .form-container {
      max-width: 800px;
      padding: 20px;
      background-color: white;
    }

    .form-container .btn {
      background-color: #04AA6D;
      color: white;
      padding: 16px 20px;
      border: none;
      cursor: pointer;
      width: 100%;
      margin-bottom: 10px;
      opacity: 0.8;
    }

    .form-container .cancel {
      background-color: red;
    }

    .hero-wrapper, .hero-wrapper-squared {
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      text-align: center;
    }

    .heading h1, .heading h2 {
      text-align: center;
      margin-top: 20px;
      margin-bottom: 10px;
    }

    .section-wrapper, .info {
      max-width: 800px;
      margin: 20px auto;
      padding: 15px;
      text-align: center;
    }

    @media (max-width: 900px) {
      .section-wrapper, .info {
        padding: 10px 5vw;
      }
    }

    @media (max-width: 600px) {
      .section-wrapper, .info {
        padding: 8px 2vw;
        font-size: 1em;
      }
      .heading h1, .heading h2 {
        font-size: 1.3em;
      }
    }
  </style>
  <div class="hero-wrapper">
    <div class="hero-wrapper-squared">
      <h1>MKJJ Online Banking System</h1>
    </div>
  </div>
  <nav class="heading">
    <h1>
      <center>Account Details</center>
    </h1>
  </nav>

  <hr>
  <div>
    <center>
      <p>Welcome back, <?php echo $fname; ?>!</p>
      <!-- Display general notice if any -->
      <?php if (!empty($notice)): ?>
        <div style='color: red;'><?php echo $notice; ?></div>
      <?php endif; ?>
      <?php if (!empty($notice2)): ?>
        <div style='color: red;'><?php echo $notice2; ?></div>
      <?php endif; ?>
    </center>
  </div>

  <!--prompts user's information-->
  <div class="info">

    <b><u>User Information:</u></b>
    <div></div>
    <center>
      <div>
        First Name: <?php echo $fname; ?>
      </div>
      <div>
        Last Name: <?php echo $lname; ?>
      </div>
      <div>
        Email Address: <?php echo $email; ?>
      </div>
      <div>
        Primary Address: <?php echo $address; ?>
      </div>
  </div> <!-- End info div -->

</body>

</html>