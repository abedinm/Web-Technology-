<?php

include "auth.php";
require_role("admin");

$error   = "";
$message = "";

if (isset($_POST["add"])) {
    $name = test_input($_POST["dept_name"]);

    if ($name == "") {
        $error = "Please type a department name.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT dept_id FROM departments WHERE dept_name = ?");
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        $exists = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($exists) {
            $error = "That department already exists.";
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO departments (dept_name) VALUES (?)");
            mysqli_stmt_bind_param($stmt, "s", $name);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            $message = "Department added.";
        }
    }
}

if (isset($_POST["rename"])) {
    $deptId = (int) $_POST["dept_id"];
    $name   = test_input($_POST["dept_name"]);

    if ($name == "") {
        $error = "The name cannot be empty.";
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE departments SET dept_name = ? WHERE dept_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $name, $deptId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $message = "Department renamed.";
    }
}

if (isset($_POST["remove"])) {
    $deptId = (int) $_POST["dept_id"];

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM doctors WHERE dept_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $deptId);
    mysqli_stmt_execute($stmt);
    $used = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($used["total"] > 0) {
        $error = "This department still has " . $used["total"] . " doctor(s) and cannot be deleted.";
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM departments WHERE dept_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $deptId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $message = "Department deleted.";
    }
}

$departments = mysqli_query($conn,
    "SELECT dep.dept_id, dep.dept_name, COUNT(d.doctor_id) AS doctors
     FROM departments dep
     LEFT JOIN doctors d ON d.dept_id = dep.dept_id
     GROUP BY dep.dept_id, dep.dept_name
     ORDER BY dep.dept_name");

$pageTitle = "Departments";
include "header.php";
?>

<div class="page-head">
    <div>
        <h1>Departments</h1>
        <p class="muted">Patients filter doctors by these names, so keep them short and clear.</p>
    </div>
</div>

<?php if ($message != ""): ?>
    <div class="alert-ok"><?php echo $message; ?></div>
<?php endif; ?>

<?php if ($error != ""): ?>
    <div class="alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" class="card">
    <h2 class="nav-label">Add a department</h2>
    <label for="dept_name">Name</label>
    <input type="text" id="dept_name" name="dept_name" placeholder="e.g. Neurology">
    <input type="submit" name="add" value="Add department" class="btn btn-block">
</form>

<div class="card">
    <h2 class="nav-label">All departments</h2>

    <?php if (mysqli_num_rows($departments) == 0): ?>
        <p class="empty">No departments yet.</p>
    <?php else: ?>

    <div class="table-wrap">
    <table>
        <tr><th>Name</th><th>Doctors</th><th>Save</th><th>Delete</th></tr>

        <?php while ($d = mysqli_fetch_assoc($departments)): ?>
        <tr>
            <td>
                <form method="POST" class="inline-form">
                    <input type="hidden" name="dept_id" value="<?php echo $d["dept_id"]; ?>">
                    <input type="text" name="dept_name" value="<?php echo $d["dept_name"]; ?>">
            </td>
            <td><?php echo $d["doctors"]; ?></td>
            <td>
                    <input type="submit" name="rename" value="Save" class="btn btn-small">
                </form>
            </td>
            <td>
                <form method="POST" class="inline-form"
                      onsubmit="return confirm('Delete this department?')">
                    <input type="hidden" name="dept_id" value="<?php echo $d["dept_id"]; ?>">
                    <input type="submit" name="remove" value="Delete" class="btn btn-small btn-danger">
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    </div>

    <?php endif; ?>
</div>

<?php include "footer.php"; ?>
