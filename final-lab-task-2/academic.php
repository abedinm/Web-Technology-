<?php

session_start();

if (!isset($_SESSION["student_id"])) {
    header("Location: index.php");
    exit();
}

// Student information from the previous page
$student_id = $_SESSION["student_id"];
$name = $_SESSION["name"];
$email = $_SESSION["email"];
$department = $_SESSION["department"];

if (isset($_POST["next"])) {

    // Store academic information in session
    $_SESSION["semester"] = $_POST["semester"];
    $_SESSION["course"] = $_POST["course"];
    $_SESSION["credit"] = $_POST["credit"];

    header("Location: review.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Academic Information</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>Academic Information</h2>

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
            This information is stored in the
            <strong>PHP Session</strong>.
        </p>

    </div>

    <form method="POST">

        <label>Semester</label>

        <select name="semester">
            <option value="Spring 2026">Spring 2026</option>
            <option value="Summer 2026">Summer 2026</option>
            <option value="Fall 2026">Fall 2026</option>
        </select>

        <label>Course</label>

        <select name="course">
            <option value="Web Technologies">Web Technologies</option>
            <option value="Database Management">Database Management</option>
            <option value="Software Engineering">Software Engineering</option>
            <option value="Numerical Methods">Numerical Methods</option>
        </select>

        <label>Credit</label>

        <input
            type="text"
            name="credit"
            required
        >

        <button type="submit" name="next">
            Next
        </button>

    </form>

</div>

</body>
</html>
