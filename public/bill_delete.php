<?php
include '../includes/db.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM monthly_bill WHERE bill_id=?");
$stmt->execute([$id]);

header('Location: bills.php');
exit;
