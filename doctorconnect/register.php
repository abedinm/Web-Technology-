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
$authSplit = true;   // split brand/form layout, as in the Figma design
include "header.php";
?>

<div class="split">

    <!-- Left: brand panel, as in the Figma "02 - Register" screen. -->
    <aside class="split-brand split-brand-narrow">
        <a href="index.php" class="split-logo">
            <?php echo logo_mark(40); ?>
            <span>DoctorConnect</span>
        </a>

        <h1 class="split-title">Create your<br>account.</h1>

        <p class="split-sub">One account for every role in the hospital &mdash;
           patient, doctor, receptionist and admin.</p>
    </aside>

    <!-- Right: the register card, with the paired fields the design shows. -->
    <main class="split-form">
        <div class="card auth-card auth-card-wide">
            <h1>Register</h1>
            <p class="muted">Fill in your details to create an account.</p>

            <?php if ($success != ""): ?>
                <div class="alert-ok">
                    <?php echo $success; ?> <a href="login.php">Go to login</a>
                </div>
            <?php endif; ?>

            <!-- onsubmit="return checkForm()" - the word return is required.
                 Without it the browser ignores the false and submits anyway. -->
            <form method="post" action="register.php" onsubmit="return checkForm()">

                <div class="form-grid">
                    <div>
                        <label for="full_name">Full name</label>
                        <input type="text" id="full_name" name="full_name"
                               value="<?php echo $name; ?>" placeholder="e.g. Minhazul Abedin">
                        <span class="field-error"><?php echo $errors["name"]; ?></span>
                    </div>
                    <div>
                        <label for="email">Email</label>
                        <input type="text" id="email" name="email"
                               value="<?php echo $email; ?>" placeholder="you@example.com">
                        <span class="field-error"><?php echo $errors["email"]; ?></span>
                    </div>
                </div>

                <div class="form-grid">
                    <div>
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone"
                               value="<?php echo $phone; ?>" placeholder="01XXXXXXXXX">
                        <span class="field-error"><?php echo $errors["phone"]; ?></span>
                    </div>
                    <div>
                        <label for="role_display">Role</label>
                        <input type="text" id="role_display" value="Patient" readonly>
                        <span class="hint">Doctor and staff accounts are created by the admin.</span>
                    </div>
                </div>

                <div class="form-grid">
                    <div>
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="********">
                        <span class="field-error"><?php echo $errors["pass"]; ?></span>
                    </div>
                    <div>
                        <label for="confirm">Confirm password</label>
                        <input type="password" id="confirm" name="confirm" placeholder="********">
                        <span class="field-error"><?php echo $errors["confirm"]; ?></span>
                    </div>
                </div>

                <span class="field-error" id="jsError"></span>

                <input type="submit" name="submit" value="Create account" class="btn btn-block">
            </form>

            <p class="auth-alt">Already registered? <a href="login.php">Log in</a></p>
        </div>
    </main>

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
