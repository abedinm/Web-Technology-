<?php
// ============================================================
// logout.php - END THE SESSION
//
// session_unset()   empties $_SESSION for this request
// session_destroy() removes the session file on the server
//
// Both are needed: unset clears the data, destroy removes the
// session itself. After this the user is a stranger again.
// ============================================================
session_start();

session_unset();
session_destroy();

header("Location: login.php");
exit();   // always stop the script after a redirect
