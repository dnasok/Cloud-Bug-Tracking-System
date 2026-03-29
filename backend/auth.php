<?php
session_start();
header("Content-Type: application/json");

include "db_functions.php";

$connection = connectDB();
createUsersTable($connection);

$action = isset($_POST['action']) ? $_POST['action'] : '';

function sendResponse($success, $message, $extra = array()) {
    echo json_encode(array_merge(array(
        "success" => $success,
        "message" => $message
    ), $extra));
    exit;
}

if ($action === 'signup') {
    $fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'user';

    if ($fullname === '' || $email === '' || $username === '' || $password === '') {
        sendResponse(false, "All required fields must be provided.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, "Invalid email format.");
    }

    if (strlen($password) < 6) {
        sendResponse(false, "Password must be at least 6 characters.");
    }

    $created = createUser($connection, $fullname, $email, $username, $password, $role);
    if (!$created) {
        sendResponse(false, "Unable to create account. Username or email may already exist.");
    }

    sendResponse(true, "Account created successfully.");
}

if ($action === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        sendResponse(false, "Username and password are required.");
    }

    $user = getUserByUsername($connection, $username);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        sendResponse(false, "Invalid username or password.");
    }

    $_SESSION['user'] = array(
        "id" => $user['id'],
        "fullname" => $user['fullname'],
        "email" => $user['email'],
        "username" => $user['username'],
        "role" => $user['role']
    );

    sendResponse(true, "Login successful.", array("user" => $_SESSION['user']));
}

if ($action === 'logout') {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    sendResponse(true, "Logged out.");
}

if ($action === 'session') {
    if (isset($_SESSION['user'])) {
        sendResponse(true, "Session active.", array("user" => $_SESSION['user']));
    }
    sendResponse(false, "No active session.");
}

sendResponse(false, "Invalid action.");
