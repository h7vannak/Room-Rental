<?php
session_start();

/* ===============================
   LOGIN CHECK (GLOBAL)
================================ */
if (!isset($_SESSION['user'])) {
    header('Location: ../public/auth/login.php');
    exit;
}

/* ===============================
   ROLE HELPERS
================================ */
function isAdmin() {
    return $_SESSION['user']['role'] === 'admin';
}

function isStaff() {
    return $_SESSION['user']['role'] === 'staff';
}

/* ===============================
   ACCESS GUARDS
================================ */

/* Admin only */
function requireAdmin() {
    if (!isAdmin()) {
        http_response_code(403);
        echo "Access denied. Admin only.";
        exit;
    }
}

/* Staff only */
function requireStaff() {
    if (!isStaff()) {
        http_response_code(403);
        echo "Access denied. Staff only.";
        exit;
    }
}

/* Admin OR Staff */
function requireAuth() {
    if (!isAdmin() && !isStaff()) {
        http_response_code(403);
        echo "Access denied.";
        exit;
    }
}
