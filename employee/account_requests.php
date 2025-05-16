<?php
//gets db connection info
require_once '../db_connect.php';

//gets session info
session_start();

// Initialize variables
$notice = '';
$numPendingRequests = 0;

//checks if user has logged in
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
if (isset($_SESSION['account_approval'])) {
  if ($_SESSION['account_approval'] == 'approved') {
    $notice = 'Account has been approved.';
  } else if ($_SESSION['account_approval'] == 'denied') {
    $notice = 'Account has been denied.';
  }
  $_SESSION['account_approval'] = '';
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

// Get all pending accounts with customer information
$stmt = $db->prepare("SELECT a.*, c.cUsername, c.cFname, c.cLname 
                      FROM accounts a 
                      JOIN customer c ON a.ownerID = c.customerID 
                      WHERE a.status = 'pending approval' OR a.status = 'pending deletion'
                      ORDER BY a.status ASC");
$stmt->execute();
$results = $stmt->get_result();
$numPendingRequests = $results ? $results->num_rows : 0;

$db->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style.css">
  <title>Account Requests - Admin</title>
  <link rel="icon" type="image/x-icon" href="../assets/icon_draft1.png">
</head>

<body>
  <div class="alert alert-info text-center mb-0" role="alert" style="border-radius:0;">
    <strong>Educational Use Only:</strong> This site is for educational purposes. All content, data, and entities are entirely fictitious.
  </div>
  <ul>
    <li><a class="active admin_home" href="./homepage_admin.php">Home</a></li>
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
  <!--<div class="hero-wrapper">-->
  <!--    <div class="hero-wrapper-squared">-->
  <!--        <h1>MKJJ Online Banking System</h1>-->
  <!--    </div>-->
  <!--</div>-->
  <nav class="heading" style='text-align: center'>
    <h2>Account Requests</h2>
    <!--<button class="mkjj-button" onclick="window.print()">Print this page</button>-->
    <center>
      <div style='color: red;'><?php echo $notice ?? ''; ?></div>
    </center>
  </nav>
  <hr>
  <div>
    <?php
    if ($numPendingRequests == 0) {
      echo "<center>No pending accounts :)</center>";
    } else {
      while ($row = $results->fetch_assoc()) {
        if ($row['status'] == "pending approval") {
          echo '<div class="history-card">';
          echo '<div class="card-info"><b>Account Number</b></br>' . htmlspecialchars($row['bankAccountNumber']) . '</div>';
          echo '<div class="vl"></div>';
          echo '<div class="card-info"><b>Account Type</b></br>' . htmlspecialchars($row['accountType']) . '</div>';
          echo '<div class="vl"></div>';
          echo '<div class="card-info"><b>Initial Deposit</b></br> $' . number_format($row['balance'], 2) . '</div>';
          echo '<div class="vl"></div>';
          echo '<div class="card-info"><b>Status</b></br>' . htmlspecialchars($row['status']) . '</div>';
          echo '<form action="./scripts/approveAccount.php" method="post">
                                <button style="background-color: green" class = "mkjj-button" name="accept" value="' . htmlspecialchars($row['bankAccountNumber']) . '">Approve</button>';
          echo '</form>';
          echo '<br>';
          echo '<form action="./scripts/denyAccount.php" method="post">
                                <button style="background-color: red" class = "mkjj-button" name="decline" value="' . htmlspecialchars($row['bankAccountNumber']) . '">Deny</button>';
          echo '</form>';
          echo '<br>';
          echo "</div>";
        } else if ($row['status'] == "pending deletion") {
          echo '<div class="history-card">';
          echo '<div class="card-info"><b>Account Number</b></br>' . htmlspecialchars($row['bankAccountNumber']) . '</div>';
          echo '<div class="vl"></div>';
          echo '<div class="card-info"><b>Account Type</b></br>' . htmlspecialchars($row['accountType']) . '</div>';
          echo '<div class="vl"></div>';
          echo '<div class="card-info"><b>Initial Deposit</b></br> $' . number_format($row['balance'], 2) . '</div>';
          echo '<div class="vl"></div>';
          echo '<div class="card-info"><b>Status</b></br>' . htmlspecialchars($row['status']) . '</div>';
          echo '<form action="./scripts/approveDeletion.php" method="post">
                                <button style="background-color: green" class = "mkjj-button" name="accept" value="' . htmlspecialchars($row['bankAccountNumber']) . '">Approve</button>';
          echo '</form>';
          echo '<br>';
          echo '<form action="./scripts/denyDeletion.php" method="post">
                                <button style="background-color: red" class = "mkjj-button" name="decline" value="' . htmlspecialchars($row['bankAccountNumber']) . '">Deny</button>';
          echo '</form>';
          echo '<br>';
          echo "</div>";
        }
      }
    }
    ?>
  </div>
</body>

</html>