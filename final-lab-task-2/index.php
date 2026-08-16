<?php

session_start();

if (isset($_POST["login"])) {

    $username = $_POST["username"];

    // Store username in session
    $_SESSION["username"] = $username;

    // Create cookie if Remember Me is checked
    if (isset($_POST["remember"])) {
        setcookie(
            "remember_user",
            $username,
            time() + (86400 * 30),
            "/"
        );
    }

    header("Location: dashboard.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Session and Cookie Demo</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>Login</h2>

    <form method="POST">

        <label>Username</label>

        <input
            type="text"
            name="username"
            required
        >

        <label>
            <input
                type="checkbox"
                name="remember"
            >
            Remember Me
        </label>

        <button type="submit" name="login">
            Login
        </button>

    </form>

</div>

</body>
</html>
