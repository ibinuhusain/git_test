<?php
session_start();

// Determine the base directory (two levels up from includes)
$baseDir = dirname(__DIR__);
require_once $baseDir . '/config.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Get current user info
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Login function
function login($username, $password) {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        return true;
    }
    return false;
}

// Logout function
function logout() {
    session_start(); // Start session to ensure we can destroy it

    // Clear all session data
    $_SESSION = array();

    // Delete the session cookie if it exists
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }

    // Destroy the session
    session_destroy();

    // Redirect to login page - adjust path for web hosting compatibility
    $basePath = dirname($_SERVER['SCRIPT_NAME']);
    if ($basePath === '/') {
        $basePath = '';
    }
    header("Location: {$basePath}/login.php");
    exit();
}

// Check if user has specific role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Check if user is super admin
function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

// Check if user is admin or higher
function isAdminOrHigher() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin');
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdminOrHigher()) {
        header("Location: ../index.php");
        exit();
    }
}

// Redirect if not super admin
function requireSuperAdmin() {
    if (!isSuperAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}

initializeDatabase();
?>