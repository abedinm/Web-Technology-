<?php
// ============================================================
// reception_dashboard.php - THE FRONT DESK QUEUE
//
// The receptionist's home page. It lists every appointment for one
// day, in time order, and lets the front desk check a patient in
// when they arrive or mark them as a no-show.
//
// Status meanings used here:
//   pending   - booked online, patient has not arrived yet
//   confirmed - patient has arrived and is checked in
//   completed - the doctor has finished the visit
//   cancelled - cancelled by the patient, or a no-show
// ============================================================
include "auth.php";
require_role("receptionist");

$message = "";
$error   = "";

// ---------- check in / no show ----------
// Both actions only change the status column, so one prepared
// statement handles them. The action name is checked against a
// fixed list first, so nothing from the form reaches the SQL.
if (isset($_POST["action"]) && isset($_POST["appt_id"])) {

    $apptId = (int) $_POST["appt_id"];
    $action = $_POST["action"];

    $allowed = array("check_in" => "confirmed", "no_show" => "cancelled");

    if (!isset($allowed[$action])) {
        $error = "Unknown action.";
    } else {
        $newStatus = $allowed[$action];

        $stmt = mysqli_prepare($conn,
            "UPDATE appointments SET status = ?
             WHERE appt_id = ? AND status = 'pending'");
        mysqli_stmt_bind_param($stmt, "si", $newStatus, $apptId);
        mysqli_stmt_execute($stmt);
        $changed = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($changed > 0) {
            $message = ($action == "check_in")
                ? "Patient checked in."
                : "Marked as no-show.";
        } else {
            $error = "That appointment was already handled.";
        }
    }
}

// ---------- which day are we looking at ----------
$today = date("Y-m-d");
$date  = isset($_GET["date"]) ? test_input($_GET["date"]) : $today;

// A bad date in the URL falls back to today rather than breaking the query.
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
    $date = $today;
}

// ---------- the queue ----------
// Three tables are joined: the booking, the patient who made it and
// the doctor it belongs to (whose name lives in users, not doctors).
// STR_TO_DATE turns the start of the slot text into a real time so
// that 9:00 AM sorts before 10:00 AM instead of after it.
$stmt = mysqli_prepare($conn,
    "SELECT a.appt_id, a.time_slot, a.status,
            p.full_name AS patient_name, p.phone AS patient_phone,
            du.full_name AS doctor_name, dep.dept_name
     FROM appointments a
     JOIN users p        ON a.patient_id = p.user_id
     JOIN doctors d      ON a.doctor_id  = d.doctor_id
     JOIN users du       ON d.user_id    = du.user_id
     JOIN departments dep ON d.dept_id   = dep.dept_id
     WHERE a.appt_date = ?
     ORDER BY STR_TO_DATE(SUBSTRING_INDEX(a.time_slot, ' - ', 1), '%l:%i %p'), a.appt_id");
mysqli_stmt_bind_param($stmt, "s", $date);
mysqli_stmt_execute($stmt);
$queue = mysqli_stmt_get_result($stmt);

$rows = array();
while ($row = mysqli_fetch_assoc($queue)) {
    $rows[] = $row;
}
mysqli_stmt_close($stmt);

// ---------- counters for the summary cards ----------
$total = count($rows);
$checkedIn = 0;
$waiting   = 0;
$noShow    = 0;

foreach ($rows as $r) {
    if ($r["status"] == "confirmed" || $r["status"] == "completed") {
        $checkedIn++;
    } elseif ($r["status"] == "pending") {
        $waiting++;
    } elseif ($r["status"] == "cancelled") {
        $noShow++;
    }
}

$pageTitle = "Front Desk";
include "header.php";
?>

<div class="page-head">
    <div>
        <h1>Front desk</h1>
        <p class="muted">Check in arriving patients and keep the day&rsquo;s queue up to date.</p>
    </div>
    <a href="walkin.php" class="btn"><?php echo icon("plus"); ?> New walk-in</a>
</div>

<?php if ($message != ""): ?>
    <div class="alert-ok"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error != ""): ?>
    <div class="alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="stat-row">
    <div class="stat"><span class="stat-num"><?php echo $total; ?></span><span class="stat-label">Appointments</span></div>
    <div class="stat"><span class="stat-num"><?php echo $checkedIn; ?></span><span class="stat-label">Checked in</span></div>
    <div class="stat"><span class="stat-num"><?php echo $waiting; ?></span><span class="stat-label">Waiting</span></div>
    <div class="stat"><span class="stat-num"><?php echo $noShow; ?></span><span class="stat-label">Cancelled / no-show</span></div>
</div>

<div class="card">
    <form method="GET" class="inline-form">
        <label for="date">Showing the queue for</label>
        <input type="date" id="date" name="date" value="<?php echo $date; ?>">
        <button type="submit" class="btn btn-small btn-ghost">Show</button>
        <?php if ($date != $today): ?>
            <a href="reception_dashboard.php" class="muted-inline">Back to today</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($total == 0): ?>

    <div class="card">
        <p class="empty">No appointments booked for this date.</p>
    </div>

<?php else: ?>

    <div class="table-wrap">
    <table>
        <tr>
            <th>Serial</th>
            <th>Time</th>
            <th>Patient</th>
            <th>Phone</th>
            <th>Doctor</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php $serial = 0; foreach ($rows as $r): $serial++; ?>
        <tr>
            <td><strong>#<?php echo str_pad($serial, 2, "0", STR_PAD_LEFT); ?></strong></td>
            <td><?php echo $r["time_slot"]; ?></td>
            <td><?php echo $r["patient_name"]; ?></td>
            <td><span class="muted-inline"><?php echo $r["patient_phone"]; ?></span></td>
            <td>
                <?php echo $r["doctor_name"]; ?>
                <span class="muted-inline"><?php echo $r["dept_name"]; ?></span>
            </td>
            <td><span class="status status-<?php echo $r["status"]; ?>"><?php echo $r["status"]; ?></span></td>
            <td>
                <?php if ($r["status"] == "pending"): ?>
                    <form method="POST" class="inline-form">
                        <input type="hidden" name="appt_id" value="<?php echo $r["appt_id"]; ?>">
                        <button type="submit" name="action" value="check_in" class="btn btn-small btn-ok">Check in</button>
                        <button type="submit" name="action" value="no_show" class="btn btn-small btn-danger">No show</button>
                    </form>
                <?php else: ?>
                    <span class="muted">&mdash;</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>

<?php endif; ?>

<?php include "footer.php"; ?>
