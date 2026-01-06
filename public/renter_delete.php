<?php
include '../includes/db.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM renter WHERE renter_id=?");
$stmt->execute([$id]);

header('Location: renters.php');
exit;
