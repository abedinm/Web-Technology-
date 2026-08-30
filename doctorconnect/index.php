<?php

include "auth.php";

if (is_logged_in()) {
    header("Location: " . dashboard_for($_SESSION["role"]));
    exit();
}

$r = mysqli_query($conn, "SELECT COUNT(*) AS total FROM doctors");
$doctorCount = mysqli_fetch_assoc($r)["total"];

$r = mysqli_query($conn, "SELECT COUNT(*) AS total FROM departments");
$deptCount = mysqli_fetch_assoc($r)["total"];

$departments = mysqli_query($conn,
    "SELECT dep.dept_name, COUNT(d.doctor_id) AS doctor_count
     FROM departments dep
     LEFT JOIN doctors d ON dep.dept_id = d.dept_id
     GROUP BY dep.dept_id, dep.dept_name
     ORDER BY dep.dept_name");

$pageTitle = "Welcome";
include "header.php";
?>

<div class="hero">
    <h1>Book a doctor without standing in a queue</h1>
    <p>
        Search our specialists by department, see their fee and visiting hours,
        and reserve a time slot online. No phone calls, no serial numbers.
    </p>
    <div class="hero-actions">
        <a href="login.php" class="btn btn-big">Sign in</a>
        <a href="register.php" class="btn btn-big btn-ghost">Create an account</a>
    </div>
</div>

<div class="stat-row">
    <div class="stat"><span class="stat-num"><?php echo $doctorCount; ?></span><span class="stat-label">Doctors</span><span class="stat-sub">accepting bookings</span></div>
    <div class="stat"><span class="stat-num"><?php echo $deptCount; ?></span><span class="stat-label">Departments</span><span class="stat-sub">to choose from</span></div>
    <div class="stat"><span class="stat-num">3</span><span class="stat-label">User types</span><span class="stat-sub">patient, doctor, admin</span></div>
    <div class="stat"><span class="stat-num">24/7</span><span class="stat-label">Booking</span><span class="stat-sub">any time of day</span></div>
</div>

<div class="card">
    <h2>Our departments</h2>
    <p class="muted">Sign in to see the doctors in each one.</p>
    <div class="table-wrap">
        <table>
        <tr><th>Department</th><th>Doctors available</th></tr>
        <?php while ($d = mysqli_fetch_assoc($departments)): ?>
            <tr>
                <td><span class="pill"><?php echo $d["dept_name"]; ?></span></td>
                <td><?php echo $d["doctor_count"]; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
        </div>
</div>

<div class="card">
    <h2>How it works</h2>
    <div class="steps">
        <div class="step"><span class="step-n">1</span> Create a patient account</div>
        <div class="step"><span class="step-n">2</span> Search a doctor by department</div>
        <div class="step"><span class="step-n">3</span> Pick a free date and time slot</div>
        <div class="step"><span class="step-n">4</span> The doctor confirms your booking</div>
    </div>
    <p class="muted mt">
        Project page: <a href="db_status.php">database connection status</a>
    </p>
</div>

<?php include "footer.php"; ?>
