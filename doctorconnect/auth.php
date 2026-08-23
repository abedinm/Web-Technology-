<?php
// ============================================================
// auth.php - SESSIONS AND ACCESS CONTROL
// DoctorConnect | CSC 3215 | Group 6, Section F
//
// Included at the top of every page that needs a logged-in user.
// It starts the session and provides the guard functions that
// keep each user inside their own part of the system.
//
// IMPORTANT: this file must be included BEFORE any HTML is
// printed, because session_start() has to run before any output.
// ============================================================

// Harden the session cookie BEFORE the session starts - these settings
// have no effect if they are applied afterwards.
//   httponly : JavaScript cannot read the cookie, so a cross-site
//              scripting bug cannot steal the session id
//   samesite : the browser does not send the cookie when the request
//              comes from another site, which blocks CSRF
session_set_cookie_params(array(
    "httponly" => true,
    "samesite" => "Lax",
    "path"     => "/",
));

session_start();

include_once "db.php";


// Is anybody logged in right now?
function is_logged_in() {
    return isset($_SESSION["user_id"]);
}


// Stop a page from loading unless the visitor is logged in.
// Called at the top of every private page.
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}


// Stop a page unless the visitor has the right role.
// Example: require_role("admin") on the admin pages, so a patient
// who types the admin URL is sent back to their own dashboard.
function require_role($role) {
    require_login();
    if ($_SESSION["role"] != $role) {
        header("Location: " . dashboard_for($_SESSION["role"]));
        exit();
    }
}


// Each role has its own home page.
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


// The doctors table row belonging to the logged-in doctor.
// A doctor logs in as a user, but their fee and department live
// in the doctors table, so we look that row up by user_id.
function current_doctor($conn) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM doctors WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $doctor = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $doctor;
}


// Clean text coming from a form before showing or storing it.
// trim          - removes spaces at the start and end
// stripslashes  - removes escape backslashes
// htmlspecialchars - turns < and > into harmless text so typed
//                    HTML or JavaScript cannot run on our page
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
