<?php
// ============================================================
// doctors.php - SEARCH DOCTORS  (Patient feature 1)
//
// Two search inputs: a name box and a department dropdown.
// Both arrive through $_GET, so the search stays in the URL and
// the patient can bookmark or share it - that is the difference
// between GET and POST in practice.
// ============================================================
include "auth.php";

// Patients browse this list to choose a doctor. The receptionist needs
// the same list at the front desk when a walk-in asks who is available,
// so both roles are allowed in. The Book button below changes to suit
// whichever one is looking.
require_any_role(array("patient", "receptionist"));

// $_GET values, with "" as the default on the first visit.
$search = isset($_GET["search"]) ? test_input($_GET["search"]) : "";
$dept   = isset($_GET["dept"])   ? test_input($_GET["dept"])   : "";

// Build the query in pieces so we only add the filters that were used.
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
    $params[] = "%" . $search . "%";   // % means "anything can be here"
    $types   .= "s";
}
if ($dept != "") {
    $sql .= " AND dep.dept_id = ?";
    $params[] = $dept;
    $types   .= "i";
}
$sql .= " ORDER BY u.full_name";

$stmt = mysqli_prepare($conn, $sql);

// Only bind if at least one filter was used.
if (count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$doctors = mysqli_stmt_get_result($stmt);

// The dropdown list of departments.
$deptList = mysqli_query($conn, "SELECT * FROM departments ORDER BY dept_name");

$pageTitle = "Find Doctors";
include "header.php";
?>

<div class="page-head">
    <h1>Find a doctor</h1>
    <p>Search by name, or filter by department.</p>
</div>

<div class="card">
    <!-- method="get" so the search terms appear in the URL -->
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
    <!-- Three-column card grid, as in the Figma "Find a Doctor" screen. -->
    <div class="doctor-grid">
    <?php while ($doc = mysqli_fetch_assoc($doctors)): ?>
        <div class="card doctor-card">
            <div class="doctor-avatar">
                <?php
                    // Initials from the doctor's name, e.g. "Dr. Salma Akter" -> "SA".
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
                    <!-- The receptionist books on somebody else's behalf, so
                         the button goes to the walk-in form instead. -->
                    <a href="walkin.php?doctor_id=<?php echo $doc["doctor_id"]; ?>" class="btn">Book walk-in</a>
                <?php else: ?>
                    <!-- The doctor's id travels in the URL to the booking page -->
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
