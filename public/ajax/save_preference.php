<?php
session_start();
require '../../includes/db.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $_SESSION['user']['id'];

if (isset($data['language'])) {
    $pdo->prepare("UPDATE users SET language=? WHERE user_id=?")
        ->execute([$data['language'], $userId]);

    $_SESSION['user']['lang'] = $data['language'];
}

if (isset($data['theme'])) {
    $pdo->prepare("UPDATE users SET theme=? WHERE user_id=?")
        ->execute([$data['theme'], $userId]);

    $_SESSION['user']['theme'] = $data['theme'];
}

echo json_encode(['success' => true]);
