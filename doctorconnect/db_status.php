<?php

include "auth.php";

$tablesResult = mysqli_query($conn, "SHOW TABLES");
$tables = array();
while ($t = mysqli_fetch_array($tablesResult)) {
    $tables[] = $t[0];
}

function countRows($conn, $table) {
    $result = mysqli_query($conn, "SELECT * FROM $table");
    return mysqli_num_rows($result);
}

$deptResult = mysqli_query($conn, "SELECT * FROM departments ORDER BY dept_id");

$doctorSql = "SELECT d.doctor_id, u.full_name, u.email, dep.dept_name,
                     d.specialization, d.consultation_fee, d.available_time
              FROM doctors d
              JOIN users u        ON d.user_id = u.user_id
              JOIN departments dep ON d.dept_id = dep.dept_id
              ORDER BY d.doctor_id";
$doctorResult = mysqli_query($conn, $doctorSql);

$userResult = mysqli_query($conn, "SELECT user_id, full_name, email, role FROM users ORDER BY user_id");
?>
<?php
$pageTitle = "Database Connection";
include "header.php";
?>

<div class="page-head">
        <h1>Database Connection</h1>
        <p>CSC 3215 Web Technologies &middot; Group 6, Section F &middot; Phase 1</p>
    </div>

    <div class="status-ok">
        <strong>Connection successful.</strong>
        Connected to MySQL server at <code><?php echo $host; ?></code>
        and database <code><?php echo $dbName; ?></code>.
    </div>

    <div class="card">
        <h2>Tables created</h2>
        <p class="muted">Created by <code>db.php</code> using CREATE TABLE IF NOT EXISTS.</p>
        <div class="stat-row">
            <?php foreach ($tables as $table): ?>
                <div class="stat">
                    <span class="stat-num"><?php echo countRows($conn, $table); ?></span>
                    <span class="stat-label"><?php echo $table; ?></span>
                    <span class="stat-sub">rows</span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h2>Departments</h2>
        <p class="muted">Read back with: SELECT * FROM departments</p>
        <div class="table-wrap">

        <table>
            <tr>
                <th>ID</th>
                <th>Department Name</th>
            </tr>
            <?php while ($dept = mysqli_fetch_assoc($deptResult)): ?>
                <tr>
                    <td><?php echo $dept["dept_id"]; ?></td>
                    <td><?php echo $dept["dept_name"]; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
        </div>
    </div>

    <div class="card">
        <h2>Doctors</h2>
        <p class="muted">Three tables joined together: doctors + users + departments.</p>
        <div class="table-wrap">

        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Specialization</th>
                <th>Fee</th>
                <th>Available</th>
            </tr>
            <?php while ($doc = mysqli_fetch_assoc($doctorResult)): ?>
                <tr>
                    <td><?php echo $doc["doctor_id"]; ?></td>
                    <td><?php echo $doc["full_name"]; ?></td>
                    <td><span class="pill"><?php echo $doc["dept_name"]; ?></span></td>
                    <td><?php echo $doc["specialization"]; ?></td>
                    <td><?php echo $doc["consultation_fee"]; ?> Tk</td>
                    <td><?php echo $doc["available_time"]; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
        </div>
    </div>

    <div class="card">
        <h2>Registered Users</h2>
        <p class="muted">One table holds all three user types. The role column separates them.</p>
        <div class="table-wrap">

        <table>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
            </tr>
            <?php while ($u = mysqli_fetch_assoc($userResult)): ?>
                <tr>
                    <td><?php echo $u["user_id"]; ?></td>
                    <td><?php echo $u["full_name"]; ?></td>
                    <td><?php echo $u["email"]; ?></td>
                    <td><span class="role role-<?php echo $u["role"]; ?>"><?php echo $u["role"]; ?></span></td>
                </tr>
            <?php endwhile; ?>
        </table>
        </div>
    </div>

    <div class="card next">
    <h2>Next phase</h2>
    <p class="muted" style="margin:0">
        Registration and login with PHP sessions, doctor search by department,
        appointment booking, and the doctor and admin dashboards.
    </p>
</div>

<?php include "footer.php"; ?>
