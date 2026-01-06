<?php
/**
 * Session Management and Verification
 * Handles user session validation and authorization
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please login.'
        ]);
        exit;
    }
}

function isSuperAdmin() {
    return isLoggedIn() && $_SESSION['role'] === 'super';
}

function isAdmin() {
    return isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super');
}

function requireSuperAdmin() {
    requireLogin();
    
    if (!isSuperAdmin()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Forbidden. Super admin access required.'
        ]);
        exit;
    }
}

function getUserSession() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'name' => $_SESSION['name'],
        'role' => $_SESSION['role']
    ];
}

function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['name'] = $user['name'];
    $_SESSION['role'] = $user['role'];
}

function destroyUserSession() {
    session_unset();
    session_destroy();
}
?>