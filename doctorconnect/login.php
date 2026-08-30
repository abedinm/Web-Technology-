<?php

include "auth.php";

if (is_logged_in()) {
    header("Location: " . dashboard_for($_SESSION["role"]));
    exit();
}

$error = "";
$email = "";

if (isset($_POST["submit"])) {
    $email    = test_input($_POST["email"]);
    $password = $_POST["password"];

    if ($email == "" || $password == "") {
        $error = "Please fill in both fields.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id, full_name, password, role FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user   = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($user && password_verify($password, $user["password"])) {
            session_regenerate_id(true);

            $_SESSION["user_id"]   = $user["user_id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["role"]      = $user["role"];

            header("Location: " . dashboard_for($user["role"]));
            exit();
        } else {
            $error = "Wrong email or password.";
        }
    }
}

$pageTitle = "Login";
$authSplit = true;
include "header.php";
?>

<div class="split">

    <aside class="split-brand">
        <a href="index.php" class="split-logo">
            <?php echo logo_mark(40); ?>
            <span>DoctorConnect</span>
        </a>

        <h1 class="split-title">Book your doctor,<br>skip the queue.</h1>

        <p class="split-sub">An online appointment system for the hospital reception
           desk &mdash; patients, doctors, receptionists and admin in one place.</p>

        <ul class="split-points">
            <li>Search doctors by department</li>
            <li>Book an open time slot instantly</li>
            <li>Track appointment status online</li>
        </ul>
    </aside>

    <main class="split-form">
        <div class="card auth-card">
            <h1>Log in</h1>
            <p class="muted">Enter your account details to continue.</p>

            <?php if ($error != ""): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="post" action="login.php">
                <label for="email">Email</label>
                <input type="text" id="email" name="email" value="<?php echo $email; ?>"
                       placeholder="you@example.com">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="********">

                <input type="submit" name="submit" value="Log in" class="btn btn-block">
            </form>

            <p class="auth-alt">Don&rsquo;t have an account? <a href="register.php">Register</a></p>

            <div class="demo-box">
                <strong>Demo accounts</strong> (password for all: <code>1234</code>)<br>
                Admin: admin@doctorconnect.com<br>
                Doctor: salma@doctorconnect.com<br>
                Receptionist: reception@doctorconnect.com<br>
                Patient: nusrat@gmail.com
            </div>
        </div>
    </main>

</div>

<?php include "footer.php"; ?>
