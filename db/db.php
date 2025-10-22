<?php
$servername = "sql213.infinityfree.com";  // MySQL Host Name
$username   = "if0_40222907";             // MySQL User Name
$password   = "oQtunn3UsF4";              // MySQL Password
$dbname     = "if0_40222907_hr_db";       // MySQL Database Name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
