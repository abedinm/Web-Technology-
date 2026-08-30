<?php

include "auth.php";
require_role("admin");

function count_rows($conn, $sql) {
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return 0;
    }
    $row = mysqli_fetch_assoc($result);
    return $row["total"];
}

$doctorCount  = count_rows($conn, "SELECT COUNT(*) AS total FROM doctors");
$patientCount = count_rows($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 'patient'");
$deptCount    = count_rows($conn, "SELECT COUNT(*) AS total FROM departments");
$apptCount    = count_rows($conn, "SELECT COUNT(*) AS total FROM appointments");

$counts = array("pending" => 0, "confirmed" => 0, "completed" => 0, "cancelled" => 0);
$result = mysqli_query($conn, "SELECT status, COUNT(*) AS total FROM appointments GROUP BY status");
while ($row = mysqli_fetch_assoc($result)) {
    $counts[$row["status"]] = $row["total"];
}

$byDept = mysqli_query($conn,
    "SELECT dep.dept_name,
            COUNT(DISTINCT d.doctor_id) AS doctors,
            COUNT(a.appt_id)            AS appointments
     FROM departments dep
     LEFT JOIN doctors d      ON d.dept_id    = dep.dept_id
     LEFT JOIN appointments a ON a.doctor_id  = d.doctor_id
     GROUP BY dep.dept_id, dep.dept_name
     ORDER BY appointments DESC, dep.dept_name");

$recent = mysqli_query($conn,
    "SELECT a.appt_id, a.appt_date, a.time_slot, a.status,
            p.full_name AS patient_name,
            du.full_name AS doctor_name,
            dep.dept_name
     FROM appointments a
     JOIN users p         ON a.patient_id = p.user_id
     JOIN doctors d       ON a.doctor_id  = d.doctor_id
     JOIN users du        ON d.user_id    = du.user_id
     JOIN departments dep ON d.dept_id    = dep.dept_id
     ORDER BY a.appt_id DESC
     LIMIT 10");

$pageTitle = "Overview";
include "header.php";
?>

<div class="page-head">
    <div>
        <h1>Hospital overview</h1>
        <p class="muted">Everything registered in DoctorConnect at a glance.</p>
    </div>
    <a href="manage_doctors.php" class="btn"><?php echo icon("plus"); ?> Manage doctors</a>
</div>

<div class="stat-row">
    <div class="stat"><span class="stat-num"><?php echo $doctorCount; ?></span><span class="stat-label">Doctors</span></div>
    <div class="stat"><span class="stat-num"><?php echo $patientCount; ?></span><span class="stat-label">Patients</span></div>
    <div class="stat"><span class="stat-num"><?php echo $deptCount; ?></span><span class="stat-label">Departments</span></div>
    <div class="stat"><span class="stat-num"><?php echo $apptCount; ?></span><span class="stat-label">Appointments</span></div>
</div>

<div class="stat-row">
    <div class="stat"><span class="stat-num"><?php echo $counts["pending"]; ?></span><span class="stat-label">Pending</span></div>
    <div class="stat"><span class="stat-num"><?php echo $counts["confirmed"]; ?></span><span class="stat-label">Confirmed</span></div>
    <div class="stat"><span class="stat-num"><?php echo $counts["completed"]; ?></span><span class="stat-label">Completed</span></div>
    <div class="stat"><span class="stat-num"><?php echo $counts["cancelled"]; ?></span><span class="stat-label">Cancelled</span></div>
</div>

<div class="card">
    <h2 class="nav-label">By department</h2>
    <div class="table-wrap">
    <table>
        <tr><th>Department</th><th>Doctors</th><th>Appointments</th></tr>
        <?php while ($d = mysqli_fetch_assoc($byDept)): ?>
        <tr>
            <td><span class="pill"><?php echo $d["dept_name"]; ?></span></td>
            <td><?php echo $d["doctors"]; ?></td>
            <td><?php echo $d["appointments"]; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
    </div>
</div>

<div class="card">
    <h2 class="nav-label">Latest bookings</h2>
    <?php if (mysqli_num_rows($recent) == 0): ?>
        <p class="empty">No appointments have been booked yet.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <tr><th>Patient</th><th>Doctor</th><th>Department</th><th>Date</th><th>Time</th><th>Status</th></tr>
            <?php while ($a = mysqli_fetch_assoc($recent)): ?>
            <tr>
                <td><?php echo $a["patient_name"]; ?></td>
                <td><?php echo $a["doctor_name"]; ?></td>
                <td><span class="pill"><?php echo $a["dept_name"]; ?></span></td>
                <td><?php echo date("d M Y", strtotime($a["appt_date"])); ?></td>
                <td><?php echo $a["time_slot"]; ?></td>
                <td><span class="status status-<?php echo $a["status"]; ?>"><?php echo $a["status"]; ?></span></td>
            </tr>
            <?php endwhile; ?>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php include "footer.php"; ?>
