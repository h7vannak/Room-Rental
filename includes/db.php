<?php
date_default_timezone_set('Asia/Phnom_Penh');

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'room_rental';

// 1. Create connection
$conn = new mysqli($host, $user, $pass, $db);

// 2. Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 3. Set charset to match your database
$conn->set_charset("utf8mb4");

// Now you can use $conn to run queries