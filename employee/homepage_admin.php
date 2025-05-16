<?php
//gets db connection info
require_once '../db_connect.php';

//gets session info
session_start();

//checks if user has logged in. if not, redirects to login page
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
  $employeeID = htmlspecialchars($row['employeeID']);
  $username = htmlspecialchars($row['eUsername']);
  $fname = htmlspecialchars($row['eFname']);
  $lname = htmlspecialchars($row['eLname']);
  $email = htmlspecialchars($row['eEmail']);
  $address = htmlspecialchars($row['eAddress']); // FIX: use eAddress
}
$stmt->close();

// Get counts of pending accounts
$stmt = $db->prepare("SELECT COUNT(*) as count FROM accounts WHERE status = 'pending approval' OR status = 'pending deletion'");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$numPendingAccounts = $row['count'];
$stmt->close();

$db->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style.css">
  <title>MKJJ</title>
  <link rel="icon" type="image/x-icon" href="assets/icon_draft1.png">
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
    <!--<li style="float:right"><a class="active" href="../about.html">About</a></li>-->
    <!--<li style="float:right"><a class="active" href="../services.php">Services</a></li>-->
  </ul>
  <style>
    body {
      font-size: 24px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

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
  <nav class="heading">
    <h1>
      <center>Hello,<?php echo " " . $_SESSION['user']; ?> </center>
    </h1>
    <center>
      <div style='color: red;'><?php echo $notice; ?></div>
    </center>
  </nav>
  <hr>
  <div class="section-wrapper">
    <h2>Admin Dashboard</h2>
    <?php
    echo '<button style="background-color: blue" class="mkjj-button" onclick="openAdminHistoryForm()"><b>History</b></button>';
    // echo '<button style="background-color: blue" class="mkjj-button" onclick="openBankAccountForm()"><b>Requests</b></button>';
    // <form action="./scripts/account_requests.php: method="post">
    ?>
    <button style="background-color: blue" class="mkjj-button" onclick="location.href='./account_requests.php'"><b>Requests</b> </button>
    <button style="background-color: blue" class="mkjj-button" onclick="location.href='./emp_search.php'"><b>Search</b> </button>

    <hr style="border:none;">
    <button style="background-color: blue" class="mkjj-button" onclick="location.href='./addTransaction.php'"> <b>Add Transaction</b> </button>

  </div>

  <div1 class="form-popup" id="adminHistoryForm">

    <form action='./scripts/search_history.php' method='post' class="form-container">
      <h1>History Lookup</h1>

      <p><label for="account_num"><b>Bank Account #:</b></label>
        <input type="number" pattern="4+[0-9]{11}" title="A valid account number starts with a 4 that is followed by 11 more digits. Ex: 412345678901" placeholder="Ex: 444444444444" name="account_num" required>
      </p>
      <button type="submit" class="btn">Search</button>
      <button type="button" class="btn cancel" onclick="closeAdminHistoryForm()">Cancel</button>

    </form>
  </div1>



  <script>
    function openAdminHistoryForm() {
      document.getElementById("adminHistoryForm").style.display = "block";

    }

    function closeAdminHistoryForm() {
      document.getElementById("adminHistoryForm").style.display = "none";
    }
  </script>

</body>

</html>
<?php
// No changes are needed for PHP 8.2 compatibility in this file.
// It does not use get_magic_quotes_gpc or any deprecated features for PHP 8.2.
// No changes made for PHP 8.2 compatibility, file is already compatible.
?>