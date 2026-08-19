<?php
session_start();                         // resume the session started on page 1
require_once __DIR__ . '/includes/config.php';

// Step guard: personal information must already be in the session.
if (!has_personal_info()) {
    redirect('index.php');
}

$student = $_SESSION['student'];         // read step 1 back out of $_SESSION
$errors  = [];

$form = [
    'semester'          => $_SESSION['academic']['semester']          ?? '',
    'courses'           => $_SESSION['academic']['courses']           ?? [],
    'completed_credits' => $_SESSION['academic']['completed_credits'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['semester']          = trim($_POST['semester'] ?? '');
    $form['courses']           = isset($_POST['courses']) && is_array($_POST['courses']) ? $_POST['courses'] : [];
    $form['completed_credits'] = trim($_POST['completed_credits'] ?? '');

    if ($form['semester'] === '') {
        $errors['semester'] = 'Please choose a semester.';
    } elseif (!in_array($form['semester'], SEMESTERS, true)) {
        $errors['semester'] = 'Unknown semester selected.';
    }

    // Keep only codes that really exist in the catalogue.
    $form['courses'] = array_values(array_filter(
        $form['courses'],
        static fn($code) => array_key_exists($code, COURSES)
    ));

    $selectedCredits = total_credits($form['courses']);

    if (!$form['courses']) {
        $errors['courses'] = 'Select at least one course.';
    } elseif ($selectedCredits < MIN_CREDITS) {
        $errors['courses'] = 'A minimum of ' . MIN_CREDITS . ' credits is required.';
    } elseif ($selectedCredits > MAX_CREDITS) {
        $errors['courses'] = 'You selected ' . $selectedCredits . ' credits. The limit is ' . MAX_CREDITS . '.';
    }

    if ($form['completed_credits'] === '') {
        $errors['completed_credits'] = 'Enter the credits you have completed so far.';
    } elseif (!ctype_digit($form['completed_credits']) || (int) $form['completed_credits'] > 160) {
        $errors['completed_credits'] = 'Enter a whole number between 0 and 160.';
    }

    if (!$errors) {
        // Step 2 goes into the session next to step 1.
        $_SESSION['academic'] = [
            'semester'          => $form['semester'],
            'courses'           => $form['courses'],
            'credits_selected'  => $selectedCredits,
            'completed_credits' => (int) $form['completed_credits'],
            'saved_at'          => date('d M Y, h:i A'),
        ];

        redirect('review.php');
    }
}

$pageTitle   = 'Academic Information';
$currentStep = 2;
require __DIR__ . '/includes/header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <strong>Please fix the highlighted field<?= count($errors) > 1 ? 's' : '' ?> below.</strong>
    </div>
<?php endif; ?>

<section class="card card-recall">
    <h2 class="recall-title">Retrieved from <code>$_SESSION</code></h2>
    <div class="recall-grid">
        <div><span class="recall-label">Student ID</span><span class="recall-value"><?= e($student['student_id']) ?></span></div>
        <div><span class="recall-label">Full Name</span><span class="recall-value"><?= e($student['full_name']) ?></span></div>
        <div><span class="recall-label">Email</span><span class="recall-value"><?= e($student['email']) ?></span></div>
        <div><span class="recall-label">Department</span><span class="recall-value"><?= e(DEPARTMENTS[$student['department']]) ?></span></div>
    </div>
    <p class="recall-foot">
        Saved at <?= e($student['saved_at']) ?> &middot;
        <a href="index.php">Edit these details</a>
    </p>
</section>

<section class="card">
    <div class="card-head">
        <h1>Step 2 &mdash; Academic Information</h1>
        <p>Choose the semester and the courses you want to register for.</p>
    </div>

    <form method="post" action="academic.php" class="form" novalidate>
        <div class="form-grid">
            <div class="field">
                <label for="semester">Semester <span class="req">*</span></label>
                <select id="semester" name="semester"
                        class="<?= isset($errors['semester']) ? 'is-invalid' : '' ?>">
                    <option value="">-- Select semester --</option>
                    <?php foreach (SEMESTERS as $semester): ?>
                        <option value="<?= e($semester) ?>" <?= $form['semester'] === $semester ? 'selected' : '' ?>>
                            <?= e($semester) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['semester'])): ?>
                    <span class="error"><?= e($errors['semester']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="completed_credits">Credits Completed So Far <span class="req">*</span></label>
                <input type="number" id="completed_credits" name="completed_credits"
                       min="0" max="160" step="1"
                       value="<?= e($form['completed_credits']) ?>"
                       placeholder="45"
                       class="<?= isset($errors['completed_credits']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['completed_credits'])): ?>
                    <span class="error"><?= e($errors['completed_credits']) ?></span>
                <?php else: ?>
                    <span class="hint">Total credits earned in previous semesters.</span>
                <?php endif; ?>
            </div>
        </div>

        <fieldset class="courses">
            <legend>
                Course Selection <span class="req">*</span>
                <span class="legend-note">Minimum <?= MIN_CREDITS ?> credits, maximum <?= MAX_CREDITS ?> credits</span>
            </legend>

            <?php if (isset($errors['courses'])): ?>
                <span class="error error-block"><?= e($errors['courses']) ?></span>
            <?php endif; ?>

            <div class="course-grid">
                <?php foreach (COURSES as $code => $course): ?>
                    <?php $checked = in_array($code, $form['courses'], true); ?>
                    <label class="course <?= $checked ? 'course-on' : '' ?>">
                        <input type="checkbox" name="courses[]" value="<?= e($code) ?>" <?= $checked ? 'checked' : '' ?>>
                        <span class="course-body">
                            <span class="course-code"><?= e($code) ?></span>
                            <span class="course-title"><?= e($course['title']) ?></span>
                        </span>
                        <span class="course-credit"><?= (int) $course['credit'] ?> cr</span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <div class="form-actions form-actions-split">
            <a href="index.php" class="btn btn-secondary">&larr; Back</a>
            <button type="submit" class="btn btn-primary">Save &amp; Review &rarr;</button>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
