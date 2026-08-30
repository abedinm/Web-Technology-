<?php

include_once "icons.php";

$pageTitle = isset($pageTitle) ? $pageTitle : "DoctorConnect";
$role = isset($_SESSION["role"]) ? $_SESSION["role"] : "";
$here = basename($_SERVER["PHP_SELF"]);

$initials = "";
if (isset($_SESSION["full_name"])) {
    $bits = explode(" ", str_replace("Dr. ", "", $_SESSION["full_name"]));
    $initials = strtoupper(substr($bits[0], 0, 1));
    if (count($bits) > 1) {
        $initials .= strtoupper(substr($bits[count($bits) - 1], 0, 1));
    }
}

function navlink($file, $label, $iconName, $here) {
    $on = ($here == $file) ? " class=\"on\"" : "";
    echo '<a href="' . $file . '"' . $on . '>' . icon($iconName) . '<span>' . $label . '</span></a>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $pageTitle; ?> &mdash; DoctorConnect</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php if (is_logged_in()): ?>

<div class="shell">
    <nav class="sidebar">
        <a href="<?php echo dashboard_for($role); ?>" class="brand">
            <?php echo logo_mark(30); ?>
            <span class="logo">DoctorConnect</span>
        </a>

        <div class="nav-label"><?php
            if ($role == "admin") {
                echo "Administration";
            } elseif ($role == "receptionist") {
                echo "Front desk";
            } else {
                echo "Menu";
            }
        ?></div>
        <div class="navlinks">
            <?php
            if ($role == "patient") {
                navlink("patient_dashboard.php", "Dashboard",       "grid",     $here);
                navlink("doctors.php",           "Find Doctors",    "search",   $here);
                navlink("my_appointments.php",   "My Appointments", "calendar", $here);
            } elseif ($role == "doctor") {
                navlink("doctor_dashboard.php",  "Appointments",    "calendar",     $here);
                navlink("doctor_profile.php",    "My Profile",      "stethoscope",  $here);
            } elseif ($role == "receptionist") {
                navlink("reception_dashboard.php", "Front Desk",    "clock",    $here);
                navlink("walkin.php",              "Book Walk-in",  "plus",     $here);
                navlink("doctors.php",             "Doctors",       "search",   $here);
            } elseif ($role == "admin") {
                navlink("admin_dashboard.php",     "Overview",    "grid",     $here);
                navlink("manage_doctors.php",      "Doctors",     "users",    $here);
                navlink("manage_departments.php",  "Departments", "layers",   $here);
            }
            ?>
        </div>

        <div class="nav-label">Account</div>
        <div class="navlinks">
            <?php navlink("profile.php", "My Account", "user", $here); ?>
        </div>

        <div class="side-foot">
            <div class="whoami">
                <span class="avatar"><?php echo $initials; ?></span>
                <span>
                    <span class="whoami-name"><?php echo $_SESSION["full_name"]; ?></span>
                    <span class="role role-<?php echo $role; ?>"><?php echo $role; ?></span>
                </span>
            </div>
            <a href="logout.php" class="btn-logout"><?php echo icon("logout"); ?> <span>Log out</span></a>
        </div>
    </nav>

    <main class="main">
        <div class="container">

<?php elseif (!empty($authSplit)): ?>

<?php else: ?>

<div class="plain-top">
    <div class="inner">
        <?php echo logo_mark(28); ?>
        <span class="logo">DoctorConnect</span>
        <span class="tagline">Online Doctor Appointment Management System</span>
    </div>
</div>
<div class="plain-wrap">

<?php endif; ?>
