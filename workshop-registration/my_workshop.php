<?php

session_start();

// Only a registered student can open this page
if (!isset($_SESSION["student_id"])) {
    header("Location: index.php");
    exit();
}

include "db.php";

$student_id = $_SESSION["student_id"];

// Read the registration of this student with the workshop details
$sql = "SELECT r.student_id, r.name, r.email, r.department, r.registered_at,
               w.title, w.instructor, w.schedule
        FROM registrations r
        JOIN workshops w ON r.workshop_id = w.id
        WHERE r.student_id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>

<!DOCTYPE html>
<html>
<head>
    <title>My Workshop</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">

    <h2>My Workshop</h2>

    <?php if ($row) { ?>

        <div class="info">

            <p>
                Student ID:
                <strong><?php echo $row["student_id"]; ?></strong>
            </p>

            <p>
                Name:
                <strong><?php echo $row["name"]; ?></strong>
            </p>

            <p>
                Email:
                <strong><?php echo $row["email"]; ?></strong>
            </p>

            <p>
                Department:
                <strong><?php echo $row["department"]; ?></strong>
            </p>

            <p>
                This information is kept in the
                <strong>PHP Session</strong>.
            </p>

        </div>

        <div class="info">

            <p>
                Workshop:
                <strong><?php echo $row["title"]; ?></strong>
            </p>

            <p>
                Instructor:
                <strong><?php echo $row["instructor"]; ?></strong>
            </p>

            <p>
                Schedule:
                <strong><?php echo $row["schedule"]; ?></strong>
            </p>

            <p>
                Registered On:
                <strong><?php echo $row["registered_at"]; ?></strong>
            </p>

        </div>

    <?php } else { ?>

        <p class="error">No registration was found for this student.</p>

    <?php } ?>

    <a href="index.php">Back to Registration</a>

    <a href="logout.php">Logout</a>

</div>

</body>
</html>
