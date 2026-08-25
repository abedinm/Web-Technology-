<?php

session_start();

// Read the remembered Student ID from the cookie
if (isset($_COOKIE["remember_student"])) {

    $remembered = $_COOKIE["remember_student"];

} else {

    $remembered = "";

}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Workshop Registration</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>Workshop Registration</h2>

    <div class="info">

        <p>Available Workshops</p>

        <!-- The workshop list is loaded here by AJAX -->
        <div id="workshop_list">Loading workshops...</div>

    </div>

    <!-- Success or error message is shown here -->
    <div id="message"></div>

    <form id="reg_form">

        <label>Student ID</label>

        <input
            type="text"
            name="student_id"
            id="student_id"
            value="<?php echo htmlspecialchars($remembered); ?>"
            required
        >

        <label>Full Name</label>

        <input
            type="text"
            name="name"
            id="name"
            required
        >

        <label>Email</label>

        <input
            type="text"
            name="email"
            id="email"
            required
        >

        <label>Department</label>

        <select name="department" id="department">
            <option value="CSE">CSE</option>
            <option value="EEE">EEE</option>
            <option value="SE">SE</option>
            <option value="IT">IT</option>
        </select>

        <label>Select Workshop</label>

        <select name="workshop_id" id="workshop_id">
            <option value="">Loading...</option>
        </select>

        <button type="submit">
            Register
        </button>

    </form>

    <a href="my_workshop.php">View My Workshop</a>

</div>

<script src="script.js"></script>

</body>
</html>
