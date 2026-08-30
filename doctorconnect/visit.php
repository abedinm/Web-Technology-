<?php

include "auth.php";
require_role("doctor");

$doctor = current_doctor($conn);

if (!$doctor) {
    header("Location: doctor_dashboard.php");
    exit();
}

$doctorId = $doctor["doctor_id"];
$apptId   = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

$error   = "";
$message = "";

if (isset($_POST["submit"])) {
    $apptId    = (int) $_POST["appt_id"];
    $diagnosis = test_input($_POST["diagnosis"]);
    $note      = test_input($_POST["visit_note"]);

    if ($diagnosis == "") {
        $error = "Please write at least a short diagnosis.";
    } else {
        $stmt = mysqli_prepare($conn,
            "UPDATE appointments
             SET diagnosis = ?, visit_note = ?, status = 'completed'
             WHERE appt_id = ? AND doctor_id = ? AND status != 'cancelled'");
        mysqli_stmt_bind_param($stmt, "ssii", $diagnosis, $note, $apptId, $doctorId);
        mysqli_stmt_execute($stmt);
        $changed = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($changed > 0) {
            header("Location: doctor_dashboard.php");
            exit();
        } else {
            $error = "That appointment could not be updated.";
        }
    }
}

$stmt = mysqli_prepare($conn,
    "SELECT a.appt_id, a.appt_date, a.time_slot, a.status, a.diagnosis, a.visit_note,
            p.full_name AS patient_name, p.email AS patient_email, p.phone AS patient_phone,
            dep.dept_name, d.consultation_fee
     FROM appointments a
     JOIN users p         ON a.patient_id = p.user_id
     JOIN doctors d       ON a.doctor_id  = d.doctor_id
     JOIN departments dep ON d.dept_id    = dep.dept_id
     WHERE a.appt_id = ? AND a.doctor_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $apptId, $doctorId);
mysqli_stmt_execute($stmt);
$appt = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$appt) {
    $pageTitle = "Visit";
    include "header.php";
    echo '<div class="alert-error">That appointment does not exist, or it does not belong to you.</div>';
    echo '<p><a href="doctor_dashboard.php" class="btn btn-small btn-ghost">Back to my schedule</a></p>';
    include "footer.php";
    exit();
}

$readOnly = ($appt["status"] == "completed");

$pageTitle = "Visit";
include "header.php";
?>

<div class="page-head">
    <div>
        <h1>Visit &mdash; <?php echo $appt["patient_name"]; ?></h1>
        <p class="muted">
            <?php echo date("d M Y", strtotime($appt["appt_date"])); ?>
            &middot; <?php echo $appt["time_slot"]; ?>
            &middot; <?php echo $appt["dept_name"]; ?>
        </p>
    </div>
    <a href="doctor_dashboard.php" class="btn btn-small btn-ghost"><?php echo icon("arrow-left"); ?> Back to schedule</a>
</div>

<?php if ($error != ""): ?>
    <div class="alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($readOnly): ?>
    <div class="alert-ok">This visit is already completed. The note below is a record only.</div>
<?php endif; ?>

<div class="card">
    <h2 class="nav-label">Patient</h2>
    <div class="table-wrap">
    <table>
        <tr><th>Name</th><td><?php echo $appt["patient_name"]; ?></td></tr>
        <tr><th>Phone</th><td><?php echo $appt["patient_phone"]; ?></td></tr>
        <tr><th>Email</th><td><?php echo $appt["patient_email"]; ?></td></tr>
        <tr><th>Consultation fee</th><td><?php echo number_format($appt["consultation_fee"], 0); ?> Tk</td></tr>
        <tr><th>Status</th><td><span class="status status-<?php echo $appt["status"]; ?>"><?php echo $appt["status"]; ?></span></td></tr>
    </table>
    </div>
</div>

<form method="POST" class="card">

    <input type="hidden" name="appt_id" value="<?php echo $appt["appt_id"]; ?>">

    <h2 class="nav-label">Visit record</h2>

    <label for="diagnosis">Diagnosis</label>
    <input type="text" id="diagnosis" name="diagnosis" maxlength="120"
           value="<?php echo $appt["diagnosis"]; ?>"
           placeholder="e.g. Sinus tachycardia, mild"
           <?php echo $readOnly ? "readonly" : ""; ?>>

    <label for="visit_note">Note and advice</label>
    <textarea id="visit_note" name="visit_note" rows="6"
              placeholder="Findings, tests advised, medicines and follow-up date."
              <?php echo $readOnly ? "readonly" : ""; ?>><?php echo $appt["visit_note"]; ?></textarea>

    <?php if (!$readOnly): ?>
        <span class="hint">Saving the note marks this appointment as completed.</span>
        <input type="submit" name="submit" value="Save and mark completed" class="btn btn-block">
    <?php endif; ?>

</form>

<?php include "footer.php"; ?>
