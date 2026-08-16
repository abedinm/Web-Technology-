<?php

session_start();

// Remove all session variables
session_unset();

// Destroy the session
session_destroy();

// The cookie is not removed, so it is still available
if (isset($_COOKIE["remember_student"])) {

    $cookie_id = $_COOKIE["remember_student"];

} else {

    $cookie_id = "No cookie found";

}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Registration Complete</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2 class="success">Registration Complete</h2>

    <div class="info">

        <p>
            The session has been destroyed using
            <strong>session_unset()</strong> and
            <strong>session_destroy()</strong>.
        </p>

        <p>
            Remembered Student ID:
            <strong><?php echo $cookie_id; ?></strong>
        </p>

        <p>
            The cookie is still stored in the browser.
        </p>

    </div>

    <a href="index.php">Go to Registration</a>

</div>

</body>
</html>
