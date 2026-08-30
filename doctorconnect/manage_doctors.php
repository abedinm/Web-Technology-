<?php

include "auth.php";
require_role("admin");

$error   = "";
$message = "";

if (isset($_POST["add"])) {
    $name  = test_input($_POST["full_name"]);
    $email = test_input($_POST["email"]);
    $phone = test_input($_POST["phone"]);
    $deptId = (int) $_POST["dept_id"];
    $spec  = test_input($_POST["specialization"]);
    $fee   = $_POST["consultation_fee"];
    $time  = test_input($_POST["available_time"]);
    $room  = test_input($_POST["room"]);
    $pass  = $_POST["password"];

    if ($name == "" || $email == "" || $pass == "" || $deptId == 0) {
        $error = "Name, email, password and department are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!is_numeric($fee) || $fee < 0) {
        $error = "The consultation fee must be a number.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $taken = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($taken) {
            $error = "That email is already registered.";
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn,
                "INSERT INTO users (full_name, email, password, phone, role)
                 VALUES (?, ?, ?, ?, 'doctor')");
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hash, $phone);

            if (mysqli_stmt_execute($stmt)) {
                $newUserId = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);

                $fee = (float) $fee;
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO doctors (user_id, dept_id, specialization, consultation_fee, available_time, room)
                     VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "iisdss", $newUserId, $deptId, $spec, $fee, $time, $room);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                $message = "Doctor added.";
            } else {
                $error = "Could not create the doctor account.";
                mysqli_stmt_close($stmt);
            }
        }
    }
}

if (isset($_POST["update"])) {
    $doctorId = (int) $_POST["doctor_id"];
    $deptId   = (int) $_POST["dept_id"];
    $spec     = test_input($_POST["specialization"]);
    $fee      = $_POST["consultation_fee"];
    $time     = test_input($_POST["available_time"]);
    $room     = test_input($_POST["room"]);

    if (!is_numeric($fee) || $fee < 0) {
        $error = "The consultation fee must be a number.";
    } else {
        $fee = (float) $fee;
        $stmt = mysqli_prepare($conn,
            "UPDATE doctors SET dept_id = ?, specialization = ?, consultation_fee = ?, available_time = ?, room = ?
             WHERE doctor_id = ?");
        mysqli_stmt_bind_param($stmt, "isdssi", $deptId, $spec, $fee, $time, $room, $doctorId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $message = "Doctor updated.";
    }
}

if (isset($_POST["remove"])) {
    $doctorId = (int) $_POST["doctor_id"];

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM appointments WHERE doctor_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $doctorId);
    mysqli_stmt_execute($stmt);
    $used = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($used["total"] > 0) {
        $error = "This doctor has " . $used["total"] . " appointment(s) and cannot be removed.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM doctors WHERE doctor_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $doctorId);
        mysqli_stmt_execute($stmt);
        $owner = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conn, "DELETE FROM doctors WHERE doctor_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $doctorId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if ($owner) {
            $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $owner["user_id"]);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        $message = "Doctor removed.";
    }
}

$departments = mysqli_query($conn, "SELECT dept_id, dept_name FROM departments ORDER BY dept_name");
$deptList = array();
while ($d = mysqli_fetch_assoc($departments)) {
    $deptList[] = $d;
}

