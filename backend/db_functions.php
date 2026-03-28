<?php
include "inc/dbinfo.inc";

/*! Database connection and operations functions
 * This file contains functions to connect to the database, create the bugs table, and insert new bug reports.
 * It uses MySQLi for database interactions. Make sure to update the DB_SERVER, DB_USERNAME, DB_PASSWORD, and DB_DATABASE constants in dbinfo.inc with your database credentials.
 */
function connectDB() {
    $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_DATABASE);

    if (mysqli_connect_errno()) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    return $connection;
}

/*! Database table creation and insertion functions
 * These functions create the bugs table if it doesn't exist and insert new bug reports into the database.
 * The bugs table has fields for id, title, description, category, status, and created_at timestamp.
 */
function createBugTable($connection) {

    $query = "CREATE TABLE IF NOT EXISTS bugs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255),
        description TEXT,
        category VARCHAR(50),
        priority VARCHAR(20),
        status VARCHAR(50) DEFAULT 'Open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    mysqli_query($connection, $query);
}

/*! Insert a new bug report into the database
 * This function takes the database connection, bug title, description, category, and priority as parameters and inserts a new record into the bugs table.
 * It uses mysqli_real_escape_string to prevent SQL injection attacks.
 */
function insertBug($connection, $title, $description, $category, $priority) {

    $t = mysqli_real_escape_string($connection, $title);
    $d = mysqli_real_escape_string($connection, $description);
    $c = mysqli_real_escape_string($connection, $category);
    $p = mysqli_real_escape_string($connection, $priority);

    $query = "INSERT INTO bugs (title, description, category, priority) 
              VALUES ('$t', '$d', '$c', '$p')";

    mysqli_query($connection, $query);
}

?>