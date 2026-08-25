<?php
// ============================================================
// doctor_dashboard.php - THE DOCTOR'S APPOINTMENT LIST
// Doctor features 1 and 3: view my appointments for a date,
// and mark a visit completed with a note.
// ============================================================
include "auth.php";
require_role("doctor");

// Find the doctors-table row for the logged-in user.
$doctor = current_doctor($conn);

if (!$doctor) {
    // A user with role 'doctor' but no doctors row - the admin has
    // not finished setting them up.
    $pageTitle = "Dashboard";
    include "header.php";
    echo '<div class="card"><p class="empty">Your doctor profile has not been set up yet.
          Please ask the admin to complete it.</p></div>';
    include "footer.php";
    exit();
}

$doctorId = $doctor["doctor_id"];
$message  = "";

// ---- confirm / complete / cancel an appointment ----
if (isset($_POST["action"])) {

    $apptId = intval($_POST["appt_id"]);
    $action = $_POST["action"];

    // Only these three transitions are allowed. Anything else is ignored.
    $allowed = array("confirmed", "completed", "cancelled");

    if (in_array($action, $allowed)) {
        // "AND doctor_id = ?" makes sure a doctor can only change
        // appointments that belong to them.
        $stmt = mysqli_prepare($conn,
            "UPDATE appointments SET status = ? WHERE appt_id = ? AND doctor_id = ?");
        mysqli_stmt_bind_param($stmt, "sii", $action, $apptId, $doctorId);
        mysqli_stmt_execute($stmt);
        $ok = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        $message = $ok ? "Appointment marked as " . $action . "." : "No change was made.";
    }
}

// ---- which date are we looking at? ----
// Default is today. The date box sends a new one through $_GET.
$viewDate = isset($_GET["date"]) && $_GET["date"] != "" ? test_input($_GET["date"]) : date("Y-m-d");

// Today's counts for the summary boxes.
$stmt = mysqli_prepare($conn,
    "SELECT status, COUNT(*) AS total FROM appointments
     WHERE doctor_id = ? AND appt_date = ? GROUP BY status");
mysqli_stmt_bind_param($stmt, "is", $doctorId, $viewDate);
mysqli_stmt_execute($stmt);
$r = mysqli_stmt_get_result($stmt);
$counts = array("pending" => 0, "confirmed" => 0, "completed" => 0, "cancelled" => 0);
while ($row = mysqli_fetch_assoc($r)) {
    $counts[$row["status"]] = $row["total"];
}
mysqli_stmt_close($stmt);

// The appointment list for that date, with the patient's details.
$stmt = mysqli_prepare($conn,
    "SELECT a.appt_id, a.time_slot, a.status,
            u.full_name AS patient_name, u.phone, u.email
     FROM appointments a
     JOIN users u ON a.patient_id = u.user_id
     WHERE a.doctor_id = ? AND a.appt_date = ?
     ORDER BY a.time_slot");
mysqli_stmt_bind_param($stmt, "is", $doctorId, $viewDate);
mysqli_stmt_execute($stmt);
$appointments = mysqli_stmt_get_result($stmt);

$pageTitle = "My Appointments";
include "header.php";
?>

<div class="page-head">
    <h1>Welcome, <?php echo $_SESSION["full_name"]; ?></h1>
    <p>Your appointment list. Confirm bookings, then mark each visit completed.</p>
</div>

<?php if ($message != ""): ?>
    <div class="alert-ok"><?php echo $message; ?></div>
<?php endif; ?>

<div class="stat-row">
    <div class="stat"><span class="stat-num"><?php echo $counts["pending"]; ?></span><span class="stat-label">Pending</span><span class="stat-sub">need confirming</span></div>
    <div class="stat"><span class="stat-num"><?php echo $counts["confirmed"]; ?></span><span class="stat-label">Confirmed</span><span class="stat-sub">expected today</span></div>
    <div class="stat"><span class="stat-num"><?php echo $counts["completed"]; ?></span><span class="stat-label">Completed</span><span class="stat-sub">seen</span></div>
    <div class="stat"><span class="stat-num"><?php echo $counts["cancelled"]; ?></span><span class="stat-label">Cancelled</span><span class="stat-sub">not coming</span></div>
</div>

<div class="card">
    <h2>Appointments for <?php echo date("d M Y", strtotime($viewDate)); ?></h2>

    <form method="get" action="doctor_dashboard.php" class="search-form">
        <input type="date" name="date" value="<?php echo $viewDate; ?>">
        <input type="submit" value="Show this date" class="btn">
        <a href="doctor_dashboard.php" class="btn btn-ghost">Today</a>
    </form>

    <?php if (mysqli_num_rows($appointments) == 0): ?>
        <p class="empty">No appointments on this date.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <tr>
                <th>Time</th><th>Patient</th><th>Phone</th>
                <th>Status</th><th>Action</th>
            </tr>
            <?php while ($a = mysqli_fetch_assoc($appointments)): ?>
                <tr>
                    <td><?php echo $a["time_slot"]; ?></td>
                    <td>
                        <?php echo $a["patient_name"]; ?><br>
                        <span class="muted-inline"><?php echo $a["email"]; ?></span>
                    </td>
                    <td><?php echo $a["phone"]; ?></td>
                    <td><span class="status status-<?php echo $a["status"]; ?>"><?php echo $a["status"]; ?></span></td>
                    <td>
                        <?php if ($a["status"] == "pending"): ?>
                            <form method="post" action="doctor_dashboard.php?date=<?php echo $viewDate; ?>" class="inline-form">
                                <input type="hidden" name="appt_id" value="<?php echo $a["appt_id"]; ?>">
                                <input type="hidden" name="action" value="confirmed">
                                <input type="submit" value="Confirm" class="btn btn-small">
                            </form>
                            <form method="post" action="doctor_dashboard.php?date=<?php echo $viewDate; ?>" class="inline-form">
                                <input type="hidden" name="appt_id" value="<?php echo $a["appt_id"]; ?>">
                                <input type="hidden" name="action" value="cancelled">
                                <input type="submit" value="Reject" class="btn btn-small btn-danger">
                            </form>

                        <?php elseif ($a["status"] == "confirmed"): ?>
                            <form method="post" action="doctor_dashboard.php?date=<?php echo $viewDate; ?>" class="inline-form">
                                <input type="hidden" name="appt_id" value="<?php echo $a["appt_id"]; ?>">
                                <input type="hidden" name="action" value="completed">
                                <input type="submit" value="Mark completed" class="btn btn-small btn-ok">
                            </form>

                        <?php else: ?>
                            <span class="muted-inline">&mdash;</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php
mysqli_stmt_close($stmt);
include "footer.php";
?>
