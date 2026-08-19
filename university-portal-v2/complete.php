<?php
session_start();
require_once __DIR__ . '/includes/config.php';

if (!has_personal_info()) {
    redirect('index.php');
}
if (!has_academic_info()) {
    redirect('academic.php');
}
if (!is_completed()) {
    redirect('review.php');
}

$student      = $_SESSION['student'];
$academic     = $_SESSION['academic'];
$registration = $_SESSION['registration'];

$pageTitle   = 'Registration Slip';
$currentStep = 4;
require __DIR__ . '/includes/header.php';
?>

<div class="alert alert-success">
    <strong>Registration completed.</strong>
    Reference number <code><?= e($registration['reference']) ?></code> was generated on <?= e($registration['completed_at']) ?>.
</div>

<section class="card slip">
    <div class="slip-head">
        <div>
            <h1>Registration Slip</h1>
            <p class="slip-sub"><?= e(PORTAL_NAME) ?> &middot; Office of the Registrar</p>
        </div>
        <div class="slip-ref">
            <span class="slip-ref-label">Reference</span>
            <span class="slip-ref-value"><?= e($registration['reference']) ?></span>
        </div>
    </div>

    <div class="slip-grid">
        <div><span class="recall-label">Student ID</span><span class="recall-value"><?= e($student['student_id']) ?></span></div>
        <div><span class="recall-label">Name</span><span class="recall-value"><?= e($student['full_name']) ?></span></div>
        <div><span class="recall-label">Email</span><span class="recall-value"><?= e($student['email']) ?></span></div>
        <div><span class="recall-label">Department</span><span class="recall-value"><?= e(DEPARTMENTS[$student['department']]) ?></span></div>
        <div><span class="recall-label">Semester</span><span class="recall-value"><?= e($academic['semester']) ?></span></div>
        <div><span class="recall-label">Credits Registered</span><span class="recall-value"><?= (int) $academic['credits_selected'] ?></span></div>
    </div>

    <table class="data-table slip-courses">
        <thead>
            <tr><th>Course Code</th><th>Course Title</th><th class="num">Credit</th></tr>
        </thead>
        <tbody>
            <?php foreach ($academic['courses'] as $code): ?>
                <tr>
                    <td><?= e($code) ?></td>
                    <td><?= e(COURSES[$code]['title']) ?></td>
                    <td class="num"><?= (int) COURSES[$code]['credit'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total credits this semester</td>
                <td class="num"><?= (int) $academic['credits_selected'] ?></td>
            </tr>
        </tfoot>
    </table>
</section>

<section class="card card-danger">
    <div class="card-head">
        <h2>Finish Session</h2>
        <p>
            Logging out runs <code>session_unset()</code> to empty <code>$_SESSION</code> and
            <code>session_destroy()</code> to remove the session file on the server.
        </p>
    </div>

    <div class="logout-options">
        <a class="btn btn-danger" href="logout.php">Log out &amp; clear session</a>
        <a class="btn btn-outline-danger" href="logout.php?forget=1">Log out &amp; forget my Student ID</a>
    </div>

    <p class="hint">
        The first option keeps the <code><?= e(COOKIE_NAME) ?></code> cookie so the ID is filled in next time.
        The second also deletes that cookie by setting its expiry in the past.
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
