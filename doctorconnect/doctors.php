<?php

include "auth.php";

require_any_role(array("patient", "receptionist"));

$search = isset($_GET["search"]) ? test_input($_GET["search"]) : "";
$dept   = isset($_GET["dept"])   ? test_input($_GET["dept"])   : "";

$sql = "SELECT d.doctor_id, u.full_name, dep.dept_name, d.specialization,
               d.consultation_fee, d.available_time, d.room
        FROM doctors d
        JOIN users u         ON d.user_id = u.user_id
        JOIN departments dep ON d.dept_id = dep.dept_id
        WHERE 1";

$params = array();
$types  = "";

if ($search != "") {
    $sql .= " AND u.full_name LIKE ?";
    $params[] = "%" . $search . "%";
    $types   .= "s";
}
if ($dept != "") {
    $sql .= " AND dep.dept_id = ?";
    $params[] = $dept;
    $types   .= "i";
}
$sql .= " ORDER BY u.full_name";

$stmt = mysqli_prepare($conn, $sql);

if (count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$doctors = mysqli_stmt_get_result($stmt);

$deptList = mysqli_query($conn, "SELECT * FROM departments ORDER BY dept_name");

$pageTitle = "Find Doctors";
include "header.php";
?>

<div class="page-head">
    <h1>Find a doctor</h1>
    <p>Search by name, or filter by department.</p>
</div>

<div class="card">

    <form method="get" action="doctors.php" class="search-form">
        <input type="text" name="search" placeholder="Search by doctor name..."
               value="<?php echo $search; ?>">

        <select name="dept">
            <option value="">All departments</option>
            <?php while ($d = mysqli_fetch_assoc($deptList)): ?>
                <option value="<?php echo $d["dept_id"]; ?>"
                    <?php echo ($dept == $d["dept_id"]) ? "selected" : ""; ?>>
                    <?php echo $d["dept_name"]; ?>
                </option>
            <?php endwhile; ?>
        </select>

        <input type="submit" value="Search" class="btn">
        <?php if ($search != "" || $dept != ""): ?>
            <a href="doctors.php" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if (mysqli_num_rows($doctors) == 0): ?>
    <div class="card">
        <p class="empty">No doctors matched your search. <a href="doctors.php">Show all doctors</a>.</p>
    </div>
<?php else: ?>

    <div class="doctor-grid">
    <?php while ($doc = mysqli_fetch_assoc($doctors)): ?>
        <div class="card doctor-card">
            <div class="doctor-avatar">
                <?php

                    $clean = str_replace("Dr. ", "", $doc["full_name"]);
                    $parts = explode(" ", $clean);
                    $ini = substr($parts[0], 0, 1);
                    if (count($parts) > 1) {
                        $ini .= substr($parts[count($parts) - 1], 0, 1);
                    }
                    echo strtoupper($ini);
                ?>
            </div>
            <div class="doctor-info">
                <h3><?php echo $doc["full_name"]; ?></h3>
                <p class="doctor-meta">
                    <span class="pill"><?php echo $doc["dept_name"]; ?></span>
                    <?php echo $doc["specialization"]; ?>
                </p>
                <p class="doctor-meta">
                    Fee: <strong><?php echo $doc["consultation_fee"]; ?> Tk</strong>
                    &middot; Available: <?php echo $doc["available_time"]; ?>
                    <?php if ($doc["room"] != ""): ?>
                        &middot; Room <?php echo $doc["room"]; ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="doctor-action">
                <?php if ($_SESSION["role"] == "receptionist"): ?>

                    <a href="walkin.php?doctor_id=<?php echo $doc["doctor_id"]; ?>" class="btn">Book walk-in</a>
                <?php else: ?>

                    <a href="book.php?doctor_id=<?php echo $doc["doctor_id"]; ?>" class="btn">Book</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php
mysqli_stmt_close($stmt);
include "footer.php";
?>
