<?php

include "auth.php";
require_role("doctor");

$doctor = current_doctor($conn);

if (!$doctor) {
    $pageTitle = "My Profile";
    include "header.php";
    echo '<div class="alert-error">Your doctor profile has not been set up yet.</div>';
    include "footer.php";
    exit();
}

$error   = "";
$message = "";

$specialization = $doctor["specialization"];
$fee            = $doctor["consultation_fee"];
$available      = $doctor["available_time"];
$room           = $doctor["room"];

if (isset($_POST["submit"])) {
    $specialization = test_input($_POST["specialization"]);
    $available      = test_input($_POST["available_time"]);
    $room           = test_input($_POST["room"]);
    $fee            = $_POST["consultation_fee"];

    if ($specialization == "" || $available == "") {
        $error = "Specialization and visiting hours cannot be empty.";
    } elseif (!is_numeric($fee) || $fee < 0) {
        $error = "The consultation fee must be a number.";
    } else {
        $fee = (float) $fee;

        $stmt = mysqli_prepare($conn,
            "UPDATE doctors
             SET specialization = ?, consultation_fee = ?, available_time = ?, room = ?
             WHERE doctor_id = ?");
        mysqli_stmt_bind_param($stmt, "sdssi", $specialization, $fee, $available, $room, $doctor["doctor_id"]);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Your profile has been updated.";
        } else {
            $error = "Could not save your profile.";
        }
        mysqli_stmt_close($stmt);
    }
}

$stmt = mysqli_prepare($conn, "SELECT dept_name FROM departments WHERE dept_id = ?");
mysqli_stmt_bind_param($stmt, "i", $doctor["dept_id"]);
mysqli_stmt_execute($stmt);
$dept = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$pageTitle = "My Profile";
include "header.php";
?>

<div class="page-head">
    <div>
        <h1>My profile</h1>
        <p class="muted">Patients see these details when they search for a doctor.</p>
    </div>
</div>

<?php if ($message != ""): ?>
    <div class="alert-ok"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error != ""): ?>
    <div class="alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" class="card">

    <label>Department</label>
    <p><span class="pill"><?php echo $dept ? $dept["dept_name"] : "Not assigned"; ?></span>
       <span class="muted-inline">set by the admin</span></p>

    <label for="specialization">Specialization</label>
    <input type="text" id="specialization" name="specialization"
           value="<?php echo $specialization; ?>"
           placeholder="e.g. Heart Specialist, MBBS, MD">

    <div class="form-grid">
        <div>
            <label for="consultation_fee">Consultation fee (Tk)</label>
            <input type="number" id="consultation_fee" name="consultation_fee"
                   step="1" min="0" value="<?php echo $fee; ?>">
        </div>
        <div>
            <label for="available_time">Visiting hours</label>
            <input type="text" id="available_time" name="available_time"
                   value="<?php echo $available; ?>"
                   placeholder="e.g. Sun-Thu, 5 PM - 8 PM">
        </div>
    </div>

    <label for="room">Room</label>
    <input type="text" id="room" name="room" maxlength="20"
           value="<?php echo $room; ?>"
           placeholder="e.g. 304, 3rd floor">

    <input type="submit" name="submit" value="Save changes" class="btn btn-block">

</form>

<?php include "footer.php"; ?>
