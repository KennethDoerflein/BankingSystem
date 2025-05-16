<?php
//gets db connection info
require_once './db_connect.php';

//gets session info
session_start();

// Initialize notice variables
$notice = '';
$notice2 = '';

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

// Get user information using prepared statement
$stmt = $db->prepare("SELECT * FROM customer WHERE cUsername = ?");
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
  $row = $result->fetch_assoc();
  $username = htmlspecialchars($row['cUsername']);
  $fname = htmlspecialchars($row['cFname']);
  $lname = htmlspecialchars($row['cLname']);
  $email = htmlspecialchars($row['cEmail']);
  $address = htmlspecialchars($row['cAddress']);
  $phone = htmlspecialchars($row['phoneNumber']);
} else {
  // Check if user is employee
  $stmt = $db->prepare("SELECT * FROM employee WHERE eUsername = ?");
  $stmt->bind_param("s", $_SESSION['user']);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result && $result->num_rows > 0) {
    header('Location: ./employee/emp_settings.php');
    $stmt->close();
    $db->close();
    exit();
  }
}
$stmt->close();
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
  <link rel="icon" type="image/x-icon" href="../assets/icon_draft1.png">
</head>

<body>
  <ul>
    <li><a class="active" href="./homepage.php">Home</a></li>
    <!-- <form action="search_apparel.php" method="post">
            <li style="float:right; display: relative; padding-top: 12px; padding-right: 10px;">
                <input name="searchterm" type="text" size="20">
                <input type="submit" name="submit" value="Search">
            </li>
            </form>
            -->
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

    .info, .section-wrapper {
      max-width: 800px;
      margin: 20px auto;
      padding: 15px;
      text-align: center;
    }

    @media (max-width: 900px) {
      .info, .section-wrapper {
        padding: 10px 5vw;
      }
    }

    @media (max-width: 600px) {
      .info, .section-wrapper {
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
      <!-- Use htmlspecialchars to prevent XSS -->
      <p>Welcome back, <?php echo htmlspecialchars($fname, ENT_QUOTES, 'UTF-8'); ?>!</p>
    </center>
  </div>

  <!--prompts user's information-->
  <div class="info">

    <div2><b><u>User Information:</u></div2></b>
    <div></div>
    <center>
      <div>
        First Name: <?php echo htmlspecialchars($fname, ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <div>
        Last Name: <?php echo htmlspecialchars($lname, ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <div>
        Email Address: <?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <div>
        Primary Address: <?php echo htmlspecialchars($address, ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <div>
        Phone Number: <?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    </center>

    <div2><b><u>Security:</u></b></div2>
    <center>
      <center>
        <!-- Use htmlspecialchars for notices too -->
        <div style='color: red;'><?php echo htmlspecialchars($notice2, ENT_QUOTES, 'UTF-8'); ?></div>
      </center>
      <div>
        Username | <button class="editusername" onclick="openUsernameForm()">edit</button>
      </div>
      <div><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></div>
      <center>
        <div style='color: red;'><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
      </center>
      <div>
        Password | <button class="editpass" onclick="openPasswordForm()">edit</button>
      </div>
      <div>*******</div>
    </center>
  </div>

  <div1 class="form-popup" id="passwordForm">
    <form action='./scripts/change_pass.php' method='post' class="form-container">
      <h1>Change Password</h1>

      <label for="oldPass"><b>Old Password</b></label>
      <input type="password" placeholder="Enter Old Password" name="oldpass" required>

      <p><label for="newPass"><b>New Password</b></label>
        <input type="password" placeholder="Enter New Password" name="newpass" onChange="onChange()" required>
      </p>

      <p><label for="newPassConf"><b>Confirm New Password</b></label>
        <input type="password" placeholder="Reenter New Password" name="connewpass" onChange="onChange()" required>
      </p>

      <button type="submit" class="btn">Update Password</button>
      <button type="button" class="btn cancel" onclick="closePasswordForm()">Cancel</button>

    </form>
  </div1>

  <script>
    function onChange() {
      const password = document.querySelector('input[name=newpass]');
      const confirm = document.querySelector('input[name=connewpass]');
      if (confirm.value === password.value) {
        confirm.setCustomValidity('');
      } else {
        confirm.setCustomValidity('Passwords do not match');
      }
    }
  </script>

  <div1 class="form-popup" id="usernameForm">
    <form action='./scripts/change_user.php' method='post' class="form-container">
      <h1>Change Username</h1>

      <label for="oldPass"><b>Password</b></label>
      <input type="password" placeholder="Enter Password" name="pass" required>

      <p><label for="newPass"><b>New Username</b></label>
        <input type="text" placeholder="Enter New Username" name="newuser" required>
      </p>


      <button type="submit" class="btn">Change Username</button>
      <button type="button" class="btn cancel" onclick="closeUsernameForm()">Cancel</button>

    </form>
  </div1>


  <script>
    function openPasswordForm() {
      document.getElementById("passwordForm").style.display = "block";
      document.getElementById("usernameForm").style.display = "none";
    }

    function closePasswordForm() {
      document.getElementById("passwordForm").style.display = "none";
    }

    function openUsernameForm() {
      document.getElementById("usernameForm").style.display = "block";
      document.getElementById("passwordForm").style.display = "none";
    }

    function closeUsernameForm() {
      document.getElementById("usernameForm").style.display = "none";
    }
  </script>


</body>

</html>