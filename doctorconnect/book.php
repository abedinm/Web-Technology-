<?php
// ============================================================
// book.php - BOOK AN APPOINTMENT  (Patient feature 2)
//
// The doctor's id arrives in the URL ($_GET), the booking details
// are sent by the form ($_POST). Before saving we check that the
// same doctor does not already have that slot taken.
// ============================================================
include "auth.php";
require_role("patient");

// intval() forces the URL value to be a whole number. A patient
// could type anything after ?doctor_id= so we never trust it raw.
$doctorId = isset($_GET["doctor_id"]) ? intval($_GET["doctor_id"]) : 0;

// Load the doctor being booked.
$stmt = mysqli_prepare($conn,
    "SELECT d.doctor_id, u.full_name, dep.dept_name, d.specialization,
            d.consultation_fee, d.available_time
     FROM doctors d
     JOIN users u         ON d.user_id = u.user_id
     JOIN departments dep ON d.dept_id = dep.dept_id
     WHERE d.doctor_id = ?");
mysqli_stmt_bind_param($stmt, "i", $doctorId);
mysqli_stmt_execute($stmt);
$doctor = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// No such doctor? Back to the search page.
if (!$doctor) {
    header("Location: doctors.php");
    exit();
}

// The time slots a patient may choose.
$slots = array(
    "9:00 AM - 9:30 AM", "10:00 AM - 10:30 AM", "11:00 AM - 11:30 AM",
    "4:00 PM - 4:30 PM", "5:00 PM - 5:30 PM", "6:00 PM - 6:30 PM",
    "7:00 PM - 7:30 PM", "8:00 PM - 8:30 PM"
);

$error = "";
$date  = "";
$slot  = "";

if (isset($_POST["submit"])) {

    $date = test_input($_POST["appt_date"]);
    $slot = test_input($_POST["time_slot"]);

    if ($date == "" || $slot == "") {
        $error = "Please choose both a date and a time slot.";

    } elseif ($date < date("Y-m-d")) {
        // date("Y-m-d") is today. A booking in the past makes no sense.
        $error = "You cannot book a date in the past.";

    } elseif (!in_array($slot, $slots)) {
        $error = "That time slot is not available.";

    } else {
        // Is this doctor already booked at that date and time?
        // Cancelled bookings do not block the slot.
        $stmt = mysqli_prepare($conn,
            "SELECT appt_id FROM appointments
             WHERE doctor_id = ? AND appt_date = ? AND time_slot = ?
             AND status != 'cancelled'");
        mysqli_stmt_bind_param($stmt, "iss", $doctorId, $date, $slot);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $taken = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($taken) {
            $error = "That slot is already booked. Please pick another time.";
        } else {
            // Save the appointment. status defaults to 'pending' until
            // the doctor confirms it.
            $stmt = mysqli_prepare($conn,
                "INSERT INTO appointments (patient_id, doctor_id, appt_date, time_slot)
                 VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiss", $_SESSION["user_id"], $doctorId, $date, $slot);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Redirect after a successful POST so a page refresh does
            // not book the same appointment twice.
            header("Location: my_appointments.php?booked=1");
            exit();
        }
    }
}

// Which slots are already gone for the date being viewed? Used to
// grey out the taken ones in the dropdown.
$takenSlots = array();
if ($date != "") {
    $stmt = mysqli_prepare($conn,
        "SELECT time_slot FROM appointments
         WHERE doctor_id = ? AND appt_date = ? AND status != 'cancelled'");
    mysqli_stmt_bind_param($stmt, "is", $doctorId, $date);
    mysqli_stmt_execute($stmt);
    $r = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($r)) {
        $takenSlots[] = $row["time_slot"];
    }
    mysqli_stmt_close($stmt);
}

$pageTitle = "Book Appointment";
include "header.php";
?>

<div class="page-head">
    <h1>Book an appointment</h1>
    <p><a href="doctors.php">&larr; Back to doctor search</a></p>
</div>

<div class="card doctor-card">
    <div class="doctor-avatar">
        <?php
            $clean = str_replace("Dr. ", "", $doctor["full_name"]);
            $parts = explode(" ", $clean);
            $ini = substr($parts[0], 0, 1);
            if (count($parts) > 1) { $ini .= substr($parts[count($parts) - 1], 0, 1); }
            echo strtoupper($ini);
        ?>
    </div>
    <div class="doctor-info">
        <h3><?php echo $doctor["full_name"]; ?></h3>
        <p class="doctor-meta">
            <span class="pill"><?php echo $doctor["dept_name"]; ?></span>
            <?php echo $doctor["specialization"]; ?>
        </p>
        <p class="doctor-meta">
            Fee: <strong><?php echo $doctor["consultation_fee"]; ?> Tk</strong>
            &middot; Available: <?php echo $doctor["available_time"]; ?>
        </p>
    </div>
</div>

<div class="card">
    <h2>Choose date and time</h2>

    <?php if ($error != ""): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="post" action="book.php?doctor_id=<?php echo $doctorId; ?>"
          onsubmit="return checkBooking()">

        <label for="appt_date">Appointment date</label>
        <input type="date" id="appt_date" name="appt_date"
               value="<?php echo $date; ?>"
               min="<?php echo date('Y-m-d'); ?>">

        <label for="time_slot">Time slot</label>
        <select id="time_slot" name="time_slot">
            <option value="">-- Select a time --</option>
            <?php foreach ($slots as $s): ?>
                <?php $isTaken = in_array($s, $takenSlots); ?>
                <option value="<?php echo $s; ?>"
                    <?php echo ($slot == $s) ? "selected" : ""; ?>
                    <?php echo $isTaken ? "disabled" : ""; ?>>
                    <?php echo $s; ?><?php echo $isTaken ? " (booked)" : ""; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <span class="hint">Pick a date first, then reload to see which slots are already taken.</span>

        <span class="field-error" id="jsError"></span>

        <input type="submit" name="submit" value="Confirm booking" class="btn">
    </form>
</div>

<script>
function checkBooking() {
    var d = document.getElementById("appt_date").value;
    var t = document.getElementById("time_slot").value;
    var box = document.getElementById("jsError");

    if (d == "") { box.innerHTML = "Please choose a date."; return false; }
    if (t == "") { box.innerHTML = "Please choose a time slot."; return false; }
    return true;
}
</script>

<?php include "footer.php"; ?>
