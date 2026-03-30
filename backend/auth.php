<?php
/**
 * File: backend/auth.php
 * Purpose: Authentication API for signup, login, logout, and session validation.
 */
session_start();
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include "db_functions.php";

$connection = connectDB();
createUsersTable($connection);

/**
 * Parses JSON request body and returns an array.
 *
 * @return array Decoded body or empty array when unavailable/invalid
 */
function getJsonBody() {
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return array();
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return array();
    }

    return is_array($decoded) ? $decoded : array();
}

$input = !empty($_POST) ? $_POST : getJsonBody();
$action = isset($input['action']) ? $input['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

/**
 * Sends a standardized JSON error response and stops execution.
 *
 * @param int $statusCode HTTP status code
 * @param string $message Human-readable error message
 * @param array $extra Optional extra response fields
 * @return void
 */
function sendErrorResponse($statusCode, $message, $extra = array()) {
    http_response_code($statusCode);
    echo json_encode(array_merge(array(
        "success" => false,
        "message" => $message
    ), $extra));
    exit;
}

/**
 * Sends a standardized JSON success/failure response and stops execution.
 *
 * @param bool $success Response success flag
 * @param string $message Human-readable status message
 * @param array $extra Optional extra response fields
 * @return void
 */
function sendResponse($success, $message, $extra = array()) {
    if ($success) {
        http_response_code(200);
    }

    echo json_encode(array_merge(array(
        "success" => $success,
        "message" => $message
    ), $extra));
    exit;
}

if ($action === 'signup') {
    $fullname = isset($input['fullname']) ? trim($input['fullname']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $username = isset($input['username']) ? trim($input['username']) : '';
    $password = isset($input['password']) ? $input['password'] : '';
    $role = isset($input['role']) ? trim($input['role']) : 'user';

    if ($fullname === '' || $email === '' || $username === '' || $password === '') {
        sendErrorResponse(400, "All required fields must be provided.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendErrorResponse(400, "Invalid email format.");
    }

    if (strlen($password) < 6) {
        sendErrorResponse(400, "Password must be at least 6 characters.");
    }

    $created = createUser($connection, $fullname, $email, $username, $password, $role);
    if (!$created) {
        sendErrorResponse(409, "Unable to create account. Username or email may already exist.");
    }

    sendResponse(true, "Account created successfully.");
}

if ($action === 'login') {
    $username = isset($input['username']) ? trim($input['username']) : '';
    $password = isset($input['password']) ? $input['password'] : '';

    if ($username === '' || $password === '') {
        sendErrorResponse(400, "Username and password are required.");
    }

    $user = getUserByUsername($connection, $username);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        sendErrorResponse(401, "Invalid username or password.");
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
    sendErrorResponse(401, "No active session.");
}

sendErrorResponse(400, "Invalid action.");
