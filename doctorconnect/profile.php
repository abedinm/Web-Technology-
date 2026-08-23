<?php
// ============================================================
// profile.php - MY ACCOUNT (every role)
//
// The details that live in the users table: name, email, phone and
// the password. Linked from the sidebar for all four roles, so it
// only needs require_login(), not require_role().
// ============================================================
include "auth.php";
require_login();

$userId = $_SESSION["user_id"];

$error   = "";
$message = "";

// ---------- save name, email and phone ----------
if (isset($_POST["save_details"])) {

    $name  = test_input($_POST["full_name"]);
    $email = test_input($_POST["email"]);
    $phone = test_input($_POST["phone"]);

    if ($name == "" || $email == "") {
        $error = "Name and email cannot be empty.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } else {

        // Somebody else may already own that email, because the
        // column is UNIQUE. "user_id != ?" ignores my own row.
        $stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        mysqli_stmt_bind_param($stmt, "si", $email, $userId);
        mysqli_stmt_execute($stmt);
        $taken = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($taken) {
            $error = "That email is already used by another account.";
        } else {
            $stmt = mysqli_prepare($conn,
                "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "sssi", $name, $email, $phone, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // The sidebar prints the name from the session, so refresh it.
            $_SESSION["full_name"] = $name;
            $message = "Your details have been saved.";
        }
    }
}

// ---------- change password ----------
if (isset($_POST["save_password"])) {

    $current = $_POST["current_password"];
    $new     = $_POST["new_password"];
    $again   = $_POST["confirm_password"];

    if ($current == "" || $new == "" || $again == "") {
        $error = "Please fill in all three password fields.";

    } elseif ($new != $again) {
        $error = "The two new passwords do not match.";

    } elseif (strlen($new) < 4) {
        $error = "The new password is too short.";

    } else {

        $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$row || !password_verify($current, $row["password"])) {
            $error = "Your current password is wrong.";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "si", $hash, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $message = "Your password has been changed.";
        }
    }
}

// ---------- current values ----------
$stmt = mysqli_prepare($conn, "SELECT full_name, email, phone, role, created_at FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);
$me = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$pageTitle = "My Account";
include "header.php";
?>

<div class="page-head">
    <div>
        <h1>My account</h1>
        <p class="muted">You are signed in as a
           <span class="role role-<?php echo $me["role"]; ?>"><?php echo $me["role"]; ?></span>.</p>
    </div>
</div>

<?php if ($message != ""): ?>
    <div class="alert-ok"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error != ""): ?>
    <div class="alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" class="card">
    <h2 class="nav-label">My details</h2>

    <label for="full_name">Full name</label>
    <input type="text" id="full_name" name="full_name" value="<?php echo $me["full_name"]; ?>">

    <div class="form-grid">
        <div>
            <label for="email">Email</label>
            <input type="text" id="email" name="email" value="<?php echo $me["email"]; ?>">
        </div>
        <div>
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?php echo $me["phone"]; ?>">
        </div>
    </div>

    <input type="submit" name="save_details" value="Save details" class="btn btn-block">
</form>

<form method="POST" class="card">
    <h2 class="nav-label">Change password</h2>

    <label for="current_password">Current password</label>
    <input type="password" id="current_password" name="current_password">

    <div class="form-grid">
        <div>
            <label for="new_password">New password</label>
            <input type="password" id="new_password" name="new_password">
        </div>
        <div>
            <label for="confirm_password">Repeat new password</label>
            <input type="password" id="confirm_password" name="confirm_password">
        </div>
    </div>

    <input type="submit" name="save_password" value="Change password" class="btn btn-block">
</form>

<?php include "footer.php"; ?>
