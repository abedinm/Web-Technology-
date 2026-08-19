<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// Both earlier steps must be present in the session.
if (!has_personal_info()) {
    redirect('index.php');
}
if (!has_academic_info()) {
    redirect('academic.php');
}

$student  = $_SESSION['student'];
$academic = $_SESSION['academic'];
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['declaration'])) {
        $errors['declaration'] = 'You must confirm the declaration before completing registration.';
    }

    if (!$errors) {
        $_SESSION['registration'] = [
            'reference'    => 'REG-' . strtoupper(substr(md5($student['student_id'] . $academic['semester']), 0, 8)),
            'completed_at' => date('d M Y, h:i A'),
        ];

        redirect('complete.php');
    }
}

$pageTitle   = 'Review & Confirm';
$currentStep = 3;
require __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error"><strong><?= e(reset($errors)) ?></strong></div>
<?php endif; ?>

<section class="card">
    <div class="card-head">
        <h1>Step 3 &mdash; Review Stored Data</h1>
        <p>Everything below was read back out of <code>$_SESSION</code> and <code>$_COOKIE</code>.</p>
    </div>

    <h2 class="section-title">Session data <span class="tag tag-session">$_SESSION</span></h2>
    <table class="data-table">
        <tbody>
            <tr><th>Student ID</th><td><?= e($student['student_id']) ?></td></tr>
            <tr><th>Full Name</th><td><?= e($student['full_name']) ?></td></tr>
            <tr><th>Email</th><td><?= e($student['email']) ?></td></tr>
            <tr><th>Department</th><td><?= e(DEPARTMENTS[$student['department']]) ?> (<?= e($student['department']) ?>)</td></tr>
            <tr><th>Semester</th><td><?= e($academic['semester']) ?></td></tr>
            <tr>
                <th>Selected Courses</th>
                <td>
                    <ul class="course-list">
                        <?php foreach ($academic['courses'] as $code): ?>
                            <li>
                                <strong><?= e($code) ?></strong> &mdash; <?= e(COURSES[$code]['title']) ?>
                                <span class="pill"><?= (int) COURSES[$code]['credit'] ?> cr</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </td>
            </tr>
            <tr><th>Credits This Semester</th><td><strong><?= (int) $academic['credits_selected'] ?></strong> of <?= MAX_CREDITS ?> allowed</td></tr>
            <tr><th>Credits Completed</th><td><?= (int) $academic['completed_credits'] ?></td></tr>
            <tr><th>Personal Info Saved</th><td><?= e($student['saved_at']) ?></td></tr>
            <tr><th>Academic Info Saved</th><td><?= e($academic['saved_at']) ?></td></tr>
        </tbody>
    </table>

    <h2 class="section-title">Cookie data <span class="tag tag-cookie">$_COOKIE</span></h2>
    <?php if (remembered_student_id() !== ''): ?>
        <table class="data-table">
            <tbody>
                <tr><th>Cookie Name</th><td><code><?= e(COOKIE_NAME) ?></code></td></tr>
                <tr><th>Cookie Value</th><td><?= e(remembered_student_id()) ?></td></tr>
                <tr><th>Lifetime</th><td><?= COOKIE_DAYS ?> days from the moment it was set</td></tr>
                <tr><th>Survives Logout</th><td>Yes &mdash; cookies live in the browser, not in the session</td></tr>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-info">
            No <code><?= e(COOKIE_NAME) ?></code> cookie is set. Tick
            <em>Remember my Student ID</em> on <a href="index.php">Step 1</a> to create one.
        </div>
    <?php endif; ?>

    <details class="raw-dump">
        <summary>Show raw superglobal contents</summary>
        <h3>$_SESSION</h3>
        <pre><?= e(print_r($_SESSION, true)) ?></pre>
        <h3>$_COOKIE</h3>
        <pre><?= e(print_r($_COOKIE, true)) ?></pre>
    </details>

    <form method="post" action="review.php" class="form">
        <label class="checkbox <?= isset($errors['declaration']) ? 'is-invalid' : '' ?>">
            <input type="checkbox" name="declaration" value="1">
            <span>
                <strong>I confirm the information above is correct</strong>
                <small>Once submitted, the registration slip is generated for this semester.</small>
            </span>
        </label>

        <div class="form-actions form-actions-split">
            <a href="academic.php" class="btn btn-secondary">&larr; Back</a>
            <button type="submit" class="btn btn-primary">Complete Registration</button>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
