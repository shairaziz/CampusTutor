<?php
$servername = "localhost";
$username = "root" ; // mysql - u root -p;
$password = "";
$dbname = "campusTutor";

$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn-> connect_error)
{
  die( "Connection failed: " . 	$conn-> connect_error); // die = print and break
}
else
{
  echo "Connection succesful";  // echo = print
}  

?>