<?php
//includes file with db connection
require_once '../../db_connect.php'; // Ensure path is correct

//gets session info
session_start();

// Initialize variables to avoid undefined variable errors
$notice = '';
$acctNum = null;
$acctType = null;
$balance = null;
$numOfTransactions = 0; // Default to 0

## Start - stop user from viewing page
// Ensure $_SESSION['user'] is set before using it
if (!isset($_SESSION['user'])) {
  $_SESSION['hasAccess'] = false; // Or another appropriate flag/redirect
  header('Location: ../../login.php');
  if (isset($db)) $db->close(); // Close db if connection exists
  exit();
}
$userTest = "SELECT cUsername FROM customer WHERE cUsername = '" . $db->real_escape_string($_SESSION['user']) . "'";
//gets info from db
$isUSR = $db->query($userTest);
if ($isUSR) { // Check if query succeeded
  $num_USR = $isUSR->num_rows;
  if ($num_USR > 0) {
    $_SESSION['hasAccess'] = false;
    header('Location: ../../login.php');
    $isUSR->free();
    $db->close();
    exit();
  }
  $isUSR->free(); // Free result set
} else {
  // Handle query error
  error_log("User access check query failed: " . $db->error);
  // Optionally redirect or show generic error
  echo "Error verifying access.";
  $db->close();
  exit();
}
## End - stop uder from viewing page

//takes input passed from form and assigns to variables
if (isset($_POST['account_num'])) {
  $acctNum_input = trim($_POST['account_num']);
  // Basic validation: check if it's numeric and potentially length/format
  if (is_numeric($acctNum_input)) {
    $acctNum = intval($acctNum_input); // Convert to integer if needed, or keep as string if it's very large
  } else {
    $_SESSION['invalid_acctNum'] = 'invalid account number format';
    header('Location: ../homepage_admin.php');
    $db->close();
    exit();
  }
} else {
  // Handle case where account_num is not provided
  $_SESSION['invalid_acctNum'] = 'account number missing';
  header('Location: ../homepage_admin.php');
  $db->close();
  exit();
}


//checks if user has logged in. if not, redirects to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  $_SESSION['needlog'] = true;
  header('Location: ../emp_login.php'); // Redirect admin to admin login
  $db->close();
  exit();
}

// --- Query 2: Get Account Details First ---
// Use prepared statement for security
$query2 = "SELECT accountType, balance, numOfTransactions FROM accounts WHERE bankAccountNumber = ?";
$stmt2 = $db->prepare($query2);

if ($stmt2) {
  $stmt2->bind_param("s", $acctNum); // Use "s" if account number is treated as string (e.g., BIGINT)
  $stmt2->execute();
  $result2 = $stmt2->get_result();

  if ($result2->num_rows > 0) {
    $row2 = $result2->fetch_assoc();
    $acctType = $row2['accountType'];
    $balance = $row2['balance'];
    // $numOfTransactions = $row2['numOfTransactions']; // This might be from accounts table, but the history loop should use the count from transactions query
    $result2->free();
  } else {
    // Account not found
    $_SESSION['invalid_acctNum'] = 'invalid account number';
    header('Location: ../homepage_admin.php');
    $stmt2->close();
    $db->close();
    exit();
  }
  $stmt2->close();
} else {
  // Handle prepare error for query 2
  error_log("Account details query prepare failed: " . $db->error);
  echo "Error retrieving account details.";
  $db->close();
  exit();
}


// --- Query 1: Get Transaction History ---
// Use prepared statement for security
$query = "SELECT t.dateOfTransaction, t.transactionType, t.changeInBalance, t.transactionID
          FROM transactions as t
          WHERE t.bankAccountNumber = ?
          ORDER BY t.dateOfTransaction DESC";
$stmt = $db->prepare($query);
$transactions = []; // Array to hold transaction results

if ($stmt) {
  $stmt->bind_param("s", $acctNum); // Use "s" if account number is treated as string
  $stmt->execute();
  $results = $stmt->get_result();

  if ($results) {
    $numOfTransactions = $results->num_rows; // Get actual number of transactions
    while ($row = $results->fetch_assoc()) {
      $transactions[] = $row; // Store results in an array
    }
    $results->free();
  } else {
    // Handle get_result error
    error_log("Transaction history query get_result failed: " . $stmt->error);
    $notice = "Error retrieving transaction history.";
  }
  $stmt->close();
} else {
  // Handle prepare error for query 1
  error_log("Transaction history query prepare failed: " . $db->error);
  $notice = "Error preparing transaction history query.";
}


//closes db connection
$db->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../style.css">
  <title>MKJJ</title>
  <link rel="icon" type="image/x-icon" href="assets/icon_draft1.png">

</head>

<body>
  <ul>
    <li><a class="active admin_home" href="../homepage_admin.php">Admin Home</a></li>
    <!--<li><a class="active" href="../homepage.php">Home</a></li>-->
    <!--<form action="search_apparel.php" method="post">-->
    <!--<li style="float:right; display: relative; padding-top: 12px; padding-right: 10px;">-->
    <!--    <input name="searchterm" type="text" size="20">-->
    <!--    <input type="submit" name="submit" value="Search">-->
    <!--</li>-->
    <!--</form>-->
    <li style="float:right"><a class="active" href="../../scripts/logout.php">Log Out</a></li>
    <li style="float:right"><a class="active" href="../emp_settings.php">Settings</a></li> <!-- Changed link to emp_settings.php -->
    <!--<li style="float:right"><a class="active" href="../about.html">About</a></li>-->
    <!--<li style="float:right"><a class="active" href="../services.php">Services</a></li>-->
  </ul>
  <?php
  // This array seems unused
  // $acctsList = array();
  ?>
  <style>
    body {
      font-size: 24px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
  </style>
  <nav class="heading" style='text-align: center'>
    <h2>History</h2>
    <h3>Account Number: <?php echo htmlspecialchars($acctNum); ?></h3>
    <h3>Account Type: <?php echo htmlspecialchars($acctType); ?></h3>
    <h3>Balance: $<?php echo number_format($balance, 2); // Use number_format for balance 
                  ?></h3>
    <button class="mkjj-button" onclick="window.print()">Print this page</button>
    <center>
      <div style='color: red;'><?php echo htmlspecialchars($notice); ?></div>
    </center>
  </nav>
  <hr>
  <div>
    <?php
    if ($numOfTransactions == 0) {
      echo "<center>";
      echo "No transaction history";
      echo "</center>";
    } else {
      $i = 0; // Counter for alternating row colors
      foreach ($transactions as $row) { // Loop through the fetched transactions
        $bgColorStyle = ($i % 2 == 0) ? 'style="background-color: LightGray"' : '';
        echo "<div {$bgColorStyle} class=\"history-card\">";
        echo '<div class="card-info"><b>Date of Transaction</b></br>' . htmlspecialchars($row['dateOfTransaction']) . '</div>';
        echo '<div class="vl"></div>';
        echo '<div class="card-info"><b>Transaction ID</b></br>' . htmlspecialchars($row['transactionID']) . '</div>';
        echo '<div class="vl"></div>';
        echo '<div class="card-info"><b>Transaction Type</b></br>' . htmlspecialchars($row['transactionType']) . '</div>';
        echo '<div class="vl"></div>';
        // Replace money_format with number_format
        echo '<div class="card-info"><b>Amount</b></br>$' . number_format($row['changeInBalance'], 2) . '</div>';
        echo '<br>';
        echo "</div>";
        $i++;
      }
    }
    ?>
  </div>
</body>

</html>