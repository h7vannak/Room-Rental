<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Safety check: Only admins can delete price history
if ($_SESSION['user']['role'] !== 'admin') {
    die("Unauthorized");
}

$id = $_GET['id'] ?? 0;
if ($id) {
    $stmt = $conn->prepare("DELETE FROM utility_rates WHERE rate_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: utility_rates.php");
exit;