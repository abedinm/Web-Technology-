<?php

session_start();

// Read the remembered Student ID from the cookie
if (isset($_COOKIE["remember_student"])) {

    $remembered = $_COOKIE["remember_student"];

} else {

    $remembered = "";

}

if (isset($_POST["next"])) {

    // Store student information in session
    $_SESSION["student_id"] = $_POST["student_id"];
    $_SESSION["name"] = $_POST["name"];
    $_SESSION["email"] = $_POST["email"];
    $_SESSION["department"] = $_POST["department"];

    // Create cookie if Remember Student ID is checked
    if (isset($_POST["remember"])) {
        setcookie(
            "remember_student",
            $_POST["student_id"],
            time() + (86400 * 30),
            "/"
        );
    }

    header("Location: academic.php");
    exit();
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>University Portal Registration</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>Student Information</h2>

    <form method="POST">

        <label>Student ID</label>

        <input
            type="text"
            name="student_id"
            value="<?php echo $remembered; ?>"
            required
        >

        <label>Full Name</label>

        <input
            type="text"
            name="name"
            required
        >

        <label>Email</label>

        <input
            type="text"
            name="email"
            required
        >

        <label>Department</label>

        <select name="department">
            <option value="CSE">CSE</option>
            <option value="EEE">EEE</option>
            <option value="SE">SE</option>
            <option value="IT">IT</option>
        </select>

        <label>
            <input
                type="checkbox"
                name="remember"
            >
            Remember Student ID
        </label>

        <button type="submit" name="next">
            Next
        </button>

    </form>

</div>

</body>
</html>
