<?php
// ============================================================
// db.php - DATABASE CONNECTION
// DoctorConnect - Online Doctor Appointment Management System
// CSC 3215 Web Technologies | Group 6, Section F
//
// This file is included at the top of every other page.
// It does four jobs, in order:
//   1. Connect to the MySQL server
//   2. Create the database if it does not exist
//   3. Create the four tables if they do not exist
//   4. Insert sample data, but ONLY the first time
//
// Because of the IF NOT EXISTS checks and the count guard in
// step 4, this file is safe to run again and again. Loading a
// page fifty times does not create fifty databases or fifty
// copies of the sample rows.
// ============================================================


// ---------- STEP 1: connect to the MySQL server ----------
// Three arguments: the server address, the username, the password.
// On XAMPP the default user is "root" with an empty password.
// No database name yet - we may still need to create it.

// "127.0.0.1" forces a TCP connection on port 3306. "localhost" makes
// PHP use a unix socket file instead, and on macOS that file is in a
// different place for XAMPP than for a Homebrew MySQL, which gives a
// "No such file or directory" error. TCP works with either server.
$host     = "127.0.0.1";
$user     = "root";
$password = "";
$dbName   = "doctorconnect_db";

$conn = mysqli_connect($host, $user, $password);

// If the connection object is false, MySQL is not running or the
// login is wrong. die() stops the script immediately, so no page
// ever runs with a broken database behind it.
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


// ---------- STEP 2: create the database ----------
// IF NOT EXISTS means: make it the first time, do nothing after.

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $dbName");

// Now tell the connection which database to use for every query
// that follows.
mysqli_select_db($conn, $dbName);

// Tell MySQL that everything we send and receive is utf8mb4.
// Without this the connection falls back to Latin-1 and any name
// with a Bangla letter or an accent is stored as broken characters.
mysqli_set_charset($conn, "utf8mb4");


// ---------- STEP 3: create the four tables ----------
// These four tables are the ER diagram from our proposal.
// PRIMARY KEY      = the unique id of a row
// AUTO_INCREMENT   = MySQL assigns the next number itself
// FOREIGN KEY      = this column must match an id in another table
// UNIQUE           = no two rows may share this value
// ENUM             = the value must be one from a fixed list


// TABLE 1: users - every person who can log in.
// One table holds all three roles; the "role" column separates them.
$usersTable = "CREATE TABLE IF NOT EXISTS users (
    user_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name  VARCHAR(60)  NOT NULL,
    email      VARCHAR(80)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    phone      VARCHAR(20),
    role       ENUM('patient','doctor','receptionist','admin') NOT NULL DEFAULT 'patient',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $usersTable);


// TABLE 2: departments - Cardiology, Medicine and so on.
$departmentsTable = "CREATE TABLE IF NOT EXISTS departments (
    dept_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dept_name VARCHAR(50) NOT NULL UNIQUE
)";
mysqli_query($conn, $departmentsTable);


// TABLE 3: doctors - the extra details a doctor has that a
// patient does not. It links to users (who the doctor is) and
// to departments (where the doctor works).
$doctorsTable = "CREATE TABLE IF NOT EXISTS doctors (
    doctor_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL,
    dept_id          INT UNSIGNED NOT NULL,
    specialization   VARCHAR(80),
    consultation_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    available_time   VARCHAR(60),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (dept_id) REFERENCES departments(dept_id)
)";
mysqli_query($conn, $doctorsTable);


// TABLE 4: appointments - the booking itself. It joins a patient
// (from users) to a doctor (from doctors) on a date and time.
$appointmentsTable = "CREATE TABLE IF NOT EXISTS appointments (
    appt_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id  INT UNSIGNED NOT NULL,
    appt_date  DATE NOT NULL,
    time_slot  VARCHAR(30) NOT NULL,
    status     ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id),
    FOREIGN KEY (doctor_id)  REFERENCES doctors(doctor_id)
)";
mysqli_query($conn, $appointmentsTable);


