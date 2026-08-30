<?php

$host     = "127.0.0.1";
$user     = "root";
$password = "";
$dbName   = "doctorconnect_db";

$conn = mysqli_connect($host, $user, $password);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $dbName");

mysqli_select_db($conn, $dbName);

mysqli_set_charset($conn, "utf8mb4");

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

$departmentsTable = "CREATE TABLE IF NOT EXISTS departments (
    dept_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dept_name VARCHAR(50) NOT NULL UNIQUE
)";
mysqli_query($conn, $departmentsTable);

$doctorsTable = "CREATE TABLE IF NOT EXISTS doctors (
    doctor_id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL,
    dept_id          INT UNSIGNED NOT NULL,
    specialization   VARCHAR(80),
    consultation_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    available_time   VARCHAR(60),
    room             VARCHAR(20) DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (dept_id) REFERENCES departments(dept_id)
)";
mysqli_query($conn, $doctorsTable);

$appointmentsTable = "CREATE TABLE IF NOT EXISTS appointments (
    appt_id    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id INT UNSIGNED NOT NULL,
    doctor_id  INT UNSIGNED NOT NULL,
    appt_date  DATE NOT NULL,
    time_slot  VARCHAR(30) NOT NULL,
    status     ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    diagnosis  VARCHAR(120) DEFAULT NULL,
    visit_note TEXT         DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(user_id),
    FOREIGN KEY (doctor_id)  REFERENCES doctors(doctor_id)
)";
mysqli_query($conn, $appointmentsTable);

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

function ensure_column($conn, $table, $column, $definition) {
    $stmt = mysqli_prepare($conn,
        "SELECT COUNT(*) AS total FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = ?
           AND COLUMN_NAME  = ?");
    mysqli_stmt_bind_param($stmt, "ss", $table, $column);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($row && $row["total"] == 0) {
        mysqli_query($conn, "ALTER TABLE $table ADD COLUMN $column $definition");
    }
}

ensure_column($conn, "appointments", "diagnosis",  "VARCHAR(120) DEFAULT NULL");
ensure_column($conn, "appointments", "visit_note", "TEXT DEFAULT NULL");
ensure_column($conn, "doctors",      "room",       "VARCHAR(20) DEFAULT NULL");

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

ensure_index($conn, "appointments", "idx_patient", "patient_id");

$check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$row   = mysqli_fetch_assoc($check);

if ($row["total"] == 0) {
    mysqli_query($conn, "INSERT INTO departments (dept_id, dept_name) VALUES
        (1, 'Cardiology'),
        (2, 'Medicine'),
        (3, 'Orthopedics'),
        (4, 'Gynecology'),
        (5, 'ENT')");

    $hash = password_hash("1234", PASSWORD_DEFAULT);

    $insertUsers = "INSERT INTO users (user_id, full_name, email, password, phone, role) VALUES
        (1, 'System Admin',    'admin@doctorconnect.com', '$hash', '01700000000', 'admin'),
        (2, 'Dr. Salma Akter', 'salma@doctorconnect.com', '$hash', '01711111111', 'doctor'),
        (3, 'Dr. Rahim Uddin', 'rahim@doctorconnect.com', '$hash', '01822222222', 'doctor'),
        (4, 'Dr. Tanvir Hasan','tanvir@doctorconnect.com','$hash', '01933333333', 'doctor'),
        (5, 'Nusrat Jahan',    'nusrat@gmail.com',        '$hash', '01644444444', 'patient'),
        (6, 'Hasan Mahmud',    'hasan@gmail.com',         '$hash', '01555555555', 'patient')";
    mysqli_query($conn, $insertUsers);

    mysqli_query($conn, "INSERT INTO doctors
        (doctor_id, user_id, dept_id, specialization, consultation_fee, available_time, room) VALUES
        (1, 2, 1, 'Heart Specialist, MBBS, MD',      800.00, 'Sun-Thu, 5 PM - 8 PM', '304'),
        (2, 3, 1, 'Cardiology, MBBS',                600.00, 'Sat-Wed, 6 PM - 9 PM', '306'),
        (3, 4, 3, 'Orthopedic Surgeon, MBBS, FCPS', 1000.00, 'Fri, 4 PM - 7 PM',     '210')");

    mysqli_query($conn, "INSERT INTO appointments
        (patient_id, doctor_id, appt_date, time_slot, status) VALUES
        (5, 1, '2026-08-25', '5:00 PM - 5:30 PM', 'confirmed'),
        (6, 3, '2026-08-26', '4:30 PM - 5:00 PM', 'pending')");
}

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

