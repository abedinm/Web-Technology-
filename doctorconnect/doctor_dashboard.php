<?php

include "auth.php";
require_role("doctor");

$doctor = current_doctor($conn);

if (!$doctor) {
    $pageTitle = "Appointments";
    include "header.php";
    echo '<div class="alert-error">Your doctor profile has not been set up yet.
          Please ask the admin to add you to a department.</div>';
    include "footer.php";
    exit();
}

$doctorId = $doctor["doctor_id"];

$today = date("Y-m-d");
$date  = isset($_GET["date"]) ? test_input($_GET["date"]) : $today;

if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $date)) {
    $date = $today;
}

$stmt = mysqli_prepare($conn,
    "SELECT a.appt_id, a.time_slot, a.status, a.diagnosis,
            p.full_name AS patient_name, p.phone AS patient_phone
     FROM appointments a
     JOIN users p ON a.patient_id = p.user_id
     WHERE a.doctor_id = ? AND a.appt_date = ?
     ORDER BY STR_TO_DATE(SUBSTRING_INDEX(a.time_slot, ' - ', 1), '%l:%i %p'), a.appt_id");
mysqli_stmt_bind_param($stmt, "is", $doctorId, $date);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = array();
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = $row;
}
mysqli_stmt_close($stmt);

$total = count($rows);
$done  = 0;
$left  = 0;

foreach ($rows as $r) {
    if ($r["status"] == "completed") {
        $done++;
    } elseif ($r["status"] == "pending" || $r["status"] == "confirmed") {
        $left++;
    }
}

$expected = $left * $doctor["consultation_fee"];

$pageTitle = "Appointments";
include "header.php";
?>

<div class="page-head">
    <div>
        <h1>Today&rsquo;s schedule</h1>
        <p class="muted">Appointments booked with you for the selected date.</p>
    </div>
    <a href="doctor_profile.php" class="btn btn-small btn-ghost"><?php echo icon("stethoscope"); ?> My profile</a>
</div>

<div class="stat-row">
    <div class="stat"><span class="stat-num"><?php echo $total; ?></span><span class="stat-label">Appointments</span><span class="stat-sub">on this date</span></div>
    <div class="stat"><span class="stat-num"><?php echo $done; ?></span><span class="stat-label">Completed</span><span class="stat-sub">visit finished</span></div>
    <div class="stat"><span class="stat-num"><?php echo $left; ?></span><span class="stat-label">Still to see</span><span class="stat-sub">pending or confirmed</span></div>
    <div class="stat"><span class="stat-num"><?php echo number_format($expected, 0); ?></span><span class="stat-label">Expected fees</span><span class="stat-sub">Tk, visits not yet done</span></div>
</div>

<div class="card">
    <form method="GET" class="inline-form">
        <label for="date">Showing appointments for</label>
        <input type="date" id="date" name="date" value="<?php echo $date; ?>">
        <button type="submit" class="btn btn-small btn-ghost">Show</button>
        <?php if ($date != $today): ?>
            <a href="doctor_dashboard.php" class="muted-inline">Back to today</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
<?php if ($total == 0): ?>

    <p class="empty">No appointments booked for this date.</p>

<?php else: ?>

    <div class="table-wrap">
    <table>
        <tr>
            <th>Time</th>
            <th>Patient</th>
            <th>Phone</th>
            <th>Diagnosis</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php foreach ($rows as $r): ?>
        <tr>
            <td><strong><?php echo $r["time_slot"]; ?></strong></td>
            <td><?php echo $r["patient_name"]; ?></td>
            <td><span class="muted-inline"><?php echo $r["patient_phone"]; ?></span></td>
            <td>
                <?php if ($r["diagnosis"] != ""): ?>
                    <?php echo $r["diagnosis"]; ?>
                <?php else: ?>
                    <span class="muted-inline">&mdash;</span>
                <?php endif; ?>
            </td>
            <td><span class="status status-<?php echo $r["status"]; ?>"><?php echo $r["status"]; ?></span></td>
            <td>
                <?php if ($r["status"] == "completed"): ?>
                    <a href="visit.php?id=<?php echo $r["appt_id"]; ?>" class="btn btn-small btn-ghost">View note</a>
                <?php elseif ($r["status"] == "cancelled"): ?>
                    <span class="muted-inline">&mdash;</span>
                <?php else: ?>
                    <a href="visit.php?id=<?php echo $r["appt_id"]; ?>" class="btn btn-small">Start visit</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>

<?php endif; ?>
</div>

<?php include "footer.php"; ?>
