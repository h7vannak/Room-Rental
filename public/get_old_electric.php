<?php
include '../includes/db.php';

$room_id = $_GET['room_id'] ?? 0;

$stmt = $pdo->prepare("SELECT new_electric FROM monthly_bill WHERE room_id = ? ORDER BY bill_month DESC LIMIT 1");
$stmt->execute([$room_id]);
$last = $stmt->fetch();

echo json_encode([
    'old_electric' => $last ? $last['new_electric'] : 0
]);
