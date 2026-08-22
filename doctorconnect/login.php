<?php
// ============================================================
// login.php - LOGIN FOR ALL THREE USER TYPES
//
// One login page serves patients, doctors and admins. After the
// password is checked we read the user's role out of the database
// and send them to the dashboard that belongs to that role.
// ============================================================
include "auth.php";

// Already logged in? Skip the form.
if (is_logged_in()) {
    header("Location: " . dashboard_for($_SESSION["role"]));
    exit();
}

$error = "";
$email = "";

// This runs only when the form has been submitted.
if (isset($_POST["submit"])) {

    $email    = test_input($_POST["email"]);
    $password = $_POST["password"];      // not cleaned: it is verified, never printed

    if ($email == "" || $password == "") {
        $error = "Please fill in both fields.";
    } else {
        // Prepared statement: the email goes in as DATA, so a typed
        // quote can never change the SQL. This is what stops SQL injection.
        $stmt = mysqli_prepare($conn, "SELECT user_id, full_name, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // password_verify compares the typed password against the
        // stored hash. We never store or compare the real password.
        if ($user && password_verify($password, $user["password"])) {

            // Give the session a brand new id now that the user has
            // proved who they are. If somebody managed to fix a known
            // session id on this browser beforehand, that id is now
            // worthless - this is the defence against session fixation.
            session_regenerate_id(true);

            // Remember who they are for every later page.
            $_SESSION["user_id"]   = $user["user_id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["role"]      = $user["role"];

            header("Location: " . dashboard_for($user["role"]));
            exit();
        } else {
            // Same message for a wrong email and a wrong password, so
            // nobody can use this page to discover which emails exist.
            $error = "Wrong email or password.";
        }
    }
}

$pageTitle = "Login";
include "header.php";
?>

<div class="auth-wrap">
    <div class="card auth-card">
        <h1>Sign in</h1>
        <p class="muted">Use your DoctorConnect account.</p>

        <?php if ($error != ""): ?>
            <div class="alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <label for="email">Email</label>
            <input type="text" id="email" name="email" value="<?php echo $email; ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password">

            <input type="submit" name="submit" value="Login" class="btn btn-block">
        </form>

        <p class="auth-alt">New patient? <a href="register.php">Create an account</a></p>

        <div class="demo-box">
            <strong>Demo accounts</strong> (password for all: <code>1234</code>)<br>
            Admin: admin@doctorconnect.com<br>
            Doctor: salma@doctorconnect.com<br>
            Patient: nusrat@gmail.com
        </div>
    </div>
</div>

<?php include "footer.php"; ?>
