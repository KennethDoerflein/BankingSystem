<?php
//gets db connection info
require_once '../db_connect.php';

//gets session info
session_start();

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
  header('Location: ./homepage_admin.php');
  exit();
}

$notice = '';
if (isset($_SESSION['needlog']) && $_SESSION['needlog'] === true) {
  $notice = 'Please log in to access your account.';
  $_SESSION['needlog'] = false;
} else if (isset($_SESSION['login_failed'])) {
  if ($_SESSION['login_failed'] === 'invalid_input') {
    $notice = 'Missing input. Please try again.';
  } else if ($_SESSION['login_failed'] === 'invalid_login') {
    $notice = 'Invalid username or password.';
  }
  $_SESSION['login_failed'] = '';
}

// Check if user is customer
$stmt = $db->prepare("SELECT cUsername FROM customer WHERE cUsername = ?");
if (isset($_SESSION['user'])) {
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
  <link rel="stylesheet" href="../login.css">
  <link rel="stylesheet" href="../style.css">
  
  <title>MKJJ</title>
  <link rel="icon" type="image/x-icon" href="assets/icon_draft1.png">
</head>

<!--login.php takes username and password input from a user that already made an account-->

<body>
  <div class="alert alert-info text-center mb-0" role="alert" style="border-radius:0;">
    <strong>Educational Use Only:</strong> This site is for educational purposes. All content, data, and entities are entirely fictitious.
  </div>
  <style>
    body {
      font-size: 22px;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    a {
      color: grey;
    }

    h1 {
      text-align: center;
    }

    a:hover {
      color: #953636;
      transition: 1s;
    }

    button {
      font-size: 16px;
    }

    .phpcode {
      padding: 12px;
    }

    #log-in {
      width: 30%;
      padding: 60px;
      margin: auto;
      text-align: center;
    }

    input {
      width: 100%;
      padding: 12px;
      margin: 10px 0px;
      box-sizing: border-box;
    }

    select {
      width: 100%;
      padding: 12px;
      margin: 10px 0px;
      box-sizing: border-box;
    }

    button[type="submit"] {
      background-color: black;
      border: none;
      color: white;
      padding: 12px 30px;
      text-decoration: none;
      margin: 12px 2px;
      cursor: pointer;
    }

    input[type="submit"] {
      background-color: black;
      border: none;
      color: white;
      padding: 12px 30px;
      text-decoration: none;
      margin: 12px 2px;
      cursor: pointer;
    }

    label {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      font-size: 18px;
      font-weight: 100;

      line-height: 30px;
      padding-top: 10px;
    }

    @media only screen and (max-width: 600px) {
      body {
        font-size: 16px;
      }

      #log-in {
        width: 70%;
        padding: 30px;
        margin: auto;
        text-align: center;
      }
    }

    .phpcode {
      padding: 12px;
    }
  </style>
  <!--outputs notice for user-->
  <center>
    <div style='color: red;'><?php echo $notice; ?></div>
  </center>

  <div id="log-in">

    <!--passes inputs to emp_confirm.php to process-->
    <form action='scripts/emp_login_confirm.php' method='post'>
      <div class="log-in">
        <h1>
          <center>ADMIN LOGIN</center>
        </h1>
        <p>
          <center>Please enter your employee username and password:</center>
        </p>
        <div>
          <!--input for username-->
          <input type="text" placeholder="Username" name='user' required />
        </div>
        <div>
          <!--input for password-->
          <input type="password" placeholder="Password" name='pass' required />
        </div>
        <!--submit inputs-->
        <div>
          <input type="submit" value="LOGIN" name="submit" id="button_submit" /> <input type="reset" value="CLEAR" />
        </div>
      </div>
    </form>

    <!--other access links-->
    <div>
      <center>
        A customer? <a class="link" href="../login.php">Log In</a>
      </center>
    </div>
  </div>
</body>

</html>