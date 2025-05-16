<?php
    //gets db connection info
    require_once './db_connect.php';

//gets session info
session_start();

    // Initialize variables
    $notice = '';
    $results = null;
    $num_results = 0;
    $acctNum = '';
    $acctType = '';

    // Check if user is employee
    $stmt = $db->prepare("SELECT eUsername FROM employee WHERE eUsername = ?");
    $stmt->bind_param("s", $_SESSION['user']);
    $stmt->execute();
    $isEMP = $stmt->get_result();

if ($isEMP->num_rows > 0) {
  $_SESSION['hasAccess'] = false;
  header('Location: ./employee/emp_login.php');
      $stmt->close();
      $db->close();
      exit();
    }
    $stmt->close();

    // Get the requested account number
    if (isset($_POST['account_num'])) {
      $acctNum = trim($_POST['account_num']);

      // Verify account ownership and get account type
      $stmt = $db->prepare("SELECT a.accountType 
                         FROM accounts a 
                         JOIN customer c ON a.ownerID = c.customerID 
                         WHERE a.bankAccountNumber = ? AND c.cUsername = ?");
      $stmt->bind_param("ss", $acctNum, $_SESSION['user']);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $acctType = $row['accountType'];

    // Get transaction history
    $stmt = $db->prepare("SELECT t.* 
                            FROM transactions t 
                            JOIN accounts a ON t.bankAccountNumber = a.bankAccountNumber 
                            JOIN customer c ON a.ownerID = c.customerID 
                            WHERE t.bankAccountNumber = ? AND c.cUsername = ? 
                            ORDER BY t.dateOfTransaction DESC");
    $stmt->bind_param("ss", $acctNum, $_SESSION['user']);
    $stmt->execute();
    $results = $stmt->get_result();
    $num_results = $results->num_rows;
  } else {
    $_SESSION['transaction_failed'] = 'doesntOwnAcct';
    header('Location: ./homepage.php');
    $stmt->close();
    $db->close();
    exit();
  }
  $stmt->close();
}

    $db->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./style.css">
  <title>MKJJ</title>
  <link rel="icon" type="image/x-icon" href="assets/icon_draft1.png">

</head>

<body>
  <?php
  $acctsList = array();
  ?>
  <ul>
    <li><a class="active" href="./homepage.php">Home</a></li>
    <!--<form action="search_apparel.php" method="post">-->
    <!--<li style="float:right; display: relative; padding-top: 12px; padding-right: 10px;">-->
    <!--    <input name="searchterm" type="text" size="20">-->
    <!--    <input type="submit" name="submit" value="Search">-->
    <!--</li>-->
    <!--</form>-->
    <li style="float:right"><a class="active" href="./scripts/logout.php">Log Out</a></li>
    <li style="float:right"><a class="active" href="./account.php">Settings</a></li>
    <li style="float:right"><a class="active" href="./about.php">About</a></li>
    <li style="float:right"><a class="active" href="./services.php">Services</a></li>
  </ul>
  <style>
    body {
      font-size: 24px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
  </style>
  <!--<div class="hero-wrapper">-->
  <!--    <div class="hero-wrapper-squared">-->
  <!--        <h1>MKJJ Online Banking System</h1>-->
  <!--    </div>-->
  <!--</div>-->
  <nav class="heading" style='text-align: center'>
    <h2>History</h2>
    <h3>Account Number: <?php echo $acctNum ?></h3>
    <h3>Account Type: <?php echo $acctType ?></h3>
    <button class="mkjj-button" onclick="window.print()">Print this page</button>

    <!-- <center>
      <div style='color: red;'><?php echo $notice; ?></div>
    </center> -->
  </nav>
  <hr>
  <div>
    <?php
    if ($num_results == 0) {
      echo "No transaction history";
    } else {
      $currentDate = strtotime(date("Y/m/d H:i:s"));
      for ($i = 0; $i < $num_results; $i++) {
        $row = $results->fetch_assoc();
        $secs = $currentDate - strtotime($row['dateOfTransaction']);
        $days = $secs / 86400;
        if ($days <= 365) {
          if ($i % 2 == 0) {
            echo '<div style="background-color: LightGray" class="history-card">';
          } else {
            echo '<div class="history-card">';
          }

          echo '<div class="card-info"><b>Date of Transaction</b></br>' . $row['dateOfTransaction'] . '</div>';
          echo '<div class="vl"></div>';
          echo '<div class="card-info"><b>Transaction ID</b></br>' . $row['transactionID'] . '</div>';
          echo '<div class="vl"></div>';
          echo '<div class="card-info"><b>Transaction Type</b></br>' . $row['transactionType'] . '</div>';
          echo '<div class="vl"></div>';
          echo '<div class="card-info"><b>Amount</b></br> $' . number_format($row['changeInBalance'], 2) . '</div>'; // replaced money_format
          // echo '<div class="vl"></div>';
          // echo '<div class="card-info"><b>Days Since Transaction</b></br>'.$days.'</div>';
          echo '<br>';
          echo "</div>";
        }
      }
    }
    ?>
  </div>
</body>

</html>