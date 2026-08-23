<?php

// This file sends the workshop list to the browser in JSON format

include "db.php";

header("Content-Type: application/json");

$workshops = array();

$sql = "SELECT id, title, instructor, schedule, seats FROM workshops ORDER BY id";

$result = mysqli_query($conn, $sql);

if (!$result) {

    echo json_encode(array(
        "status" => "error",
        "message" => "Could not load workshops."
    ));

    exit();
}

// Put every row into an array
while ($row = mysqli_fetch_assoc($result)) {
    $workshops[] = $row;
}

echo json_encode(array(
    "status" => "success",
    "workshops" => $workshops
));

mysqli_close($conn);

?>
