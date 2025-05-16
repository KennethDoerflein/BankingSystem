<?php
//gets db connection info
require_once './db_connect.php'; // Ensure this path is correct and the file establishes $db

//gets session info
session_start();

// Check if the database connection was successful
if (!isset($db) || $db->connect_error) {
  // Log error details securely, don't expose to user
  error_log("Database connection failed: " . ($db->connect_error ?? 'Unknown error'));
  // Set a generic error message for the user
  $_SESSION['error_message'] = 'Database connection error. Please try again later.'; // Use a generic session key
  // Optionally redirect to an error page or login page with a generic message
  // header('Location: error_page.php'); // Or back to login
  // For now, just exit cleanly if DB connection failed before session check
  die("Database connection failed. Please try again later."); // Avoid exposing details
}

// Initialize notice variable
$notice = '';
$results = null;
$num_results = 0;
$numOfAccounts = 0;

// Process session messages
if (isset($_SESSION['transaction_failed'])) {
  if ($_SESSION['transaction_failed'] == 'insufficentBalance') {
    $notice = 'Insufficient balance.';
  } else if ($_SESSION['transaction_failed'] == 'doesntOwnAcct') {
    $notice = 'Account does not exist or you do not have access to it.';
  }
  $_SESSION['transaction_failed'] = '';
}

// Process other session messages
if (isset($_SESSION['externalTransferSuccess']) && $_SESSION['externalTransferSuccess'] == 'successful') {
  $notice = 'External transfer was successful.';
  $_SESSION['externalTransferSuccess'] = '';
} else if (isset($_SESSION['transferSuccess']) && $_SESSION['transferSuccess'] == 'successful') {
  $notice = 'Transfer was successful.';
  $_SESSION['transferSuccess'] = '';
} else if (isset($_SESSION['depositSuccess']) && $_SESSION['depositSuccess'] == 'successful') {
  $notice = 'Deposit was successful.';
  $_SESSION['depositSuccess'] = '';
} else if (isset($_SESSION['withdrawalSuccess']) && $_SESSION['withdrawalSuccess'] == 'successful') {
  $notice = 'Withdrawal was successful.';
  $_SESSION['withdrawalSuccess'] = '';
} else if (isset($_SESSION['acctRegDone']) && $_SESSION['acctRegDone'] == 'done') {
  $notice = 'Account successfully created. Pending approval...';
  $_SESSION['acctRegDone'] = '';
} else if (isset($_SESSION['acctDeletionDone']) && $_SESSION['acctDeletionDone'] == 'done') {
  $notice = 'Account deletion request successful. Pending approval...';
  $_SESSION['acctDeletionDone'] = '';
}

//checks if user has logged in. if not, redirects to login page
// Use isset() for boolean check as well for clarity and safety
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  $_SESSION['needlog'] = true;
  header('Location: login.php');
  //closes db connection (ensure $db exists before closing)
  $db->close(); // Close connection before exiting
  exit();
}


## Start - stop admin from viewing page
// Ensure $_SESSION['user'] is set before using in query
if (!isset($_SESSION['user'])) {
  // Handle error: User session variable not set
  // Redirect to login or show error
  header('Location: login.php');
  $db->close(); // Close connection before exiting
  exit();
}

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
## End - stop admin from viewing page

