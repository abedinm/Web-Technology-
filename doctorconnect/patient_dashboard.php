<?php
// ============================================================
// patient_dashboard.php - PATIENT HOME
//
// Follows the dashboard layout from the Figma design: a greeting,
// three summary figures, the one appointment that comes next, and
// a short history table underneath.
// ============================================================
include "auth.php";
require_role("patient");

$patientId = $_SESSION["user_id"];

// ---------- summary figures ----------
// One grouped query answers "how many of each status", which is
// cheaper than running four separate COUNT queries.
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

// "Upcoming" means still to happen: booked but not finished.
$upcomingCount = $counts["pending"] + $counts["confirmed"];

// How many departments the hospital offers. No user input, so no
// parameters are needed here.
$deptResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM departments");
$deptRow    = mysqli_fetch_assoc($deptResult);
$deptCount  = $deptRow["total"];

// ---------- the next appointment ----------
// Only appointments from today onwards can be "next", so past dates
// are filtered out. LIMIT 1 takes the soonest one.
$stmt = mysqli_prepare($conn,
    "SELECT a.appt_id, a.appt_date, a.time_slot, a.status,
            u.full_name AS doctor_name, dep.dept_name, d.consultation_fee, d.room
     FROM appointments a
     JOIN doctors d       ON a.doctor_id = d.doctor_id
     JOIN users u         ON d.user_id   = u.user_id
     JOIN departments dep ON d.dept_id   = dep.dept_id
     WHERE a.patient_id = ?
       AND a.status IN ('pending','confirmed')
       AND a.appt_date >= CURDATE()
     ORDER BY a.appt_date ASC, a.appt_id ASC
     LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $patientId);
mysqli_stmt_execute($stmt);
$next = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// ---------- recent history ----------
$stmt = mysqli_prepare($conn,
    "SELECT a.appt_id, a.appt_date, a.time_slot, a.status,
            u.full_name AS doctor_name, dep.dept_name
     FROM appointments a
     JOIN doctors d       ON a.doctor_id = d.doctor_id
     JOIN users u         ON d.user_id   = u.user_id
     JOIN departments dep ON d.dept_id   = dep.dept_id
     WHERE a.patient_id = ?
     ORDER BY a.appt_date DESC, a.appt_id DESC
     LIMIT 5");
mysqli_stmt_bind_param($stmt, "i", $patientId);
mysqli_stmt_execute($stmt);
$recent = mysqli_stmt_get_result($stmt);

// Greet by the time of day, and use only the first name.
$hour = (int) date("G");
if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 17) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}

$nameParts = explode(" ", $_SESSION["full_name"]);
$firstName = $nameParts[0];

$pageTitle = "Dashboard";
include "header.php";
?>

<div class="page-head">
    <div>
        <h1><?php echo $greeting; ?>, <?php echo $firstName; ?></h1>
        <p class="muted">Here is what is happening with your appointments today.</p>
    </div>
    <a href="doctors.php" class="btn"><?php echo icon("search"); ?> Find a doctor</a>
</div>

<div class="stat-row">
    <div class="stat">
        <span class="stat-num"><?php echo $upcomingCount; ?></span>
        <span class="stat-label"><?php echo ($upcomingCount == 1) ? "Upcoming appointment" : "Upcoming appointments"; ?></span>
    </div>
    <div class="stat">
        <span class="stat-num"><?php echo $counts["completed"]; ?></span>
        <span class="stat-label">Completed visits</span>
    </div>
    <div class="stat">
        <span class="stat-num"><?php echo $deptCount; ?></span>
        <span class="stat-label">Departments available</span>
    </div>
</div>

<?php if ($next): ?>

    <div class="card">
        <span class="nav-label">Next appointment</span>

        <h2><?php echo $next["doctor_name"]; ?> &mdash; <?php echo $next["dept_name"]; ?></h2>

        <p class="muted">
            <?php echo date("l, d M Y", strtotime($next["appt_date"])); ?>
            &middot; <?php echo $next["time_slot"]; ?>
            <?php if ($next["room"] != ""): ?>
                &middot; Room <?php echo $next["room"]; ?>
            <?php endif; ?>
            &middot; Fee <?php echo number_format($next["consultation_fee"], 0); ?> Tk
            &middot; <span class="status status-<?php echo $next["status"]; ?>"><?php echo $next["status"]; ?></span>
        </p>

        <!-- A div, not a p: a <form> is not allowed inside a paragraph,
             and the browser would close the p early and break the row. -->
        <div class="mt row-actions">
            <a href="my_appointments.php" class="btn btn-small">View details</a>

            <!-- Cancelling changes data, so it is a POST, not a link.
                 my_appointments.php already contains the cancel logic. -->
            <form method="POST" action="my_appointments.php" class="inline-form"
                  onsubmit="return confirm('Cancel this appointment?')">
                <input type="hidden" name="appt_id" value="<?php echo $next["appt_id"]; ?>">
                <input type="submit" name="cancel" value="Cancel" class="btn btn-small btn-ghost">
            </form>
        </div>
    </div>

<?php else: ?>

    <div class="card">
        <span class="nav-label">Next appointment</span>
        <p class="empty">You have no upcoming appointments.
           <a href="doctors.php">Find a doctor</a> to book one.</p>
    </div>

<?php endif; ?>

<div class="card">
    <span class="nav-label">Recent appointments</span>

    <?php if (mysqli_num_rows($recent) == 0): ?>

        <p class="empty">Nothing booked yet. Your history will appear here.</p>

    <?php else: ?>

        <div class="table-wrap">
        <table>
            <tr>
                <th>Doctor</th><th>Department</th><th>Date &amp; time</th><th>Status</th>
            </tr>
            <?php while ($a = mysqli_fetch_assoc($recent)): ?>
            <tr>
                <td><?php echo $a["doctor_name"]; ?></td>
                <td><span class="pill"><?php echo $a["dept_name"]; ?></span></td>
                <td>
                    <?php echo date("d M Y", strtotime($a["appt_date"])); ?>,
                    <?php echo $a["time_slot"]; ?>
                </td>
                <td><span class="status status-<?php echo $a["status"]; ?>"><?php echo $a["status"]; ?></span></td>
            </tr>
            <?php endwhile; ?>
        </table>
        </div>

        <p class="mt"><a href="my_appointments.php" class="btn btn-small btn-ghost">See all my appointments</a></p>

    <?php endif; ?>
</div>

<?php
mysqli_stmt_close($stmt);
include "footer.php";
?>
