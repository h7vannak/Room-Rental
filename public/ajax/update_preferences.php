<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

requireAuth();

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['lang'])) {
    $pdo->prepare("UPDATE users SET language=? WHERE user_id=?")
        ->execute([$data['lang'], $_SESSION['user']['id']]);
    $_SESSION['user']['lang'] = $data['lang'];
}

if (isset($data['theme'])) {
    $pdo->prepare("UPDATE users SET theme=? WHERE user_id=?")
        ->execute([$data['theme'], $_SESSION['user']['id']]);
    $_SESSION['user']['theme'] = $data['theme'];
}

echo json_encode(['success' => true]);
