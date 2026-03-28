<?php
include "inc/dbinfo.inc";
include "classify_bug.php";
include "db_functions.php";

// Enable error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connect to the database and create the bugs table if it doesn't exist
$connection = connectDB();
createBugTable($connection);

$title = "";
$description = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get the submitted title and description
    $title = $_POST['title'];
    $description = $_POST['description'];

    // Classify the bug using the AI-based classification function
    $category = classifyBug($title, $description);

    // Get the priority of the bug using the AI-based priority assignment function
    $priority = getPriority($description);

    // Insert the new bug report into the database
    insertBug($connection, $title, $description, $category, $priority);

    echo "<p>Bug submitted! Category: <b>$category</b>, Priority: <b>$priority</b></p> ";
}
?>

<html>
<body>

<h1>Submit Bug</h1>

<form method="POST">
    <input type="text" name="title" placeholder="Bug Title" required><br><br>

    <textarea name="description" placeholder="Describe the bug" required></textarea><br><br>

    <input type="submit" value="Submit Bug">
</form>

<br>
<a href="view_bugs.php">View All Bugs</a>

</body>
</html>