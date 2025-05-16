<?php
//gets db connection info
require_once '../db_connect.php';

//gets session info
session_start();

// Initialize notice variable
$notice = '';

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
if (isset($_SESSION['withdrawalSuccess']) && $_SESSION['withdrawalSuccess'] == 'successful') {
  $notice = 'Transaction was successful.';
  $_SESSION['withdrawalSuccess'] = '';
}

if (isset($_SESSION['transaction_failed'])) {
  if ($_SESSION['transaction_failed'] == 'insufficentBalance') {
    $notice = 'Insufficient balance.';
  } else if ($_SESSION['transaction_failed'] == 'doesntOwnAcct') {
    $notice = 'Account does not exist.';
  }
  $_SESSION['transaction_failed'] = '';
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

// Get all accounts for dropdown
$stmt = $db->prepare("SELECT a.*, c.cUsername, c.cFname, c.cLname 
                      FROM accounts a 
                      JOIN customer c ON a.ownerID = c.customerID 
                      WHERE a.status = 'approved'
                      ORDER BY a.bankAccountNumber ASC");
$stmt->execute();
$results = $stmt->get_result();

$accountsList = array();
if ($results) {
  while ($row = $results->fetch_assoc()) {
    $accountsList[] = array(
      'accountNumber' => $row['bankAccountNumber'],
      'accountType' => $row['accountType'],
      'balance' => $row['balance'],
      'ownerName' => $row['cFname'] . ' ' . $row['cLname'],
      'username' => $row['cUsername']
    );
  }
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
  <title>Add Transaction - Admin</title>
  <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
</head>

<body>
  <div class="alert alert-info text-center mb-0" role="alert" style="border-radius:0;">
    <strong>Educational Use Only:</strong> This site is for educational purposes. All content, data, and entities are entirely fictitious.
  </div>

  <style>
    .form-popup {
      display: none;
      position: fixed;
      bottom: 25px;
      right: 25px;
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
  </style>

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

  <div class="hero-wrapper">
    <div class="hero-wrapper-squared">
      <h1>Add a Transaction</h1>
    </div>
  </div>
  <br>
  <center>
    <center>
      <!-- Use null coalescing operator or check if $notice is empty -->
      <div style='color: red;'><?php echo htmlspecialchars($notice); ?></div>
    </center>
    <button class="mkjj-button" onclick="openDepositForm()">Deposit</button>
    <button class="mkjj-button" onclick="openWithdrawalForm()">Withdrawal</button>
    <button class="mkjj-button" onclick="openTransferForm()">Transfer</button>
  </center>

  <div class="form-popup" id="depositForm">
    <form action='./scripts/emp_deposit.php' method='post' class="form-container">
      <h1>Deposit</h1>

      <p><label for="deposit"><b>Deposit: $</b></label>
        <input type="number" min="0.01" step="0.01" placeholder="Enter deposit amount" name="deposit" required>
      </p>
      <p><label for="account_num"><b>Account Number: </b></label>
        <input type="text" pattern="4[0-9]{11}" title="A valid account number starts with a 4 that is followed by 11 more digits. Ex: 412345678901" placeholder="Ex: 444444444444" name="account_num" required>
        <!-- Changed type to text as "acctNumber" is not a valid type. Kept pattern. -->
      </p>
      <button type="submit" class="btn">Confirm Deposit</button>
      <button type="button" class="btn cancel" onclick="closeDepositForm()">Cancel</button>

    </form>
  </div>

  <div class="form-popup" id="withdrawalForm">
    <form action='./scripts/emp_withdrawal.php' method='post' class="form-container">
      <h1>Withdrawal</h1>

      <p><label for="withdrawal"><b>Withdrawal: $</b></label>
        <input type="number" min="0.01" step="0.01" placeholder="Enter withdrawal amount" name="withdrawal" required>
      </p>
      <p><label for="account_num"><b>Account Number: </b></label>
        <input type="text" pattern="4[0-9]{11}" title="A valid account number starts with a 4 that is followed by 11 more digits. Ex: 412345678901" placeholder="Ex: 444444444444" name="account_num" required>
        <!-- Changed type to text -->
      </p>
      <button type="submit" class="btn">Confirm Withdrawal</button>
      <button type="button" class="btn cancel" onclick="closeWithdrawalForm()">Cancel</button>

    </form>
  </div>

  <div class="form-popup" id="transferForm">
    <form action='./scripts/emp_transfer.php' method='post' class="form-container">
      <h1>Transfer</h1>

      <p><label for="transfer"><b>Transfer: $</b></label>
        <input type="number" min="0.01" step="0.01" placeholder="Enter transfer amount" name="transfer" required>
      </p>
      <p><label for="sender_account_num"><b>Sender Account Number: </b></label>
        <input type="text" pattern="4[0-9]{11}" title="A valid account number starts with a 4 that is followed by 11 more digits. Ex: 412345678901" placeholder="Ex: 444444444444" name="sender_account_num" required>
        <!-- Changed type to text -->
      </p>
      <label for="transferType"><b>Transaction Type: </b></label>
      <select name="transferType" id="typeOfTransfer">
        <option value="internal">Internal</option>
        <option value="external">External</option>
      </select>
      <p><label for="receiver_account_num"><b>Receiver Account Number: </b></label>
        <input type="text" pattern="4[0-9]{11}" title="A valid account number starts with a 4 that is followed by 11 more digits. Ex: 412345678901" placeholder="Ex: 444444444444" name="receiver_account_num" required>
        <!-- Changed type to text -->
      </p>
      <button type="submit" class="btn">Confirm Transfer</button>
      <button type="button" class="btn cancel" onclick="closeTransferForm()">Cancel</button>

    </form>
  </div>

  <script>
    function openDepositForm() {
      document.getElementById("depositForm").style.display = "block";
      document.getElementById("withdrawalForm").style.display = "none";
      document.getElementById("transferForm").style.display = "none";
    }

    function closeDepositForm() {
      document.getElementById("depositForm").style.display = "none";
    }

    function openWithdrawalForm() {
      document.getElementById("depositForm").style.display = "none";
      document.getElementById("withdrawalForm").style.display = "block";
      document.getElementById("transferForm").style.display = "none";
    }

    function closeWithdrawalForm() {
      document.getElementById("withdrawalForm").style.display = "none";
    }

    function openTransferForm() {
      document.getElementById("depositForm").style.display = "none";
      document.getElementById("withdrawalForm").style.display = "none";
      document.getElementById("transferForm").style.display = "block";
    }

    function closeTransferForm() {
      document.getElementById("transferForm").style.display = "none";
    }
  </script>
</body>

</html>