<?php
require_once("databaseConnection.php");

if (isset($_POST['Update'])) {
    // store the values from the form
    $idnumber     = $_POST['idnum'];
    $username     = $_POST['username'];
    $password_raw = $_POST['pword'];

    // First, verify that the ID number and username match
    $verify_stmt = $conn->prepare("SELECT idnumber FROM users WHERE idnumber = ? AND username = ?");
    $verify_stmt->bind_param("ss", $idnumber, $username);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        // No matching record found
        echo "<div style='text-align: center; padding: 20px;'>";
        echo "<p class=\"error\">Invalid ID number or username combination. Please check your credentials.</p>";
        echo "<p><a href='ForgotPassword.html'><button style='padding: 10px 20px; background: #134f5c; color: white; border: none; border-radius: 5px; cursor: pointer;'>Retry</button></a></p>";
        echo "</div>";
    } else {
        // Matching record found, proceed with password update
        // hash the new password before saving
        $hashedPassword = password_hash($password_raw, PASSWORD_DEFAULT);

        // use prepared statement to update password
        $update_stmt = $conn->prepare("UPDATE users SET userpassword = ? WHERE idnumber = ? AND username = ?");
        $update_stmt->bind_param("sss", $hashedPassword, $idnumber, $username);

        if ($update_stmt->execute()) {
            echo "<div style='text-align: center; padding: 20px;'>";
            echo "<p class=\"success\">The password was successfully updated!</p>";
            echo "<p><a href='Newlogin.html'><button style='padding: 10px 20px; background: #134f5c; color: white; border: none; border-radius: 5px; cursor: pointer;'>Back to Login</button></a></p>";
            echo "</div>";
        } else {
            echo "<div style='text-align: center; padding: 20px;'>";
            echo "<p class=\"error\">Unable to update the record: " . $update_stmt->error . "</p>";
            echo "<p><a href='Newlogin.html'><button style='padding: 10px 20px; background: #134f5c; color: white; border: none; border-radius: 5px; cursor: pointer;'>Back to Login</button></a></p>";
            echo "</div>";
        }

        $update_stmt->close();
    }
    
    $verify_stmt->close();
    $conn->close();
}
?>