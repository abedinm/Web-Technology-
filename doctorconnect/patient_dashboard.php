<?php
// ============================================================
// patient_dashboard.php - PATIENT HOME
// Shows a summary of the patient's own appointments.
// ============================================================
include "auth.php";
require_role("patient");

$patientId = $_SESSION["user_id"];

// Count this patient's appointments by status.
// The ? is filled with the logged-in user's id, so a patient can
// only ever count their own rows.
$stmt = mysqli_prepare($conn,
    "SELECT status, COUNT(*) AS total FROM appointments WHERE patient_id = ? GROUP BY status");
mysqli_stmt_bind_param($stmt, "i", $patientId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$counts = array("pending" => 0, "confirmed" => 0, "completed" => 0, "cancelled" => 0);
while ($row = mysqli_fetch_assoc($result)) {
    $counts[$row["status"]] = $row["total"];
}
mysqli_stmt_close($stmt);

// The next few upcoming appointments, newest date first.
$stmt = mysqli_prepare($conn,
    "SELECT a.appt_id, a.appt_date, a.time_slot, a.status,
            u.full_name AS doctor_name, dep.dept_name
     FROM appointments a
     JOIN doctors d       ON a.doctor_id = d.doctor_id
     JOIN users u         ON d.user_id = u.user_id
     JOIN departments dep ON d.dept_id = dep.dept_id
     WHERE a.patient_id = ? AND a.status IN ('pending','confirmed')
     ORDER BY a.appt_date ASC
     LIMIT 5");
mysqli_stmt_bind_param($stmt, "i", $patientId);
mysqli_stmt_execute($stmt);
$upcoming = mysqli_stmt_get_result($stmt);

$pageTitle = "Dashboard";
include "header.php";
?>

<div class="page-head">
    <h1>Welcome, <?php echo $_SESSION["full_name"]; ?></h1>
    <p>Your appointments at a glance.</p>
</div>

<div class="stat-row">
    <div class="stat"><span class="stat-num"><?php echo $counts["pending"]; ?></span><span class="stat-label">Pending</span><span class="stat-sub">waiting for the doctor</span></div>
    <div class="stat"><span class="stat-num"><?php echo $counts["confirmed"]; ?></span><span class="stat-label">Confirmed</span><span class="stat-sub">go on the day</span></div>
    <div class="stat"><span class="stat-num"><?php echo $counts["completed"]; ?></span><span class="stat-label">Completed</span><span class="stat-sub">visit finished</span></div>
    <div class="stat"><span class="stat-num"><?php echo $counts["cancelled"]; ?></span><span class="stat-label">Cancelled</span><span class="stat-sub">not going</span></div>
</div>

<div class="card">
    <h2>Upcoming appointments</h2>
    <p class="muted">Pending and confirmed bookings, soonest first.</p>

    <?php if (mysqli_num_rows($upcoming) == 0): ?>
        <p class="empty">You have no upcoming appointments.
           <a href="doctors.php">Find a doctor</a> to book one.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <tr>
                <th>Doctor</th><th>Department</th><th>Date</th><th>Time</th><th>Status</th>
            </tr>
            <?php while ($a = mysqli_fetch_assoc($upcoming)): ?>
                <tr>
                    <td><?php echo $a["doctor_name"]; ?></td>
                    <td><span class="pill"><?php echo $a["dept_name"]; ?></span></td>
                    <td><?php echo date("d M Y", strtotime($a["appt_date"])); ?></td>
                    <td><?php echo $a["time_slot"]; ?></td>
                    <td><span class="status status-<?php echo $a["status"]; ?>"><?php echo $a["status"]; ?></span></td>
                </tr>
            <?php endwhile; ?>
        </table>
        </div>
        <p class="mt"><a href="my_appointments.php" class="btn btn-small">See all my appointments</a></p>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Book a new appointment</h2>
    <p class="muted">Search our doctors by name or department, then pick a free time slot.</p>
    <a href="doctors.php" class="btn">Find a doctor</a>
</div>

<?php
mysqli_stmt_close($stmt);
include "footer.php";
?>
