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
$slot  = "";

// The day cards reload the page with ?date=YYYY-MM-DD, so read that first
// and fall back to today. A malformed value in the URL is ignored rather
// than passed on to the query below.
if (isset($_GET["date"]) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $_GET["date"])) {
    $date = $_GET["date"];
} else {
    $date = date("Y-m-d");
}

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

        <label>Select a date</label>

        <!-- Seven days as tappable cards, the way the Figma design shows it.
             The real value still travels in a hidden field so the PHP above
             is unchanged. -->
        <div class="daystrip">
            <?php
            for ($d = 0; $d < 7; $d++) {

                $stamp = strtotime("+$d day");
                $value = date("Y-m-d", $stamp);
                $on    = ($date == $value);
                ?>
                <button type="button"
                        class="day<?php echo $on ? " on" : ""; ?>"
                        data-date="<?php echo $value; ?>">
                    <span class="day-w"><?php echo date("D", $stamp); ?></span>
                    <span class="day-n"><?php echo date("j", $stamp); ?></span>
                </button>
            <?php } ?>
        </div>

        <input type="hidden" id="appt_date" name="appt_date" value="<?php echo $date; ?>">

        <label>Available time slots</label>

        <!-- Slots as a grid of chips. A booked slot is shown flat and cannot
             be chosen, which is clearer than a disabled <option>. -->
        <div class="slotgrid">
            <?php foreach ($slots as $s): ?>
                <?php $isTaken = in_array($s, $takenSlots); ?>
                <button type="button"
                        class="slot<?php echo $isTaken ? " taken" : ($slot == $s ? " on" : ""); ?>"
                        data-slot="<?php echo $s; ?>"
                        <?php echo $isTaken ? "disabled" : ""; ?>>
                    <?php echo $s; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <input type="hidden" id="time_slot" name="time_slot" value="<?php echo $slot; ?>">

        <div class="slotkey">
            <span><i class="k-on"></i> Selected</span>
            <span><i class="k-free"></i> Available</span>
            <span><i class="k-taken"></i> Booked</span>
        </div>

        <span class="hint">Choosing a date reloads the page so the booked slots for that day are shown.</span>

        <span class="field-error" id="jsError"></span>

        <input type="submit" name="submit" value="Confirm booking" class="btn">
    </form>
</div>

<script>
// The date cards and slot chips are buttons. Clicking one writes its value
// into the matching hidden input, so the form still submits exactly the
// same two fields the PHP at the top of this file expects.

var dateField = document.getElementById("appt_date");
var slotField = document.getElementById("time_slot");

// Choosing a date reloads the page, because which slots are already taken
// depends on the date and that answer lives on the server.
document.querySelectorAll(".daystrip .day").forEach(function (btn) {
    btn.addEventListener("click", function () {
        var url = new URL(window.location.href);
        url.searchParams.set("date", btn.dataset.date);
        window.location.href = url.toString();
    });
});

document.querySelectorAll(".slotgrid .slot").forEach(function (btn) {
    btn.addEventListener("click", function () {
        if (btn.disabled) { return; }
        document.querySelectorAll(".slotgrid .slot").forEach(function (b) {
            b.classList.remove("on");
        });
        btn.classList.add("on");
        slotField.value = btn.dataset.slot;
        document.getElementById("jsError").innerHTML = "";
    });
});

function checkBooking() {
    var box = document.getElementById("jsError");

    if (dateField.value == "") { box.innerHTML = "Please choose a date."; return false; }
    if (slotField.value == "") { box.innerHTML = "Please choose a time slot."; return false; }
    return true;
}
</script>

<?php include "footer.php"; ?>
