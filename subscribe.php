<?php
// Include the database connection file
include 'databaseConnection.php'; 

// Check if the form was submitted using POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if the 'email' field exists and is not empty
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $email = trim($_POST['email']);

        // Validate the email format
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Use a prepared statement for security
            $stmt = $conn->prepare("INSERT INTO spca_subscribers (email) VALUES (?)");
            $stmt->bind_param("s", $email);

            if ($stmt->execute()) {
                // Subscription was successful.
                // Redirect the user to the thank you page.
                header("Location: thank_you.html");
                exit(); // Always call exit() after a header redirect
            } else {
                // If there's an error, display it
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Invalid email address.";
        }
    } else {
        echo "Email address was not provided.";
    }
}

// Close the connection
$conn->close();
?>