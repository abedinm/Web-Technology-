<?php
// ============================================================
// my_appointments.php - VIEW AND CANCEL  (Patient feature 3)
//
// Lists every appointment this patient has ever booked, and lets
// them cancel one that has not happened yet.
// ============================================================
include "auth.php";
require_role("patient");

$patientId = $_SESSION["user_id"];
$message = "";

// ---- cancel an appointment ----
if (isset($_POST["cancel"])) {

    $apptId = intval($_POST["appt_id"]);

    // "AND patient_id = ?" is the important half: without it, a
    // patient could cancel somebody else's appointment by sending
    // a different id. The row must be theirs AND still cancellable.
    $stmt = mysqli_prepare($conn,
        "UPDATE appointments SET status = 'cancelled'
         WHERE appt_id = ? AND patient_id = ? AND status IN ('pending','confirmed')");
    mysqli_stmt_bind_param($stmt, "ii", $apptId, $patientId);
    mysqli_stmt_execute($stmt);

    // affected_rows tells us whether the UPDATE actually changed a row.
    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $message = "Appointment cancelled.";
    } else {
        $message = "That appointment could not be cancelled.";
    }
    mysqli_stmt_close($stmt);
}

if (isset($_GET["booked"])) {
    $message = "Your appointment has been booked. The doctor will confirm it.";
}

// ---- all appointments of this patient ----
$stmt = mysqli_prepare($conn,
    "SELECT a.appt_id, a.appt_date, a.time_slot, a.status,
            u.full_name AS doctor_name, dep.dept_name, d.consultation_fee
     FROM appointments a
     JOIN doctors d       ON a.doctor_id = d.doctor_id
     JOIN users u         ON d.user_id = u.user_id
     JOIN departments dep ON d.dept_id = dep.dept_id
     WHERE a.patient_id = ?
     ORDER BY a.appt_date DESC, a.appt_id DESC");
mysqli_stmt_bind_param($stmt, "i", $patientId);
mysqli_stmt_execute($stmt);
$appointments = mysqli_stmt_get_result($stmt);

$pageTitle = "My Appointments";
include "header.php";
?>

<div class="page-head">
    <h1>My appointments</h1>
    <p>Every booking you have made, newest first.</p>
</div>

<?php if ($message != ""): ?>
    <div class="alert-ok"><?php echo $message; ?></div>
<?php endif; ?>

<div class="card">
    <?php if (mysqli_num_rows($appointments) == 0): ?>
        <p class="empty">You have not booked any appointments yet.
           <a href="doctors.php">Find a doctor</a>.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table>
            <tr>
                <th>Doctor</th><th>Department</th><th>Date</th>
                <th>Time</th><th>Fee</th><th>Status</th><th>Action</th>
            </tr>
            <?php while ($a = mysqli_fetch_assoc($appointments)): ?>
                <tr>
                    <td><?php echo $a["doctor_name"]; ?></td>
                    <td><span class="pill"><?php echo $a["dept_name"]; ?></span></td>
                    <td><?php echo date("d M Y", strtotime($a["appt_date"])); ?></td>
                    <td><?php echo $a["time_slot"]; ?></td>
                    <td><?php echo $a["consultation_fee"]; ?> Tk</td>
                    <td><span class="status status-<?php echo $a["status"]; ?>"><?php echo $a["status"]; ?></span></td>
                    <td>
                        <?php if ($a["status"] == "pending" || $a["status"] == "confirmed"): ?>
                            <!-- A tiny form per row: POST is correct here because
                                 cancelling CHANGES data. A link (GET) should not. -->
                            <form method="post" action="my_appointments.php" class="inline-form"
                                  onsubmit="return confirm('Cancel this appointment?')">
                                <input type="hidden" name="appt_id" value="<?php echo $a["appt_id"]; ?>">
                                <input type="submit" name="cancel" value="Cancel" class="btn btn-small btn-danger">
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
