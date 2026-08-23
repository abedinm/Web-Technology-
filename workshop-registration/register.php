<?php

// This file receives the registration form data, validates it,
// stores it in the database and replies in JSON format

session_start();

include "db.php";

header("Content-Type: application/json");

$student_id = trim($_POST["student_id"]);
$name = trim($_POST["name"]);
$email = trim($_POST["email"]);
$department = $_POST["department"];
$workshop_id = $_POST["workshop_id"];

// Validation
if ($student_id == "" || $name == "" || $email == "" || $workshop_id == "") {

    echo json_encode(array(
        "status" => "error",
        "message" => "All fields are required."
    ));

    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    echo json_encode(array(
        "status" => "error",
        "message" => "Please enter a valid email address."
    ));

    exit();
}

// Check whether this student has already registered
$check = mysqli_prepare($conn, "SELECT id FROM registrations WHERE student_id = ?");
mysqli_stmt_bind_param($check, "s", $student_id);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);

if (mysqli_stmt_num_rows($check) > 0) {

    echo json_encode(array(
        "status" => "error",
        "message" => "This Student ID has already registered for a workshop."
    ));

    exit();
}

mysqli_stmt_close($check);

// Read the selected workshop
$find = mysqli_prepare($conn, "SELECT title, instructor, schedule, seats FROM workshops WHERE id = ?");
mysqli_stmt_bind_param($find, "i", $workshop_id);
mysqli_stmt_execute($find);
$result = mysqli_stmt_get_result($find);
$workshop = mysqli_fetch_assoc($result);

if (!$workshop) {

    echo json_encode(array(
        "status" => "error",
        "message" => "The selected workshop was not found."
    ));

    exit();
}

if ($workshop["seats"] <= 0) {

    echo json_encode(array(
        "status" => "error",
        "message" => "No seats are left in this workshop."
    ));

    exit();
}

mysqli_stmt_close($find);

// Insert the registration
$insert = mysqli_prepare($conn, "INSERT INTO registrations (student_id, name, email, department, workshop_id, registered_at) VALUES (?, ?, ?, ?, ?, NOW())");
mysqli_stmt_bind_param($insert, "ssssi", $student_id, $name, $email, $department, $workshop_id);

if (!mysqli_stmt_execute($insert)) {

    echo json_encode(array(
        "status" => "error",
        "message" => "Registration failed. Please try again."
    ));

    exit();
}

mysqli_stmt_close($insert);

// Reduce one seat from the workshop
$update = mysqli_prepare($conn, "UPDATE workshops SET seats = seats - 1 WHERE id = ?");
mysqli_stmt_bind_param($update, "i", $workshop_id);
mysqli_stmt_execute($update);
mysqli_stmt_close($update);

// Keep the student state in the session
$_SESSION["student_id"] = $student_id;
$_SESSION["name"] = $name;

// Remember the Student ID in a cookie for 30 days
setcookie(
    "remember_student",
    $student_id,
    time() + (86400 * 30),
    "/"
);

echo json_encode(array(
    "status" => "success",
    "message" => "Registration successful for " . $workshop["title"] . "."
));

mysqli_close($conn);

?>
