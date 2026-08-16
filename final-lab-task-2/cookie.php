<?php

if (isset($_COOKIE["remember_user"])) {

    $username = $_COOKIE["remember_user"];

} else {

    $username = "No cookie found";

}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Cookie Demo</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>Cookie Information</h2>

    <div class="info">

        <p>
            Remembered Username:
        </p>

        <strong>
            <?php echo $username; ?>
        </strong>

    </div>

    <p>
        This value is retrieved from the browser cookie.
    </p>

    <a href="dashboard.php">Back to Dashboard</a>

</div>

</body>
</html>
