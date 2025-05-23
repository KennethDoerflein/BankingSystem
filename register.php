<?php
// No changes made
//gets session info
session_start();

//notifies user if not everything was input
if ($_SESSION['registration_failed'] == 'invalid_input') {
  $notice = 'Registration info was not properly input. Please try again.';

  $_SESSION['registration_failed'] = '';
}

//notifies user if password is not long enough
else if ($_SESSION['registration_failed'] == 'invalid_password') {
  $notice = 'Password must be at least 6 characters. Please try again.';

  $_SESSION['registration_failed'] = '';
}

//notifies user if passwords do not match
else if ($_SESSION['registration_failed'] == 'pwdnotmatch') {
  $notice = 'Passwords do not match. Please try again.';

  $_SESSION['registration_failed'] = '';
}

//notifies user if username is already taken
else if ($_SESSION['registration_failed'] == 'usertaken') {
  $notice = 'Username already taken. Please try again.';

  $_SESSION['registration_failed'] = '';
} else if ($_SESSION['registration_failed'] == 'emailtaken') {
  $notice = 'Email already in use. Please try again.';

  $_SESSION['registration_failed'] = '';
} else if ($_SESSION['registration_failed'] == 'phonenumbertaken') {
  $notice = 'Phone Number already in use. Please try again.';

  $_SESSION['registration_failed'] = '';
}

//notifies user if some other error occurs
else if ($_SESSION['registration_failed'] == 'randerr') {
  $notice = 'An error has occurred. Please try again.';

  $_SESSION['registration_failed'] = '';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!--<link rel="stylesheet" href="login.css">-->
  <title>MKJJ</title>
  <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
  <link rel="stylesheet" href="./style.css">
</head>

<!--register.php takes a new user's username, password, first name, middle name, last name, street address,
        city, state, and zip code-->

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
  </style>
  <!--outputs notice for user-->
  <div style='color: red;'><?php echo $notice; ?></div>

  <div id="log-in">

    <!--passes inputs to register_confirm.php to scripts-->
    <form action='scripts/register_confirm.php' method='post'>
      <div class="log-in">
        <h1>
          <center>REGISTER</center>
        </h1>
        <p>
          <center>Please fill in the information below:</center>
        </p>
        <div>
          <!--input for username-->
          <input type="text" placeholder="Username" name="user" required />
        </div>
        <div>
          <!--input for password-->
          <input type="password" placeholder="Password" name="pass" required />
        </div>
        <div>
          <!--input for confirm password-->
          <input type="password" placeholder="Confirm Password" name="conpass" required />
        </div>
        <div>
          <!--input for email-->
          <input type="email" placeholder="Email" name="email" required />
        </div>
        <div>
          <!--input for tel-->
          <input type="tel" placeholder="Phone number Ex: 999-999-9999" name="phone_num" required pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}" />
        </div>
        <div>
          <!--input for first name-->
          <input type="text" placeholder="First Name" name="fname" rquired />
        </div>
        <!--<div>-->
        <!--input for middle name-->
        <!--<input type="text" placeholder="Middle Name" name="mname" />-->
        <!--</div>-->
        <div>
          <!--input for last name-->
          <input type="text" placeholder="Last Name" name="lname" required />
        </div>
        <div>
          <!--input for street address-->
          <input type="text" placeholder="Street Address" name="stadd" required />
        </div>
        <div>
          <!--input for city-->
          <input type="text" placeholder="City" name="city" required />
        </div>
        <div>
          <!--input for state-->
          <input type="text" placeholder="State" name="state" required />
        </div>
        <div>
          <!--input for zip code-->
          <input type="text" placeholder="Zip Code" name="zip" required />
        </div>
        <!--submit inputs-->
        <div>
          <input type="submit" value="CREATE MY ACCOUNT" name="submit" id="button_submit" /> <input type="reset" value="CLEAR" />
        </div>
      </div>
    </form>

    <!--other access links-->
    <div>
      <center>
        Already have an account? <a class="link" href="login.php">Log In</a> <br>
      </center>
    </div>
  </div>
</body>

</html>