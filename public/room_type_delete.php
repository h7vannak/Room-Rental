<?php
include '../includes/db.php';

$id = $_GET['id'];

/* IMPORTANT:
   Prevent delete if room type is used by rooms */
$check = $pdo->prepare("SELECT COUNT(*) FROM room WHERE room_type_id=?");
$check->execute([$id]);

if ($check->fetchColumn() == 0) {
    $stmt = $pdo->prepare("DELETE FROM room_type WHERE room_type_id=?");
    $stmt->execute([$id]);
}

header('Location: room_types.php');
exit;