$doctors = mysqli_query($conn,
    "SELECT d.doctor_id, d.dept_id, d.specialization, d.consultation_fee, d.available_time, d.room,
            u.full_name, u.email, u.phone
     FROM doctors d
     JOIN users u ON d.user_id = u.user_id
     ORDER BY u.full_name");

$pageTitle = "Doctors";
include "header.php";
?>

<div class="page-head">
    <div>
        <h1>Doctors</h1>
        <p class="muted">Add a doctor, change their details, or remove one who has no appointments.</p>
    </div>
</div>

<?php if ($message != ""): ?>
    <div class="alert-ok"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error != ""): ?>
    <div class="alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" class="card">
    <h2 class="nav-label">Add a new doctor</h2>

    <div class="form-grid">
        <div>
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" placeholder="Dr. Salma Akter">
        </div>
        <div>
            <label for="email">Email</label>
            <input type="text" id="email" name="email" placeholder="salma@doctorconnect.com">
        </div>
    </div>

    <div class="form-grid">
        <div>
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" placeholder="01XXXXXXXXX">
        </div>
        <div>
            <label for="password">Temporary password</label>
            <input type="text" id="password" name="password" placeholder="the doctor changes it later">
        </div>
    </div>

    <div class="form-grid">
        <div>
            <label for="dept_id">Department</label>
            <select id="dept_id" name="dept_id">
                <option value="0">-- choose --</option>
                <?php foreach ($deptList as $d): ?>
                    <option value="<?php echo $d["dept_id"]; ?>"><?php echo $d["dept_name"]; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="consultation_fee">Consultation fee (Tk)</label>
            <input type="number" id="consultation_fee" name="consultation_fee" step="1" min="0" value="500">
        </div>
    </div>

    <label for="specialization">Specialization</label>
    <input type="text" id="specialization" name="specialization" placeholder="Heart Specialist, MBBS, MD">

    <div class="form-grid">
        <div>
            <label for="available_time">Visiting hours</label>
            <input type="text" id="available_time" name="available_time" placeholder="Sun-Thu, 5 PM - 8 PM">
        </div>
        <div>
            <label for="room">Room</label>
            <input type="text" id="room" name="room" maxlength="20" placeholder="304, 3rd floor">
        </div>
    </div>

    <input type="submit" name="add" value="Add doctor" class="btn btn-block">
</form>

<div class="card">
    <h2 class="nav-label">All doctors</h2>

    <?php if (mysqli_num_rows($doctors) == 0): ?>
        <p class="empty">No doctors have been added yet.</p>
    <?php else: ?>

    <div class="table-wrap">
    <table>
        <tr>
            <th>Doctor</th><th>Department</th><th>Fee</th>
            <th>Visiting hours</th><th>Room</th><th>Save</th><th>Remove</th>
        </tr>

        <?php while ($doc = mysqli_fetch_assoc($doctors)): ?>
        <tr>
            <td>
                <?php echo $doc["full_name"]; ?><br>
                <span class="muted-inline"><?php echo $doc["email"]; ?></span>
            </td>
            <td>
                <form method="POST" class="inline-form" id="f<?php echo $doc["doctor_id"]; ?>">
                    <input type="hidden" name="doctor_id" value="<?php echo $doc["doctor_id"]; ?>">
                    <select name="dept_id">
                        <?php foreach ($deptList as $d): ?>
                            <option value="<?php echo $d["dept_id"]; ?>"
                                <?php echo ($d["dept_id"] == $doc["dept_id"]) ? "selected" : ""; ?>>
                                <?php echo $d["dept_name"]; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
            </td>
            <td>
                <input type="number" name="consultation_fee" step="1" min="0"
                       value="<?php echo (int) $doc["consultation_fee"]; ?>" style="width:90px">
            </td>
            <td>
                <input type="text" name="available_time" value="<?php echo $doc["available_time"]; ?>">
                <input type="hidden" name="specialization" value="<?php echo $doc["specialization"]; ?>">
            </td>
            <td>
                <input type="text" name="room" value="<?php echo $doc["room"]; ?>" style="width:90px">
            </td>
            <td>
                <input type="submit" name="update" value="Save" class="btn btn-small">
                </form>
            </td>
            <td>
                <form method="POST" class="inline-form"
                      onsubmit="return confirm('Remove this doctor?')">
                    <input type="hidden" name="doctor_id" value="<?php echo $doc["doctor_id"]; ?>">
                    <input type="submit" name="remove" value="Remove" class="btn btn-small btn-danger">
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    </div>

    <?php endif; ?>
</div>

<?php include "footer.php"; ?>
