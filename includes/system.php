<?php
// 1. Check if the connection exists, if not, include the connection file
if (!isset($conn)) {
    include __DIR__ . '/db.php';
}

// 2. Execute the query using the MySQLi object
$systemResult = $conn->query("SELECT * FROM system_info WHERE system_id = 1");

// 3. Fetch the data as an associative array
$system = $systemResult->fetch_assoc();

// 4. Check if the configuration exists
if (!$system) {
    die('System configuration missing');
}
