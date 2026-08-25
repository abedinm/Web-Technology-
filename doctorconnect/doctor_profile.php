<?php
// ============================================================
// doctor_profile.php - UPDATE MY DETAILS  (Doctor feature 2)
// The doctor edits their specialization, consultation fee and
// available time. This is a classic UPDATE form: load the row,
// show it in the fields, save the changes back.
// ============================================================
include "auth.php";
require_role("doctor");

$doctor = current_doctor($conn);

if (!$doctor) {
    $pageTitle = "My Profile";
    include "header.php";
    echo '<div class="card"><p class="empty">Your doctor profile has not been set up yet.</p></div>';
    include "footer.php";
    exit();
}

$message = "";
$error   = "";

if (isset($_POST["submit"])) {

    $spec = test_input($_POST["specialization"]);
    $fee  = test_input($_POST["consultation_fee"]);
    $time = test_input($_POST["available_time"]);
    $dept = intval($_POST["dept_id"]);

    if ($spec == "" || $time == "") {
        $error = "Specialization and available time are required.";

    } elseif (!is_numeric($fee) || $fee < 0) {
        // is_numeric checks the text is a number - the fee box is a
        // text field, so a patient-facing price could otherwise be "abc".
        $error = "Consultation fee must be a number.";

    } else {
        // "d" in bind_param means double (a decimal number).
        $stmt = mysqli_prepare($conn,
            "UPDATE doctors
             SET specialization = ?, consultation_fee = ?, available_time = ?, dept_id = ?
             WHERE doctor_id = ?");
        mysqli_stmt_bind_param($stmt, "sdsii", $spec, $fee, $time, $dept, $doctor["doctor_id"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $message = "Your profile has been updated.";
        $doctor  = current_doctor($conn);   // reload so the form shows the new values
    }
}

$deptList = mysqli_query($conn, "SELECT * FROM departments ORDER BY dept_name");

$pageTitle = "My Profile";
include "header.php";
?>

<div class="page-head">
    <h1>My profile</h1>
    <p>Patients see this information when they search for a doctor.</p>
</div>

<?php if ($message != ""): ?><div class="alert-ok"><?php echo $message; ?></div><?php endif; ?>
<?php if ($error   != ""): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>

<div class="card">
    <h2>Account details</h2>
    <p class="muted">Your name and email are managed under Account.</p>
    <div class="table-wrap">
        <table>
        <tr><th>Name</th><td><?php echo $_SESSION["full_name"]; ?></td></tr>
        <tr><th>Role</th><td><span class="role role-doctor">doctor</span></td></tr>
    </table>
        </div>
</div>

<div class="card">
    <h2>Professional details</h2>

    <form method="post" action="doctor_profile.php" onsubmit="return checkProfile()">

        <label for="dept_id">Department</label>
        <select id="dept_id" name="dept_id">
            <?php while ($d = mysqli_fetch_assoc($deptList)): ?>
                <option value="<?php echo $d["dept_id"]; ?>"
                    <?php echo ($doctor["dept_id"] == $d["dept_id"]) ? "selected" : ""; ?>>
                    <?php echo $d["dept_name"]; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="specialization">Specialization / qualifications</label>
        <input type="text" id="specialization" name="specialization"
               value="<?php echo $doctor["specialization"]; ?>">

        <label for="consultation_fee">Consultation fee (Tk)</label>
        <input type="text" id="consultation_fee" name="consultation_fee"
               value="<?php echo $doctor["consultation_fee"]; ?>">

        <label for="available_time">Available time</label>
        <input type="text" id="available_time" name="available_time"
               value="<?php echo $doctor["available_time"]; ?>"
               placeholder="e.g. Sun-Thu, 5 PM - 8 PM">

        <span class="field-error" id="jsError"></span>

        <input type="submit" name="submit" value="Save changes" class="btn">
    </form>
</div>

<script>
function checkProfile() {
    var spec = document.getElementById("specialization").value;
    var fee  = document.getElementById("consultation_fee").value;
    var time = document.getElementById("available_time").value;
    var box  = document.getElementById("jsError");

    if (spec == "" || time == "") {
        box.innerHTML = "Specialization and available time cannot be empty.";
        return false;
    }
    // isNaN means "is not a number" - it catches a fee typed as text.
    if (fee == "" || isNaN(fee)) {
        box.innerHTML = "Fee must be a number.";
        return false;
    }
    return true;
}
</script>

<?php include "footer.php"; ?>
