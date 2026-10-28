<?php
session_start();
include "databaseConnection.php";

// Mark all as read
$conn->query("UPDATE animals SET status='read' WHERE status='unread'");

// Display animals
$result = $conn->query("SELECT * FROM animals");
while ($row = $result->fetch_assoc()) {
    echo $row['name'] . " - " . $row['type'] . "<br>";
}
?>

