<?php

session_start();

// Remove all session variables
session_unset();

// Destroy the session
session_destroy();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Logout</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>Logout Successful</h2>

    <p>
        The session has been destroyed.
    </p>

    <a href="index.php">Go to Login</a>

</div>

</body>
</html>
