<?php
include("databaseConnection.php");

// Start session to store user info if needed
session_start();

// Hardcoded example users (replace with database check in production)
$users = [
    "admin" => "admin123",
    "user1" => "password1"
];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $username = trim($_POST['username']);
    $password = trim($_POST['userpassword']);

    // Simple login check
    if (isset($users[$username]) && $users[$username] === $password) {
        // Successful login
        $_SESSION['username'] = $username;
        echo "<h3>Welcome, $username!</h3>";
        echo "<a href='logoutForm.php'>Logout</a>";
    } else {
        // Invalid login
        echo "<h3 style='color:red;'>Invalid username or password.</h3>";
        echo "<a href='Newlogin.html'>Go back to login</a>";
    }
} else {
    // Redirect to form if accessed directly
    header("Location: loginFormSysdev.html");
    exit();
}
?>
