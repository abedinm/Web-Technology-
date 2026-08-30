<?php

session_set_cookie_params(array(
    "httponly" => true,
    "samesite" => "Lax",
    "path"     => "/",
));

session_start();

include_once "db.php";

function is_logged_in() {
    return isset($_SESSION["user_id"]);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

function require_role($role) {
    require_login();
    if ($_SESSION["role"] != $role) {
        header("Location: " . dashboard_for($_SESSION["role"]));
        exit();
    }
}

function require_any_role($roles) {
    require_login();
    if (!in_array($_SESSION["role"], $roles)) {
        header("Location: " . dashboard_for($_SESSION["role"]));
        exit();
    }
}

function dashboard_for($role) {
    if ($role == "admin") {
        return "admin_dashboard.php";
    } elseif ($role == "doctor") {
        return "doctor_dashboard.php";
    } elseif ($role == "receptionist") {
        return "reception_dashboard.php";
    } else {
        return "patient_dashboard.php";
    }
}

function current_doctor($conn) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM doctors WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $doctor = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $doctor;
}

function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
