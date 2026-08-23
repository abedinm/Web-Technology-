<?php
// ============================================================
// walkin.php - BOOKING FOR A PATIENT AT THE COUNTER
//
// A patient who walks in without booking online still needs a row
// in the appointments table. The receptionist either picks a patient
// who already has an account, or types the details of a new one -
// in which case a patient account is created first, because
// appointments.patient_id is a foreign key into users.
// ============================================================
include "auth.php";
require_role("receptionist");

// The same slot list the patients see in book.php.
$slots = array(
    "9:00 AM - 9:30 AM", "10:00 AM - 10:30 AM", "11:00 AM - 11:30 AM",
    "4:00 PM - 4:30 PM", "5:00 PM - 5:30 PM", "6:00 PM - 6:30 PM",
    "7:00 PM - 7:30 PM", "8:00 PM - 8:30 PM"
);

$error   = "";
$message = "";

// Values kept so the form can be redrawn with what was typed.
$patientMode = "existing";
$patientId   = "";
$newName     = "";
$newPhone    = "";
$newEmail    = "";
$doctorId    = "";
$date        = date("Y-m-d");
$slot        = "";

if (isset($_POST["submit"])) {

    $patientMode = isset($_POST["patient_mode"]) ? $_POST["patient_mode"] : "existing";
    $patientId   = isset($_POST["patient_id"]) ? (int) $_POST["patient_id"] : 0;
    $newName     = test_input($_POST["new_name"]);
    $newPhone    = test_input($_POST["new_phone"]);
    $newEmail    = test_input($_POST["new_email"]);
    $doctorId    = (int) $_POST["doctor_id"];
    $date        = test_input($_POST["appt_date"]);
    $slot        = test_input($_POST["time_slot"]);

    // ---------- validation ----------
    if ($doctorId == 0 || $date == "" || $slot == "") {
        $error = "Please choose a doctor, a date and a time slot.";

    } elseif (!in_array($slot, $slots)) {
        $error = "That time slot is not available.";

    } elseif ($date < date("Y-m-d")) {
        $error = "You cannot book a date in the past.";

    } elseif ($patientMode == "existing" && $patientId == 0) {
        $error = "Please choose a patient from the list.";

    } elseif ($patientMode == "new" && ($newName == "" || $newPhone == "")) {
        $error = "A new patient needs at least a name and a phone number.";

    } else {

        // ---------- create the patient account if this is a new person ----------
        if ($patientMode == "new") {

            // The email column is UNIQUE and NOT NULL. Someone at the
            // counter may not have an email address, so we build a
            // placeholder from the phone number they gave.
            if ($newEmail == "") {
                $newEmail = "walkin." . preg_replace("/[^0-9]/", "", $newPhone) . "@doctorconnect.local";
            }

            // Is that email already registered? Then reuse the account
            // instead of failing on the UNIQUE constraint.
            $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $newEmail);
            mysqli_stmt_execute($stmt);
            $found = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if ($found) {
                $patientId = $found["user_id"];
            } else {
                // Walk-in accounts get the default password 1234, which
                // the patient can change after logging in.
                $hash = password_hash("1234", PASSWORD_DEFAULT);
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO users (full_name, email, password, phone, role)
                     VALUES (?, ?, ?, ?, 'patient')");
                mysqli_stmt_bind_param($stmt, "ssss", $newName, $newEmail, $hash, $newPhone);

                if (mysqli_stmt_execute($stmt)) {
                    $patientId = mysqli_insert_id($conn);
                } else {
                    $error = "Could not create the patient account.";
                }
                mysqli_stmt_close($stmt);
            }
        }

        // ---------- is the slot still free ----------
        if ($error == "") {
            $stmt = mysqli_prepare($conn,
                "SELECT appt_id FROM appointments
                 WHERE doctor_id = ? AND appt_date = ? AND time_slot = ?
                 AND status != 'cancelled'");
            mysqli_stmt_bind_param($stmt, "iss", $doctorId, $date, $slot);
            mysqli_stmt_execute($stmt);
            $taken = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if ($taken) {
                $error = "That slot is already booked. Please pick another time.";
            } else {

                // A walk-in patient is standing at the counter, so the
                // booking is created already confirmed rather than pending.
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO appointments (patient_id, doctor_id, appt_date, time_slot, status)
                     VALUES (?, ?, ?, ?, 'confirmed')");
                mysqli_stmt_bind_param($stmt, "iiss", $patientId, $doctorId, $date, $slot);

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    // Redirect after a successful POST so refreshing the
                    // page does not book the same slot twice.
                    header("Location: reception_dashboard.php?date=" . urlencode($date));
                    exit();
                } else {
                    $error = "Could not save the appointment.";
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

// ---------- lists for the two dropdowns ----------
$patients = mysqli_query($conn,
    "SELECT user_id, full_name, phone FROM users
     WHERE role = 'patient' ORDER BY full_name");

$doctors = mysqli_query($conn,
    "SELECT d.doctor_id, u.full_name, dep.dept_name, d.consultation_fee
     FROM doctors d
     JOIN users u        ON d.user_id = u.user_id
     JOIN departments dep ON d.dept_id = dep.dept_id
     ORDER BY u.full_name");

$pageTitle = "Book Walk-in";
include "header.php";
?>

<div class="page-head">
    <div>
        <h1>Book a walk-in</h1>
        <p class="muted">For a patient standing at the counter. The booking is confirmed straight away.</p>
    </div>
    <a href="reception_dashboard.php" class="btn btn-small btn-ghost"><?php echo icon("arrow-left"); ?> Back to queue</a>
</div>

<?php if ($error != ""): ?>
    <div class="alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" class="card">

    <h2 class="nav-label">Patient</h2>

    <label class="muted-inline">
        <input type="radio" name="patient_mode" value="existing"
            <?php echo $patientMode == "existing" ? "checked" : ""; ?>>
        Existing patient
    </label>

    <select name="patient_id">
        <option value="0">-- choose a registered patient --</option>
        <?php while ($p = mysqli_fetch_assoc($patients)): ?>
            <option value="<?php echo $p["user_id"]; ?>"
                <?php echo ($patientId == $p["user_id"]) ? "selected" : ""; ?>>
                <?php echo $p["full_name"]; ?> (<?php echo $p["phone"]; ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <label class="muted-inline">
        <input type="radio" name="patient_mode" value="new"
            <?php echo $patientMode == "new" ? "checked" : ""; ?>>
        New patient
    </label>

    <div class="form-grid">
        <div>
            <label for="new_name">Full name</label>
            <input type="text" id="new_name" name="new_name" value="<?php echo $newName; ?>"
                   placeholder="Name as on the ticket">
        </div>
        <div>
            <label for="new_phone">Phone</label>
            <input type="text" id="new_phone" name="new_phone" value="<?php echo $newPhone; ?>"
                   placeholder="01XXXXXXXXX">
        </div>
    </div>

    <label for="new_email">Email <span class="muted-inline">(optional)</span></label>
    <input type="text" id="new_email" name="new_email" value="<?php echo $newEmail; ?>"
           placeholder="Left blank, one is generated from the phone number">

    <h2 class="nav-label">Appointment</h2>

    <label for="doctor_id">Doctor</label>
    <select id="doctor_id" name="doctor_id">
        <option value="0">-- choose a doctor --</option>
        <?php while ($d = mysqli_fetch_assoc($doctors)): ?>
            <option value="<?php echo $d["doctor_id"]; ?>"
                <?php echo ($doctorId == $d["doctor_id"]) ? "selected" : ""; ?>>
                <?php echo $d["full_name"]; ?> &mdash; <?php echo $d["dept_name"]; ?>
                (Tk <?php echo number_format($d["consultation_fee"], 0); ?>)
            </option>
        <?php endwhile; ?>
    </select>

    <div class="form-grid">
        <div>
            <label for="appt_date">Date</label>
            <input type="date" id="appt_date" name="appt_date" value="<?php echo $date; ?>">
        </div>
        <div>
            <label for="time_slot">Time slot</label>
            <select id="time_slot" name="time_slot">
                <option value="">-- choose a slot --</option>
                <?php foreach ($slots as $s): ?>
                    <option value="<?php echo $s; ?>" <?php echo ($slot == $s) ? "selected" : ""; ?>>
                        <?php echo $s; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <button type="submit" name="submit" class="btn btn-block">Confirm walk-in</button>

    <p class="hint">
        A new patient account is created with the default password 1234,
        which the patient can change after logging in.
    </p>

</form>

<?php include "footer.php"; ?>
