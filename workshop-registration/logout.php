<?php

session_start();

// Remove all session variables
session_unset();

// Destroy the session
session_destroy();

// The cookie is not deleted, so the Student ID is still remembered
if (isset($_COOKIE["remember_student"])) {

    $remembered = $_COOKIE["remember_student"];

} else {

    $remembered = "No cookie found";

}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Logout</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2 class="success">Logout Successful</h2>

    <div class="info">

        <p>
            The session has been destroyed.
        </p>

        <p>
            Remembered Student ID:
            <strong><?php echo htmlspecialchars($remembered); ?></strong>
        </p>

        <p>
            This value is still stored in the browser
            <strong>Cookie</strong>.
        </p>

    </div>

    <a href="index.php">Go to Registration</a>

</div>

</body>
</html>
