<?php
// Shared configuration, catalogue data and helper functions.
// NOTE: session_start() is deliberately NOT called here. Every page calls it
// itself so the use of session_start() is visible on each page.

const PORTAL_NAME  = 'Metropolitan University Portal';
const COOKIE_NAME  = 'remembered_student_id';
const COOKIE_DAYS  = 30;
const MIN_CREDITS  = 3;
const MAX_CREDITS  = 15;

const DEPARTMENTS = [
    'CSE'    => 'Computer Science & Engineering',
    'EEE'    => 'Electrical & Electronic Engineering',
    'CE'     => 'Civil Engineering',
    'BBA'    => 'Business Administration',
    'ARCH'   => 'Architecture',
    'PHARM'  => 'Pharmacy',
    'ENG'    => 'English',
];

const SEMESTERS = [
    'Spring 2026',
    'Summer 2026',
    'Fall 2026',
];

const COURSES = [
    'CSC3215' => ['title' => 'Web Technologies',            'credit' => 3],
    'CSC3217' => ['title' => 'Artificial Intelligence',     'credit' => 3],
    'CSC3225' => ['title' => 'Software Quality & Testing',  'credit' => 3],
    'CSC2106' => ['title' => 'Database Management Systems', 'credit' => 3],
    'CSC1102' => ['title' => 'Data Structures',             'credit' => 3],
    'MAT2101' => ['title' => 'Linear Algebra',              'credit' => 3],
    'EEE2215' => ['title' => 'Engineering Ethics',          'credit' => 2],
    'ENG1101' => ['title' => 'Composition & Communication', 'credit' => 3],
];

// Escape anything before printing it into HTML.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $page): void
{
    header('Location: ' . $page);
    exit;
}

// Step guards - each step needs the previous one to be in $_SESSION.
function has_personal_info(): bool
{
    return isset($_SESSION['student']) && is_array($_SESSION['student']);
}

function has_academic_info(): bool
{
    return isset($_SESSION['academic']) && is_array($_SESSION['academic']);
}

function is_completed(): bool
{
    return isset($_SESSION['registration']['reference']);
}

// Remembered ID comes from the cookie, so it survives a session_destroy().
function remembered_student_id(): string
{
    return isset($_COOKIE[COOKIE_NAME]) ? trim($_COOKIE[COOKIE_NAME]) : '';
}

function total_credits(array $courseCodes): int
{
    $total = 0;
    foreach ($courseCodes as $code) {
        if (isset(COURSES[$code])) {
            $total += COURSES[$code]['credit'];
        }
    }
    return $total;
}
