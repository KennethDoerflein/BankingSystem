<?php
//creates new database object
@$db = new mysqli('localhost', 'mkjj_user', 'PASSWORD_PLACEHOLDER', 'mkjj');

//checks connection to database
if (mysqli_connect_errno()) {
  echo 'Error: could not connect to database. Please try again later.';
  exit();
}
