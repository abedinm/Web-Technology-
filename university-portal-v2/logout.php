<?php
session_start();
require_once __DIR__ . '/includes/config.php';

// Optional: also drop the "remember me" cookie by giving it a past expiry date.
if (isset($_GET['forget']) && remembered_student_id() !== '') {
    setcookie(COOKIE_NAME, '', [
        'expires' => time() - 3600,
        'path'    => '/',
    ]);
    unset($_COOKIE[COOKIE_NAME]);
}

session_unset();      // empties $_SESSION for the current request
session_destroy();    // deletes the session data stored on the server

// Remove the session cookie itself so the browser stops sending the old ID.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 3600,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
    ]);
}

redirect('index.php?logged_out=1');
