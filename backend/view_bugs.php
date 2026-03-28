<?php
include "db_functions.php";

// Connect to the database and fetch all bug reports
$connection = connectDB();
$result = mysqli_query($connection, "SELECT * FROM bugs");

?>

<html>
<body>

<h1>Bug List</h1>

<table border="1">
<tr>
    <th>ID</th>
    <th>Title</th>
    <th>Description</th>
    <th>Category</th>
    <th>Priority</th>
    <th>Status</th>
    <th>Created</th>
</tr>

<?php
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>".$row['id']."</td>";
    echo "<td>".$row['title']."</td>";
    echo "<td>".$row['description']."</td>";
    echo "<td>".$row['category']."</td>";
    echo "<td>".$row['priority']."</td>";
    echo "<td>".$row['status']."</td>";
    echo "<td>".$row['created_at']."</td>";
    echo "</tr>";
}
?>

</table>

</body>
</html>