<?php

/**
 * File: backend/inc/dbinfo.inc
 * Purpose: Centralized database connection constants used by backend PHP scripts.
 */

define('DB_SERVER', getenv('DB_SERVER') ?: 'localhost');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'your_database_username');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'your_database_password');
define('DB_DATABASE', getenv('DB_DATABASE') ?: 'bug_tracker_db');

?>