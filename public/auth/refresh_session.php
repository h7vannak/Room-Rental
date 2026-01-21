<?php
session_start();
// Just by opening this file, the session 'Last Activity' is updated.
echo json_encode(['status' => 'refreshed']);
?>