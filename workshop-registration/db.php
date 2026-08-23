<?php

// Database connection using the procedural style of MySQLi

$host = "127.0.0.1";
$user = "root";
$pass = "";
$name = "workshop_db";

$conn = mysqli_connect($host, $user, $pass, $name);

// Stop the script if the connection failed
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
