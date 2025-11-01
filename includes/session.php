<?php


// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


 //Check if user is logged in

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

//Check if user has specific role
 
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}


//Get current user ID

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role

function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}


//Get current user data

function getCurrentUser() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['user_name'] ?? null,
        'email' => $_SESSION['user_email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null
    ];
}

//Set user session data

function setUserSession($id, $name, $email, $role) {
    $_SESSION['user_id'] = $id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;
}

//Clear user session

function clearUserSession() {
    session_unset();
    session_destroy();
}

//Redirect if not logged in
 
function requireLogin($redirectTo = 'login.php') {
    if (!isLoggedIn()) {
        header("Location: $redirectTo");
        exit;
    }
}


//Redirect if doesn't have required role

function requireRole($requiredRole, $redirectTo = 'index.php') {
    if (!hasRole($requiredRole)) {
        header("Location: $redirectTo");
        exit;
    }
}


//Set flash message
 
function setMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

//Get and clear flash message

function getMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}
?>
