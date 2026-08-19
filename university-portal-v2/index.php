<?php
session_start();                         // start / resume the PHP session
require_once __DIR__ . '/includes/config.php';

$errors = [];

// Values shown in the form: previously saved session data wins, otherwise the
// student ID that was remembered in the browser cookie.
$form = [
    'student_id' => $_SESSION['student']['student_id'] ?? remembered_student_id(),
    'full_name'  => $_SESSION['student']['full_name']  ?? '',
    'email'      => $_SESSION['student']['email']      ?? '',
    'department' => $_SESSION['student']['department'] ?? '',
];
$remember = remembered_student_id() !== '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['student_id'] = trim($_POST['student_id'] ?? '');
    $form['full_name']  = trim($_POST['full_name']  ?? '');
    $form['email']      = trim($_POST['email']      ?? '');
    $form['department'] = trim($_POST['department'] ?? '');
    $remember           = isset($_POST['remember_id']);

    if ($form['student_id'] === '') {
        $errors['student_id'] = 'Student ID is required.';
    } elseif (!preg_match('/^[A-Za-z0-9\-]{4,20}$/', $form['student_id'])) {
        $errors['student_id'] = 'Use 4-20 characters: letters, digits or hyphens (e.g. 22-46789-1).';
    }

    if ($form['full_name'] === '') {
        $errors['full_name'] = 'Full name is required.';
    } elseif (mb_strlen($form['full_name']) < 3 || mb_strlen($form['full_name']) > 60) {
        $errors['full_name'] = 'Full name must be between 3 and 60 characters.';
    }

    if ($form['email'] === '') {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }

    if ($form['department'] === '') {
        $errors['department'] = 'Please select a department.';
    } elseif (!array_key_exists($form['department'], DEPARTMENTS)) {
        $errors['department'] = 'Unknown department selected.';
    }

    if (!$errors) {
        // Store step 1 in the session so page 2 can read it back.
        $_SESSION['student'] = [
            'student_id' => $form['student_id'],
            'full_name'  => $form['full_name'],
            'email'      => $form['email'],
            'department' => $form['department'],
            'saved_at'   => date('d M Y, h:i A'),
        ];

        // "Remember Student ID" -> write or delete the browser cookie.
        if ($remember) {
            setcookie(COOKIE_NAME, $form['student_id'], [
                'expires'  => time() + (COOKIE_DAYS * 24 * 60 * 60),
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } elseif (remembered_student_id() !== '') {
            setcookie(COOKIE_NAME, '', [
                'expires' => time() - 3600,   // an expiry in the past deletes it
                'path'    => '/',
            ]);
        }

        redirect('academic.php');
    }
}

$pageTitle   = 'Student Information';
$currentStep = 1;
require __DIR__ . '/includes/header.php';
?>

<?php if (isset($_GET['logged_out'])): ?>
    <div class="alert alert-success">
        <strong>Registration session closed.</strong>
        Session data was cleared with <code>session_unset()</code> and <code>session_destroy()</code>.
        <?php if (remembered_student_id() !== ''): ?>
            Your Student ID is still remembered by the cookie.
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-error">
        <strong>Please fix the highlighted field<?= count($errors) > 1 ? 's' : '' ?> below.</strong>
    </div>
<?php endif; ?>

<section class="card">
    <div class="card-head">
        <h1>Step 1 &mdash; Student Information</h1>
        <p>These details are saved into <code>$_SESSION</code> and carried to the next page.</p>
    </div>

    <?php if (remembered_student_id() !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
        <div class="alert alert-info">
            Welcome back. Student ID <strong><?= e(remembered_student_id()) ?></strong>
            was loaded from the <code>$_COOKIE</code> stored on this browser.
        </div>
    <?php endif; ?>

    <form method="post" action="index.php" class="form" novalidate>
        <div class="form-grid">
            <div class="field">
                <label for="student_id">Student ID <span class="req">*</span></label>
                <input type="text" id="student_id" name="student_id"
                       value="<?= e($form['student_id']) ?>"
                       placeholder="22-46789-1"
                       class="<?= isset($errors['student_id']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['student_id'])): ?>
                    <span class="error"><?= e($errors['student_id']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="full_name">Full Name <span class="req">*</span></label>
                <input type="text" id="full_name" name="full_name"
                       value="<?= e($form['full_name']) ?>"
                       placeholder="Minhazul Abedin"
                       class="<?= isset($errors['full_name']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['full_name'])): ?>
                    <span class="error"><?= e($errors['full_name']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="email">Email Address <span class="req">*</span></label>
                <input type="email" id="email" name="email"
                       value="<?= e($form['email']) ?>"
                       placeholder="student@university.edu"
                       class="<?= isset($errors['email']) ? 'is-invalid' : '' ?>">
                <?php if (isset($errors['email'])): ?>
                    <span class="error"><?= e($errors['email']) ?></span>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="department">Department <span class="req">*</span></label>
                <select id="department" name="department"
                        class="<?= isset($errors['department']) ? 'is-invalid' : '' ?>">
                    <option value="">-- Select department --</option>
                    <?php foreach (DEPARTMENTS as $code => $name): ?>
                        <option value="<?= e($code) ?>" <?= $form['department'] === $code ? 'selected' : '' ?>>
                            <?= e($name) ?> (<?= e($code) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['department'])): ?>
                    <span class="error"><?= e($errors['department']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <label class="checkbox">
            <input type="checkbox" name="remember_id" value="1" <?= $remember ? 'checked' : '' ?>>
            <span>
                <strong>Remember my Student ID on this browser</strong>
                <small>Stores a cookie for <?= COOKIE_DAYS ?> days so the ID is filled in automatically next time.</small>
            </span>
        </label>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Save &amp; Continue &rarr;</button>
        </div>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
