<?php
// Expects $pageTitle and $currentStep to be set by the including page.
$pageTitle   = $pageTitle   ?? PORTAL_NAME;
$currentStep = $currentStep ?? 1;

$steps = [
    1 => ['label' => 'Student Information', 'page' => 'index.php'],
    2 => ['label' => 'Academic Information', 'page' => 'academic.php'],
    3 => ['label' => 'Review & Confirm',     'page' => 'review.php'],
    4 => ['label' => 'Registration Slip',    'page' => 'complete.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> &middot; <?= e(PORTAL_NAME) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="topbar">
    <div class="topbar-inner">
        <div class="brand">
            <span class="brand-mark">UP</span>
            <div>
                <span class="brand-name"><?= e(PORTAL_NAME) ?></span>
                <span class="brand-sub">Online Course Registration</span>
            </div>
        </div>

        <div class="session-badge">
            <?php if (has_personal_info()): ?>
                <span class="dot dot-live"></span>
                <span class="session-text">
                    Session active &middot; <?= e($_SESSION['student']['student_id']) ?>
                </span>
                <a class="btn btn-ghost btn-sm" href="logout.php">Log out</a>
            <?php else: ?>
                <span class="dot"></span>
                <span class="session-text">No active session</span>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container">

    <ol class="stepper">
        <?php foreach ($steps as $number => $step): ?>
            <?php
                $state = 'todo';
                if ($number < $currentStep) {
                    $state = 'done';
                } elseif ($number === $currentStep) {
                    $state = 'active';
                }
            ?>
            <li class="step step-<?= $state ?>">
                <span class="step-number"><?= $state === 'done' ? '&check;' : $number ?></span>
                <span class="step-label"><?= e($step['label']) ?></span>
            </li>
        <?php endforeach; ?>
    </ol>
