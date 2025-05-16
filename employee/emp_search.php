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

// Initialize results array
$searchResults = array();
$searchType = '';
$searchTerm = '';

// Process search if form submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $searchType = trim($_POST['searchtype']);
  $searchTerm = trim($_POST['searchterm']);

  if ($searchType && $searchTerm) {
    try {
      switch ($searchType) {
        case 'customer':
          $stmt = $db->prepare("SELECT * FROM customer WHERE cUsername LIKE ? OR customerID LIKE ? OR cFname LIKE ? OR cLname LIKE ?");
          $searchPattern = "%$searchTerm%";
          $stmt->bind_param("ssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern);
          break;

        case 'account':
          $stmt = $db->prepare("SELECT a.*, c.cUsername, c.cFname, c.cLname 
                                        FROM accounts a 
                                        JOIN customer c ON a.ownerID = c.customerID 
                                        WHERE a.bankAccountNumber LIKE ?");
          $searchPattern = "%$searchTerm%";
          $stmt->bind_param("s", $searchPattern);
          break;

        case 'transaction':
          $stmt = $db->prepare("SELECT t.*, a.accountType, c.cUsername 
                                        FROM transactions t 
                                        JOIN accounts a ON t.bankAccountNumber = a.bankAccountNumber 
                                        JOIN customer c ON a.ownerID = c.customerID 
                                        WHERE t.transactionID LIKE ?");
          $searchPattern = "%$searchTerm%";
          $stmt->bind_param("s", $searchPattern);
          break;
      }

      if ($stmt) {
        $stmt->execute();
        $searchResults = $stmt->get_result();
        $stmt->close();
      }
    } catch (Exception $e) {
      error_log("Search error: " . $e->getMessage());
      $searchError = "An error occurred while performing the search.";
    }
  }
}

$db->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../style.css">
  <title>MKJJ</title>
  <title>Employee Search - Admin</title>
</head>

<body>
  <div class="alert alert-info text-center mb-0" role="alert" style="border-radius:0;">
    <strong>Educational Use Only:</strong> This site is for educational purposes. All content, data, and entities are entirely fictitious.
  </div>

  <ul>
    <li><a class="active admin_home" href="./homepage_admin.php">Admin Home</a></li>
    <li style="float:right"><a class="active" href="../scripts/logout.php">Log Out</a></li>
    <li style="float:right"><a class="active" href="./emp_settings.php">Settings</a></li>
  </ul>
  <style>
    body {
      font-size: 24px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    button {
      font-size: 16px;
    }

    .phpcode {
      padding: 12px;
    }
  </style>
  <div class="hero-wrapper">
    <div class="hero-wrapper-squared">
      <h1>MKJJ Online Banking System</h1>
    </div>
  </div>

  <div style="text-align: center;">
    <h3>
      Search:
      <form action='./emp_search.php' method='post'>
        <select name="searchtype">
          <option value="account">Account</option>
          <option value="transaction">Transaction</option>
        </select>
        <input name="searchterm" type="text" size="20"> <input type="submit" name="submit" value="Search">
      </form>
    </h3>
  </div>
  <hr>

  <div class="results">
    <?php
    if (!empty($searchResults) && $searchResults instanceof mysqli_result && $searchResults->num_rows > 0) {
      // echo "<h3>Search Results:</h3>";
      while ($row = $searchResults->fetch_assoc()) {
        echo "<div class='result-item' style='background:#f8f9fa; border-radius:8px; box-shadow:0 2px 8px #0001; margin:20px auto; max-width:600px; padding:20px 30px;'>";
        echo "<table style='width:100%; border-collapse:collapse;'>";
        foreach ($row as $key => $value) {
          echo "<tr><td style='font-weight:bold; color:#2c3e50; padding:6px 10px; width:40%;'>" . htmlspecialchars($key) . ":</td>";
          echo "<td style='padding:6px 10px; color:#34495e;'>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
        echo "</div>";
      }
    } elseif (isset($searchError)) {
      echo "<p style='text-align:center; color: red;'>$searchError</p>";
    } elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
      echo "<p style='text-align:center;'>No results found.</p>";
    }
    ?>
  </div>

</body>

</html>