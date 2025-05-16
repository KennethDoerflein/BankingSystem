<?php
//includes file with db connection
require_once './db_connect.php'; // Ensure this path is correct and the file establishes $db

//gets session info
session_start();

// Check if the database connection was successful
if (!isset($db) || $db->connect_error) {
  // Log error details securely, don't expose to user
  error_log("Database connection failed: " . ($db->connect_error ?? 'Unknown error'));
  // Set a generic error message for the user (optional, depending on desired user experience)
  // $_SESSION['error_message'] = 'Database connection error. Please try again later.';
  die("Database connection failed. Please try again later."); // Avoid exposing details
}


//checks if user has logged in. if not, redirects to login page
// Updated check for clarity and safety
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  $_SESSION['needlog'] = true;
  header('Location: login.php');

  //closes db connection
  $db->close();
  exit();
}

// Check session timestamp for expiration
if (isset($_SESSION["loggedin"]) && isset($_SESSION["login_time_stamp"])) { // Added check for login_time_stamp
  if (time() - $_SESSION["login_time_stamp"] > 600) { // 10 minutes timeout
    session_unset();
    session_destroy();
    header("Location: login.php");
    // Close db connection before exiting
    $db->close();
    exit();
  }
} else if (isset($_SESSION["loggedin"])) {
  // If logged in but no timestamp, treat as invalid state and logout
  session_unset();
  session_destroy();
  header("Location: login.php");
  // Close db connection before exiting
  $db->close();
  exit();
}


## Start - stop admin from viewing page
// Ensure $_SESSION['user'] is set before using in query
if (!isset($_SESSION['user'])) {
  // Handle error: User session variable not set, redirect to login
  header('Location: login.php');
  $db->close();
  exit();
}
// Escape user input to prevent SQL injection
$escapedUser = $db->real_escape_string($_SESSION['user']);
$employeeTest = "SELECT eUsername FROM employee WHERE eUsername = '" . $escapedUser . "'";

//gets info from db
$isEMP = $db->query($employeeTest);

// Check if query execution failed
if (!$isEMP) {
  // Log the database error
  error_log("Database query failed: " . $db->error);
  // Inform user or redirect
  echo "Error checking user role. Please try again later."; // Or redirect to an error page
  $db->close();
  exit();
}

$num_EMP = $isEMP->num_rows;
if ($num_EMP > 0) {
  $_SESSION['hasAccess'] = false;
  header('Location: ./employee/emp_login.php');

  // Free result set before closing connection
  $isEMP->free();
  //closes db connection
  $db->close();
  exit();
}
// Free result set if query was successful but user is not an employee
$isEMP->free();
## End - stop admin from viewing page

// Removed $db->close(); here - it should be at the end of the script execution.
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="./style.css"> <!-- Ensure path is correct -->
  <title>MKJJ - About</title> <!-- Changed title -->
  <link rel="icon" type="image/x-icon" href="assets/icon_draft1.png"> <!-- Ensure path is correct -->
</head>

<body>
  <ul>
    <li><a class="active" href="./homepage.php">Home</a></li>
    <li style="float:right"><a class="active" href="./scripts/logout.php">Log Out</a></li>
    <li style="float:right"><a class="active" href="./account.php">Settings</a></li>
    <li style="float:right"><a class="active" href="./about.php">About</a></li>
    <li style="float:right"><a class="active" href="./services.php">Services</a></li>
  </ul>
  <style>
    .heading h1 {
      margin-top: 20px;
      margin-bottom: 10px;
    }

    .content-section {
      /* Added a wrapper class for content */
      max-width: 800px;
      /* Limit width for readability */
      margin: 20px auto;
      /* Center content */
      padding: 15px;
      text-align: center;
      /* Keep center alignment as per original */
    }

    .content-section p {
      margin-bottom: 1em;
      /* Space between paragraphs */
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

    .content-section, .section-wrapper, .info {
      max-width: 800px;
      margin: 20px auto;
      padding: 15px;
      text-align: center;
    }

    @media (max-width: 900px) {
      .content-section, .section-wrapper, .info {
        padding: 10px 5vw;
      }
    }

    @media (max-width: 600px) {
      .content-section, .section-wrapper, .info {
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
      <center>About Us</center> <!-- Changed to About Us -->
    </h1>
  </nav>
  <hr>
  <div class="content-section"> <!-- Wrapped content for better structure -->
    <p>The MKJJ Banking system was designed and developed by 2 Montclair State University Students from the Computer Science Department.</p>
    <div>
      <p><b>Developers:</b></p> <!-- Added heading -->
      <p>Kenneth Doerflein</p>
      <p>Mark Mauro</p>
    </div>

    <div>
      <p><b>Our Mission:</b></p> <!-- Added heading -->
      <p>Our mission is to provide you with the easiest and most streamlined way to manage your online banking.</p>
      <p>We strive for the satisfaction of all of our clients, in terms of ease of use, security, customer support, and much much more.</p>
    </div>
  </div>

  <?php
  // Close the database connection at the very end
  if (isset($db) && $db instanceof mysqli) { // Check if $db is a valid mysqli object
    $db->close();
  }
  ?>
</body>

</html>