// ---------- STEP 3b: widen the role column ----------
// Databases created before the receptionist role existed still have
// the old three-value ENUM. information_schema reports the current
// definition; if 'receptionist' is missing we widen the column. On a
// database that already has it nothing changes, so this is safe to
// run on every page load, exactly like the CREATE TABLE IF NOT EXISTS
// statements above.
$roleCol = mysqli_query($conn,
    "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME   = 'users'
       AND COLUMN_NAME  = 'role'");

if ($roleCol) {
    $roleDef = mysqli_fetch_assoc($roleCol);
    if ($roleDef && strpos($roleDef["COLUMN_TYPE"], "receptionist") === false) {
        mysqli_query($conn, "ALTER TABLE users MODIFY role
            ENUM('patient','doctor','receptionist','admin')
            NOT NULL DEFAULT 'patient'");
    }
}


// ---------- indexes ----------
// Two queries run constantly: the doctor's list for one day, and the
// check that a slot is not already taken. Both search appointments by
// doctor and date, so an index on that pair lets MySQL jump straight
// to the matching rows instead of reading the whole table.
// IF NOT EXISTS keeps this safe to run on every page load.
// MariaDB, which XAMPP ships, understands CREATE INDEX IF NOT EXISTS.
// Plain MySQL does not, so we ask information_schema whether the index
// is already there and only create it when it is missing. The table and
// index names below are fixed text in this file, never user input.
function ensure_index($conn, $table, $indexName, $columns) {

    $stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) AS total FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = ?
           AND INDEX_NAME   = ?");
    mysqli_stmt_bind_param($stmt, "ss", $table, $indexName);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($row && $row["total"] == 0) {
        mysqli_query($conn, "CREATE INDEX $indexName ON $table ($columns)");
    }
}

ensure_index($conn, "appointments", "idx_doctor_date", "doctor_id, appt_date");

// The patient's own appointment list is filtered by patient_id.
ensure_index($conn, "appointments", "idx_patient", "patient_id");


// ---------- STEP 4: sample data, first run only ----------
// Count the rows already in users. If the count is 0 the database
// is brand new, so we insert the sample data. On every later page
// load the count is not 0 and this whole block is skipped.
//
// Without this guard the INSERTs would run on every single page
// load, and because the email column is UNIQUE the second load
// would throw a duplicate-entry error and stop the page.

$check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$row   = mysqli_fetch_assoc($check);

if ($row["total"] == 0) {

    // Departments first, because doctors point at them.
    mysqli_query($conn, "INSERT INTO departments (dept_id, dept_name) VALUES
        (1, 'Cardiology'),
        (2, 'Medicine'),
        (3, 'Orthopedics'),
        (4, 'Gynecology'),
        (5, 'ENT')");

    // password_hash() scrambles the password before storing it, so
    // even someone reading the database cannot see the real password.
    // Every sample account below uses the password: 1234
    $hash = password_hash("1234", PASSWORD_DEFAULT);

    // Users: 1 admin, 3 doctors, 2 patients.
    $insertUsers = "INSERT INTO users (user_id, full_name, email, password, phone, role) VALUES
        (1, 'System Admin',    'admin@doctorconnect.com', '$hash', '01700000000', 'admin'),
        (2, 'Dr. Salma Akter', 'salma@doctorconnect.com', '$hash', '01711111111', 'doctor'),
        (3, 'Dr. Rahim Uddin', 'rahim@doctorconnect.com', '$hash', '01822222222', 'doctor'),
        (4, 'Dr. Tanvir Hasan','tanvir@doctorconnect.com','$hash', '01933333333', 'doctor'),
        (5, 'Nusrat Jahan',    'nusrat@gmail.com',        '$hash', '01644444444', 'patient'),
        (6, 'Hasan Mahmud',    'hasan@gmail.com',         '$hash', '01555555555', 'patient')";
    mysqli_query($conn, $insertUsers);

    // Doctors: user_id 2, 3 and 4 from the table above.
    mysqli_query($conn, "INSERT INTO doctors
        (doctor_id, user_id, dept_id, specialization, consultation_fee, available_time) VALUES
        (1, 2, 1, 'Heart Specialist, MBBS, MD',      800.00, 'Sun-Thu, 5 PM - 8 PM'),
        (2, 3, 1, 'Cardiology, MBBS',                600.00, 'Sat-Wed, 6 PM - 9 PM'),
        (3, 4, 3, 'Orthopedic Surgeon, MBBS, FCPS', 1000.00, 'Fri, 4 PM - 7 PM')");

    // A couple of example bookings so the tables are not empty.
    mysqli_query($conn, "INSERT INTO appointments
        (patient_id, doctor_id, appt_date, time_slot, status) VALUES
        (5, 1, '2026-08-25', '5:00 PM - 5:30 PM', 'confirmed'),
        (6, 3, '2026-08-26', '4:30 PM - 5:00 PM', 'pending')");
}

// ---------- STEP 5: the front desk account ----------
// Kept outside the first-run block above so that a database which was
// created before this role existed also gets its receptionist. The
// count is 0 only once; afterwards this block does nothing.
$recCheck = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'receptionist'");
$recRow   = mysqli_fetch_assoc($recCheck);

if ($recRow && $recRow["total"] == 0) {

    $recHash  = password_hash("1234", PASSWORD_DEFAULT);
    $recName  = "Front Desk";
    $recEmail = "reception@doctorconnect.com";
    $recPhone = "01766666666";

    $stmt = mysqli_prepare($conn,
        "INSERT INTO users (full_name, email, password, phone, role)
         VALUES (?, ?, ?, ?, 'receptionist')");
    mysqli_stmt_bind_param($stmt, "ssss", $recName, $recEmail, $recHash, $recPhone);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}


// NOTE: there is deliberately no echo in this file. db.php is
// included at the top of every page, so anything printed here
// would appear above the <!DOCTYPE html> line and would break
// session_start() and header() on the pages that use them.
