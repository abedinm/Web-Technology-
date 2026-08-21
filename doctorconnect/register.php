<?php
// ============================================================
// register.php - PATIENT REGISTRATION
//
// Only patients sign up here. Doctors are created by the admin,
// so the role is fixed to 'patient' in the INSERT below - the
// visitor cannot choose to become an admin.
// ============================================================
include "auth.php";

if (is_logged_in()) {
    header("Location: " . dashboard_for($_SESSION["role"]));
    exit();
}

// One error message per field, shown next to that field.
$errors = array("name" => "", "email" => "", "phone" => "", "pass" => "", "confirm" => "");
$name = $email = $phone = "";
$success = "";

if (isset($_POST["submit"])) {

    $name    = test_input($_POST["full_name"]);
    $email   = test_input($_POST["email"]);
    $phone   = test_input($_POST["phone"]);
    $pass    = $_POST["password"];
    $confirm = $_POST["confirm"];

    // ---- validation, one rule per field ----
    if ($name == "") {
        $errors["name"] = "Full name is required.";
    } elseif (strlen($name) < 3) {
        $errors["name"] = "Name must be at least 3 characters.";
    }

    if ($email == "") {
        $errors["email"] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // filter_var with FILTER_VALIDATE_EMAIL is PHP's built-in
        // email checker - it returns false for an invalid address.
        $errors["email"] = "Enter a valid email address.";
    }

    if ($phone == "") {
        $errors["phone"] = "Phone number is required.";
    }

    if ($pass == "") {
        $errors["pass"] = "Password is required.";
    } elseif (strlen($pass) < 4) {
        $errors["pass"] = "Password must be at least 4 characters.";
    }

    if ($confirm != $pass) {
        $errors["confirm"] = "The two passwords do not match.";
    }

    // Is this email already registered? The email column is UNIQUE,
    // so we check first and show a friendly message instead of
    // letting MySQL throw a duplicate-entry error.
    if ($errors["email"] == "") {
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors["email"] = "This email is already registered.";
        }
        mysqli_stmt_close($stmt);
    }

    // ---- no errors? save the new patient ----
    if (implode("", $errors) == "") {

        // Never store the real password. password_hash scrambles it
        // one way, so even we cannot read it back.
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO users (full_name, email, password, phone, role)
             VALUES (?, ?, ?, ?, 'patient')");
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hash, $phone);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Account created. You can sign in now.";
            $name = $email = $phone = "";
        } else {
            $errors["email"] = "Could not create the account. Try again.";
        }
        mysqli_stmt_close($stmt);
    }
}

$pageTitle = "Register";
include "header.php";
?>

<div class="auth-wrap">
    <div class="card auth-card">
        <h1>Create a patient account</h1>
        <p class="muted">Register to book appointments with our doctors.</p>

        <?php if ($success != ""): ?>
            <div class="alert-ok">
                <?php echo $success; ?> <a href="login.php">Go to login</a>
            </div>
        <?php endif; ?>

        <!-- onsubmit="return checkForm()" - the word return is required.
             Without it the browser ignores the false and submits anyway. -->
        <form method="post" action="register.php" onsubmit="return checkForm()">

            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" value="<?php echo $name; ?>">
            <span class="field-error"><?php echo $errors["name"]; ?></span>

            <label for="email">Email</label>
            <input type="text" id="email" name="email" value="<?php echo $email; ?>">
            <span class="field-error"><?php echo $errors["email"]; ?></span>

            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?php echo $phone; ?>">
            <span class="field-error"><?php echo $errors["phone"]; ?></span>

            <label for="password">Password</label>
            <input type="password" id="password" name="password">
            <span class="field-error"><?php echo $errors["pass"]; ?></span>

            <label for="confirm">Confirm Password</label>
            <input type="password" id="confirm" name="confirm">
            <span class="field-error"><?php echo $errors["confirm"]; ?></span>

            <span class="field-error" id="jsError"></span>

            <input type="submit" name="submit" value="Register" class="btn btn-block">
        </form>

        <p class="auth-alt">Already registered? <a href="login.php">Sign in</a></p>
    </div>
</div>

<script>
// CLIENT-SIDE VALIDATION (JavaScript, runs in the browser).
// This gives instant feedback. The PHP checks above still run on
// the server, because a visitor can switch JavaScript off.
function checkForm() {
    var name    = document.getElementById("full_name").value;
    var email   = document.getElementById("email").value;
    var pass    = document.getElementById("password").value;
    var confirm = document.getElementById("confirm").value;
    var box     = document.getElementById("jsError");

    if (name == "" || email == "" || pass == "") {
        box.innerHTML = "Please fill in all the fields.";
        return false;        // false stops the form from submitting
    }
    if (pass.length < 4) {
        box.innerHTML = "Password must be at least 4 characters.";
        return false;
    }
    if (pass != confirm) {
        box.innerHTML = "The two passwords do not match.";
        return false;
    }
    return true;             // true lets the form go to the server
}
</script>

<?php include "footer.php"; ?>
