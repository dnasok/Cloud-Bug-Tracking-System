<?php
/**
 * File: backend/db_functions.php
 * Purpose: Shared database connection and helper functions for users/bugs tables.
 */
include "inc/dbinfo.inc";

/**
 * Create and return a MySQL database connection using the credentials
 * defined in dbinfo.inc.
 *
 * @return mysqli Active database connection
 */
function connectDB() {
    $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

    if (mysqli_connect_errno()) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    mysqli_set_charset($connection, "utf8mb4");
    return $connection;
}

/**
 * Create the users table if it does not already exist.
 * This table stores account details for login, signup, and role-based access.
 *
 * Columns:
 * - id: unique user ID
 * - fullname: user's full name
 * - email: unique email address
 * - username: unique login username
 * - password_hash: securely hashed password
 * - role: user role (user, developer, admin)
 * - created_at: account creation timestamp
 *
 * @param mysqli $connection Active database connection
 * @return void
 */
function createUsersTable($connection) {
    $query = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fullname VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($connection, $query);
}

/**
 * Insert a new user into the users table.
 * The password is hashed before storage for security.
 *
 * Returns false if the insert fails, such as when the username
 * or email already exists.
 *
 * @param mysqli $connection Active database connection
 * @param string $fullname User's full name
 * @param string $email User's email address
 * @param string $username Desired username
 * @param string $password Plain-text password to be hashed
 * @param string $role User role, defaults to 'user'
 * @return bool True on success, false on failure
 */
function createUser($connection, $fullname, $email, $username, $password, $role = 'user') {
    createUsersTable($connection);

    $allowedRoles = array('user', 'developer', 'admin');
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'user';
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare(
        $connection,
        "INSERT INTO users (fullname, email, username, password_hash, role)
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "sssss", $fullname, $email, $username, $passwordHash, $role);

    try {
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    } catch (mysqli_sql_exception $e) {
        mysqli_stmt_close($stmt);
        return false;
    }
}

/**
 * Retrieve one user row by username.
 * This is mainly used during login so the backend can verify
 * the submitted password against the stored password hash.
 *
 * @param mysqli $connection Active database connection
 * @param string $username Username to search for
 * @return array|false Associative user row if found, otherwise false
 */
function getUserByUsername($connection, $username) {
    createUsersTable($connection);

    $stmt = mysqli_prepare(
        $connection,
        "SELECT id, fullname, email, username, password_hash, role
         FROM users
         WHERE username = ? LIMIT 1"
    );

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    return $user;
}

/**
 * Create the bugs table if it does not already exist.
 * This table stores bug reports submitted through the frontend.
 *
 * Columns:
 * - id: unique bug ID
 * - title: bug title
 * - description: detailed bug description
 * - category: classified bug category
 * - priority: assigned priority level
 * - severity: user-selected severity
 * - status: current bug status
 * - screenshot_url: optional screenshot link
 * - assigned_to: optional assigned developer name
 * - created_at: bug creation timestamp
 * - updated_at: last update timestamp
 *
 * @param mysqli $connection Active database connection
 * @return void
 */
function createBugTable($connection) {
    $query = "CREATE TABLE IF NOT EXISTS bugs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        category VARCHAR(50) DEFAULT 'General',
        priority VARCHAR(20) DEFAULT 'Low',
        severity VARCHAR(20) DEFAULT 'Low',
        status VARCHAR(50) DEFAULT 'Open',
        screenshot_url VARCHAR(500) DEFAULT NULL,
        assigned_to VARCHAR(120) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($connection, $query);
}

/**
 * Insert a new bug report into the bugs table.
 * This function is used by the older form-based submission flow.
 *
 * @param mysqli $connection Active database connection
 * @param string $title Bug title
 * @param string $description Bug description
 * @param string $category Bug category
 * @param string $priority Bug priority
 * @return void
 */
function insertBug($connection, $title, $description, $category, $priority) {
    $stmt = mysqli_prepare(
        $connection,
        "INSERT INTO bugs (title, description, category, priority)
         VALUES (?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param($stmt, "ssss", $title, $description, $category, $priority);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>