// Get user's accounts
$stmt = $db->prepare("SELECT a.*, c.cFname FROM accounts a 
                      JOIN customer c ON c.customerID = a.ownerID 
                      WHERE c.cUsername = ? 
                      ORDER BY status ASC");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$results = $stmt->get_result();

if ($results) {
  $num_results = $results->num_rows;
  $numOfAccounts = $num_results;

  if ($num_results > 0) {
    $row = $results->fetch_assoc();
    $user = $_SESSION['user'];
    $fname = $row['cFname'];
    // Reset pointer for main loop
    $results->data_seek(0);
  }
  // Populate $acctsList with approved account numbers for dropdowns
  $acctsList = array();
  $results->data_seek(0); // Ensure pointer is at start
  while ($acctRow = $results->fetch_assoc()) {
    if ($acctRow['status'] === 'approved') {
      $acctsList[] = $acctRow['bankAccountNumber'];
    }
  }
  $results->data_seek(0); // Reset pointer again for later use
}
$stmt->close();

// Use prepared statements or ensure proper escaping for counting queries too
$query2 = "SELECT COUNT(*) as count FROM `accounts` as a JOIN `customer` as c ON c.customerID = a.ownerID WHERE c.cUsername = '" . $db->real_escape_string($_SESSION['user']) . "' AND status = 'approved'";
$result2s = $db->query($query2);
$numOfApprovedAccounts = 0;
if ($result2s) {
  $countRow = $result2s->fetch_assoc();
  $numOfApprovedAccounts = $countRow['count'];
  $result2s->free();
} else {
  error_log("Error counting approved accounts: " . $db->error);
  // Handle error appropriately, maybe set count to 0 or show error
}

$query3 = "SELECT COUNT(*) as count FROM `accounts` as a JOIN `customer` as c ON c.customerID = a.ownerID WHERE c.cUsername = '" . $db->real_escape_string($_SESSION['user']) . "' AND status = 'pending approval'";
$result3s = $db->query($query3);
$numOfpendingAccounts = 0;
if ($result3s) {
  $countRow = $result3s->fetch_assoc();
  $numOfpendingAccounts = $countRow['count'];
  $result3s->free();
} else {
  error_log("Error counting pending accounts: " . $db->error);
  // Handle error appropriately
}


function maskAccountNumber($number)
{
  // Ensure $number is a string
  $number = (string) $number;
  if (strlen($number) <= 4) {
    return $number; // Or handle as an error/edge case like return '****';
  }
  $mask_number =  str_repeat("*", strlen($number) - 4) . substr($number, -4);
  return $mask_number;
}

// Replace money_format with NumberFormatter if intl extension is available, else use number_format
if (class_exists('NumberFormatter')) {
  $currencyFormatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
  function formatCurrency($amount, $currency = 'USD')
  {
    global $currencyFormatter;
    return $currencyFormatter->formatCurrency($amount, $currency);
  }
} else {
  // Fallback if intl extension is not enabled
  function formatCurrency($amount, $currency = 'USD')
  {
    // Basic fallback, consider currency symbol more carefully if needed
    return '$' . number_format($amount, 2);
  }
}


// $db->close(); // Close connection at the end of the script
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./style.css"> <!-- Ensure this path is correct -->
  <title>MKJJ</title>
  <link rel="icon" type="image/x-icon" href="assets/icon_draft1.png"> <!-- Ensure this path is correct -->

</head>

<body>
  <div class="alert alert-info text-center mb-0" role="alert" style="border-radius:0;">
    <strong>Educational Use Only:</strong> This site is for educational purposes. All content, data, and entities are entirely fictitious.
  </div>
  <ul>
    <!--<li><a class="active admin_home" href="./employee/homepage_admin.php">Admin Home</a></li>-->
    <li><a class="active" href="./homepage.php">Home</a></li>
    <!-- Search form commented out -->
    <li style="float:right"><a class="active" href="./scripts/logout.php">Log Out</a></li>
    <li style="float:right"><a class="active" href="./account.php">Settings</a></li>
    <li style="float:right"><a class="active" href="./about.php">About</a></li>
    <li style="float:right"><a class="active" href="./services.php">Services</a></li>
  </ul>
  <style>
    body {
      font-size: 24px;
      /* Consider using relative units like rem or em for better accessibility */
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .form-popup {
      display: none;
      position: fixed;
      bottom: 25px;
      right: 25px;
      border: 5px solid #000000;
      z-index: 9;
      background-color: white;
      /* Added background color */
      max-width: 90%;
      /* Max width for smaller screens */
      width: 400px;
      /* Fixed width */
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      /* Added shadow for better visibility */
    }

    .form-container {
      max-width: 100%;
      /* Allow container to fill popup */
      padding: 20px;
      box-sizing: border-box;
      /* Include padding in width */
    }

    .form-container label {
      /* Style labels */
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }

    .form-container input[type=number],
    .form-container input[type=text],
    /* Added text type */
    .form-container select {
      /* Style inputs and selects */
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      box-sizing: border-box;
      border-radius: 4px;
    }

    .form-container .btn {
      background-color: #04AA6D;
      color: white;
      padding: 14px 20px;
      /* Adjusted padding */
      border: none;
      cursor: pointer;
      width: 100%;
      margin-bottom: 10px;
      opacity: 0.9;
      /* Slightly adjusted opacity */
      border-radius: 4px;
      /* Added border-radius */
      font-size: 16px;
      /* Set font size */
    }

    .form-container .btn:hover {
      opacity: 1;
    }

    .form-container .cancel {
      background-color: #f44336;
      /* Changed to a common red */
    }

    /* Account Card styles */
    .account-card {
      border: 1px solid #ccc;
      padding: 15px;
      margin-bottom: 15px;
      border-radius: 5px;
      display: flex;
      /* Use flexbox for layout */
      justify-content: space-around;
      /* Space out items */
      align-items: center;
      /* Center items vertically */
      background-color: #f9f9f9;
      flex-wrap: wrap;
    }

    .card-info {
      text-align: center;
      flex-basis: 30%;
      min-width: 120px;
      margin: 5px 0;
    }

    .vl {
      /* Vertical line style */
      border-left: 2px solid #ccc;
      height: 50px;
      /* Adjust height as needed */
      margin: 0 10px;
    }

    @media (max-width: 600px) {
      .account-card {
        flex-direction: column;
        align-items: stretch;
        padding: 10px;
      }

      .card-info {
        flex-basis: 100%;
        min-width: unset;
        margin: 8px 0;
        font-size: 1.1em;
      }

      .vl {
        display: none;
      }
    }

    .mkjj-button {
      /* General button style */
      padding: 10px 15px;
      margin: 5px;
      border: none;
      border-radius: 4px;
      color: white;
      cursor: pointer;
      font-size: 16px;
    }

    button.mkjj-button {
      /* Default background for non-specific buttons */
      background-color: #555;
    }
  </style>
  <nav class="heading">
    <h1>
      <!-- Use htmlspecialchars to prevent XSS -->
      <center>Hello,<?php echo " " . htmlspecialchars($_SESSION['user'] ?? 'Guest', ENT_QUOTES, 'UTF-8'); ?> </center>
    </h1>
    <center>
      <!-- Use htmlspecialchars for notice output -->
      <div style='color: red;'><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
    </center>
  </nav>
  <hr>
  <div class="section-wrapper">
    <h2>Account Management</h2>
    <?php
    echo '<button style="background-color: green" class="mkjj-button" onclick="openBankAccountForm()"><b>Create New Bank Account</b></button>';
    // Use the counts derived earlier ($numOfApprovedAccounts specifically for functional buttons)
    if ($numOfApprovedAccounts > 0) {
      echo '<button style = "background-color: red" class="mkjj-button" onclick="opencloseBankAccountForm()"><b>Close Bank Account</b></button>';
      echo '<hr style="border:none;">';
      echo '<button class="mkjj-button" onclick="openDepositForm()">Deposit</button>';
      echo '<button class="mkjj-button" onclick="openWithdrawalForm()">Withdrawal</button>';
      echo '<button class="mkjj-button" onclick="openTransferForm()">Transfer</button>';
      echo '<button class="mkjj-button" onclick="openHistoryForm()">History</button>';
    } else if ($numOfAccounts > 0) {
      // If accounts exist but none are approved yet
      echo '<p>Your account actions will be available once an account is approved.</p>';
    }
    ?>
  </div>

  <hr>
  <div class="section-wrapper">
    <h2>Bank Accounts</h2>
    <div>
      <?php
      // Check if $results is valid and has rows before attempting to iterate
      if ($results && $numOfAccounts > 0) {
        $results->data_seek(0); // Reset pointer to fetch all rows again

        while ($row = $results->fetch_assoc()) { // Loop through all fetched rows
          // Use proper quoted key access and htmlspecialchars for output
          if ($row['status'] == "approved") {
            echo "<div class=\"account-card\">";
            echo '<div class="card-info"><b>Account Number</b><br>' . htmlspecialchars(maskAccountNumber($row['bankAccountNumber']), ENT_QUOTES, 'UTF-8') . '</div>';
            echo '<div class="vl"></div>';
            echo '<div class="card-info"><b>Account Type</b><br>' . htmlspecialchars($row['accountType'], ENT_QUOTES, 'UTF-8') . '</div>';
            echo '<div class="vl"></div>';
            echo '<div class="card-info"><b>Balance</b><br>' . htmlspecialchars(formatCurrency($row['balance']), ENT_QUOTES, 'UTF-8') . '</div>';
            // echo '<br>'; // Removed extra break
            echo "</div>";
          } else if ($row['status'] == "pending approval") {
            echo "<div class=\"account-card\">";
            echo '<div class="card-info"><b>Account Number</b><br>' . htmlspecialchars(maskAccountNumber($row['bankAccountNumber']), ENT_QUOTES, 'UTF-8') . '</div>';
            echo '<div class="vl"></div>';
            echo '<div class="card-info" style="color: orange;"><b>Status</b><br>Pending Approval</div>'; // Added color hint
            // echo '<br>'; // Removed extra break
            echo "</div>";
          } else if ($row['status'] == "pending deletion") {
            echo "<div class=\"account-card\">";
            echo '<div class="card-info"><b>Account Number</b><br>' . htmlspecialchars(maskAccountNumber($row['bankAccountNumber']), ENT_QUOTES, 'UTF-8') . '</div>';
            echo '<div class="vl"></div>';
            echo '<div class="card-info" style="color: red;"><b>Status</b><br>Pending Deletion</div>'; // Added color hint
            // echo '<br>'; // Removed extra break
            echo "</div>";
          }
        }
        $results->free(); // Free result set when done
      } else if ($numOfApprovedAccounts == 0 && $numOfpendingAccounts == 0 && $numOfAccounts == 0) {
        // Check if truly no accounts exist at all
        echo "<p>No bank accounts found. You can create one using the button above.</p>";
      } else if ($numOfAccounts == 0) {
        // Fallback message if $numOfAccounts somehow became 0 after initial check
        echo "<p>No accounts found.</p>";
      }
      ?>
    </div>
  </div>


  <!--FORMS ---------------------------------------------------------------------------------------------------------------------------------------------------------->

  <!-- Bank Account Creation Form -->
  <div class="form-popup" id="bankAccountForm">
    <form action='./scripts/create_bank_account.php' method='post' class="form-container">
      <h1>Create Bank Account</h1>
      <label for="acctType"><b>Account Type:</b></label>
      <select name="acct" id="accttype" required> <!-- Added required -->
        <option value="savings">Savings</option>
        <option value="checking">Checking</option>
      </select>
      <label for="initDeposit"><b>Initial Deposit: $</b></label>
      <input type="number" min="0.00" step="0.01" placeholder="Enter deposit amount" name="initDeposit" required>
      <button type="submit" class="btn">Create Account</button>
      <button type="button" class="btn cancel" onclick="closeBankAccountForm()">Cancel</button>
    </form>
  </div>

  <!-- Deposit Form -->
  <div class="form-popup" id="depositForm">
    <form action='./scripts/deposit.php' method='post' class="form-container">
      <h1>Deposit</h1>
      <label for="deposit"><b>Deposit Amount: $</b></label>
      <input type="number" min="0.01" step="0.01" placeholder="Enter deposit amount" name="deposit" required>
      <label for="account_num_deposit"><b>Account Number:</b></label>
      <select name="account_num" id="account_num_deposit" required>
        <?php
        if (!empty($acctsList)) {
          foreach ($acctsList as $acct) {
            echo '<option value="' . htmlspecialchars($acct, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(maskAccountNumber($acct), ENT_QUOTES, 'UTF-8') . '</option>';
          }
        } else {
          echo '<option value="" disabled>No approved accounts available</option>';
        }
        ?>
      </select>
      <button type="submit" class="btn" <?php if (empty($acctsList)) echo 'disabled'; ?>>Confirm Deposit</button>
      <button type="button" class="btn cancel" onclick="closeDepositForm()">Cancel</button>
    </form>
  </div>

  <!-- Withdrawal Form -->
  <div class="form-popup" id="withdrawalForm">
    <form action='./scripts/withdrawal.php' method='post' class="form-container">
      <h1>Withdrawal</h1>
      <label for="withdrawal"><b>Withdrawal Amount: $</b></label>
      <input type="number" min="0.01" step="0.01" placeholder="Enter withdrawal amount" name="withdrawal" required>
      <label for="account_num_withdraw"><b>Account Number:</b></label>
      <select name="account_num" id="account_num_withdraw" required>
        <?php
        if (!empty($acctsList)) {
          foreach ($acctsList as $acct) {
            echo '<option value="' . htmlspecialchars($acct, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(maskAccountNumber($acct), ENT_QUOTES, 'UTF-8') . '</option>';
          }
        } else {
          echo '<option value="" disabled>No approved accounts available</option>';
        }
        ?>
      </select>
      <button type="submit" class="btn" <?php if (empty($acctsList)) echo 'disabled'; ?>>Confirm Withdrawal</button>
      <button type="button" class="btn cancel" onclick="closeWithdrawalForm()">Cancel</button>
    </form>
  </div>

  <!-- Transfer Form -->
  <div class="form-popup" id="transferForm">
    <form action='./scripts/transfer.php' method='post' class="form-container">
      <h1>Transfer</h1>
      <label for="transfer"><b>Transfer Amount: $</b></label>
      <input type="number" min="0.01" step="0.01" placeholder="Enter transfer amount" name="transfer" required>

      <label for="sender_account_num"><b>From Account:</b></label>
      <select name="sender_account_num" id="sender_account_num" required>
        <?php
        if (!empty($acctsList)) {
          foreach ($acctsList as $acct) {
            echo '<option value="' . htmlspecialchars($acct, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(maskAccountNumber($acct), ENT_QUOTES, 'UTF-8') . '</option>';
          }
        } else {
          echo '<option value="" disabled>No approved accounts available</option>';
        }
        ?>
      </select>

      <label for="typeOfTransfer"><b>Transaction Type:</b></label>
      <select name="transferType" id="typeOfTransfer" required>
        <option value="internal">Internal (To another MKJJ account)</option>
        <option value="external">External (Simulated)</option>
      </select>

      <label for="receiver_account_num"><b>Receiver Account Number:</b></label>
      <input type="text" pattern="^[0-9]{12}$" title="Account number must be 12 digits." placeholder="Enter 12-digit account number" name="receiver_account_num" required>

      <button type="submit" class="btn" <?php if (empty($acctsList)) echo 'disabled'; ?>>Confirm Transfer</button>
      <button type="button" class="btn cancel" onclick="closeTransferForm()">Cancel</button>
    </form>
  </div>

  <!-- History Form -->
  <div class="form-popup" id="historyForm">
    <form action='./history.php' method='post' class="form-container">
      <h1>View Account History</h1>
      <label for="account_num_history"><b>Account Number:</b></label>
      <select name="account_num" id="account_num_history" required>
        <?php
        if (!empty($acctsList)) {
          foreach ($acctsList as $acct) {
            echo '<option value="' . htmlspecialchars($acct, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(maskAccountNumber($acct), ENT_QUOTES, 'UTF-8') . '</option>';
          }
        } else {
          echo '<option value="" disabled>No approved accounts available</option>';
        }
        ?>
      </select>
      <button type="submit" class="btn" <?php if (empty($acctsList)) echo 'disabled'; ?>>View History</button>
      <button type="button" class="btn cancel" onclick="closeHistoryForm()">Cancel</button>
    </form>
  </div>

  <!-- Close Bank Account Form -->
  <div class="form-popup" id="closeBankAccountForm">
    <form action='./scripts/close_bank_account.php' method='post' class="form-container">
      <h1>Close Bank Account</h1>
      <p style="color:red;">Warning: This action is permanent and requires administrator approval.</p>
      <label for="account_num_close"><b>Account Number to Close:</b></label>
      <select name="account_num" id="account_num_close" required>
        <?php
        if (!empty($acctsList)) {
          foreach ($acctsList as $acct) {
            echo '<option value="' . htmlspecialchars($acct, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars(maskAccountNumber($acct), ENT_QUOTES, 'UTF-8') . '</option>';
          }
        } else {
          echo '<option value="" disabled>No approved accounts available</option>';
        }
        ?>
      </select>
      <!-- Password confirmation might be a good idea here in a real system -->
      <button type="submit" class="btn" <?php if (empty($acctsList)) echo 'disabled'; ?>>Request Account Closure</button>
      <button type="button" class="btn cancel" onclick="closecloseBankAccountForm()">Cancel</button>
    </form>
  </div>

  <script>
    // Helper function to close all popups first
    function closeAllPopups() {
      var popups = document.querySelectorAll('.form-popup');
      popups.forEach(function(popup) {
        popup.style.display = 'none';
      });
    }

    // Functions to open specific popups
    function openBankAccountForm() {
      closeAllPopups();
      document.getElementById("bankAccountForm").style.display = "block";
    }

    function openDepositForm() {
      closeAllPopups();
      document.getElementById("depositForm").style.display = "block";
    }

    function openWithdrawalForm() {
      closeAllPopups();
      document.getElementById("withdrawalForm").style.display = "block";
    }

    function openTransferForm() {
      closeAllPopups();
      document.getElementById("transferForm").style.display = "block";
    }

    function openHistoryForm() {
      closeAllPopups();
      document.getElementById("historyForm").style.display = "block";
    }

    function opencloseBankAccountForm() {
      closeAllPopups();
      document.getElementById("closeBankAccountForm").style.display = "block";
    }

    // Functions to close specific popups (could also just use closeAllPopups)
    function closeBankAccountForm() {
      document.getElementById("bankAccountForm").style.display = "none";
    }

    function closeDepositForm() {
      document.getElementById("depositForm").style.display = "none";
    }

    function closeWithdrawalForm() {
      document.getElementById("withdrawalForm").style.display = "none";
    }

    function closeTransferForm() {
      document.getElementById("transferForm").style.display = "none";
    }

    function closeHistoryForm() {
      document.getElementById("historyForm").style.display = "none";
    }

    function closecloseBankAccountForm() {
      document.getElementById("closeBankAccountForm").style.display = "none";
    }
  </script>
  <?php
  // Close the database connection at the very end of script execution
  if (isset($db) && $db instanceof mysqli) {
    $db->close();
  }
  ?>
</body>

</html>