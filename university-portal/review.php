<?php

session_start();

if (!isset($_SESSION["semester"])) {
    header("Location: index.php");
    exit();
}

// Values stored in the session
$student_id = $_SESSION["student_id"];
$name = $_SESSION["name"];
$email = $_SESSION["email"];
$department = $_SESSION["department"];
$semester = $_SESSION["semester"];
$course = $_SESSION["course"];
$credit = $_SESSION["credit"];

// Value stored in the cookie
if (isset($_COOKIE["remember_student"])) {

    $cookie_id = $_COOKIE["remember_student"];

} else {

    $cookie_id = "No cookie found";

}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Confirm Registration</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>Confirm Registration</h2>

    <div class="info">

        <p>
            Student ID:
            <strong><?php echo $student_id; ?></strong>
        </p>

        <p>
            Name:
            <strong><?php echo $name; ?></strong>
        </p>

        <p>
            Email:
            <strong><?php echo $email; ?></strong>
        </p>

        <p>
            Department:
            <strong><?php echo $department; ?></strong>
        </p>

        <p>
            Semester:
            <strong><?php echo $semester; ?></strong>
        </p>

        <p>
            Course:
            <strong><?php echo $course; ?></strong>
        </p>

        <p>
            Credit:
            <strong><?php echo $credit; ?></strong>
        </p>

        <p>
            All of this is stored in the
            <strong>PHP Session</strong>.
        </p>

    </div>

    <div class="info">

        <p>
            Remembered Student ID:
            <strong><?php echo $cookie_id; ?></strong>
        </p>

        <p>
            This value is retrieved from the browser
            <strong>Cookie</strong>.
        </p>

    </div>

    <a href="complete.php">Complete Registration</a>

</div>

</body>
</html>
