<!DOCTYPE html>
<html lang="en">
<head>

    <title>Login Form</title>

</head>
<?php
session_start();
require "databaseConnection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['userpassword'] ?? '';

    // prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verify password
        if (password_verify($password, $row['userpassword'])) {
            // Store user info in session
            $_SESSION['access'] = "yes";
            $_SESSION['username'] = $row['username'];
            $_SESSION['userrole'] = $row['userrole'];
            $_SESSION['firstname'] = $row['firstname'];
            $_SESSION['surname'] = $row['surname'];
            $_SESSION['idnumber'] = $row['idnumber'];
            $_SESSION['LAST_ACTIVITY'] = time();

            // Redirect based on role - MUST be before any output
            switch ($row['userrole']) {
                case 'Administrator':
                    header("Location: admin dashboard.php");
                    exit();
                    break;

                case 'Vet':
                    header("Location: VetDashboard.php");
                    exit();
                    break;

                case 'Volunteer':
                    header("Location: z.php");
                    exit();
                    break;

                default:
                    // If unknown role, show message but don't redirect
                    echo "<p>Welcome back {$row['surname']} {$row['firstname']}. 
                          Your role is {$row['userrole']} and your ID number is {$row['idnumber']}.</p>";
                    echo "<p>Unknown user role. Please contact administrator.</p>";
                    echo "<p><a href='Newlogin.html'><button>Back to Login</button></a></p>";
            }
        } else {
    echo "<p style='color: #fdfdfdff; background-color: #0d3c46; border: 1px solid #ebccd1; padding: 15px; border-radius: 5px; font-family: Arial, sans-serif;'>Login failed. Invalid username or password.</p>";
    echo "<p><a href='Newlogin.html'><button style='background-color: #337ab7; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;'>Back to Login</button></a></p>";
        }
    } else {
        echo "<p style='color: #fdfcfcff; background-color: #0d3c46; border: 1px solid #ebccd1; padding: 15px; border-radius: 5px; font-family: Arial, sans-serif;'>Login failed. User not found.</p>";
        echo "<p><a href='Newlogin.html'><button style='background-color: #337ab7; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;'>Back to Login</button></a></p>";
    }

    $stmt->close();
    $conn->close();
}
?>