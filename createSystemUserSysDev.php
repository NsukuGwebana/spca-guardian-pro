<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creating system user</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'TASA Explorer', sans-serif;
            text-decoration: none;
            list-style: none;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(90deg, #e2e2e2, #134f5c);
        }

        .container {
            position: relative;
            width: 850px;
            height: auto;
            background: #fff;
            margin: 20px;
            border-radius: 30px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            padding: 40px;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #134f5c;
        }

        p {
            margin: 8px 0;
            font-size: 16px;
            color: #333;
        }

        .btn {
            width: auto;
            padding: 12px 24px;
            margin-top: 20px;
            background: #134f5c;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
        }

        .btn:hover {
            background: #0d3c46;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 2000;
            background: #134f5c;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: #0d3c46;
            transform: translateY(-2px);
        }

        .back-button i {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
session_start();  // Add this at the very top
include "databaseConnection.php"; 

$surname   = $_POST["surname"];
$firstname = $_POST["firstname"];
$pword     = $_POST["pword"];
$username  = $_POST["username"];
$userrole  = $_POST["userrole"];
$phonenumber = $_POST["phonenumber"];

$hashedpassword = password_hash($pword, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (surname, firstname, username, userpassword, userrole, phonenumber) 
                        VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssss", $surname, $firstname, $username, $hashedpassword, $userrole, $phonenumber);

if ($stmt->execute()) {
    $new_id = $conn->insert_id;
    
    // Store registration data in session
    $_SESSION['last_registration'] = [
        'id' => $new_id,
        'surname' => $surname,
        'firstname' => $firstname,
        'username' => $username,
        'userrole' => $userrole,
        'phonenumber' => $phonenumber,
        'timestamp' => time()
    ];
    
    // Store success message in session (useful if redirecting)
    $_SESSION['success_message'] = "User registered successfully!";

    echo "<h2>Record successfully created</h2>";
    echo "<p><strong>Assigned ID Number:</strong> $new_id</p>";
    echo "<p><strong>Surname:</strong> $surname</p>";
    echo "<p><strong>Firstname:</strong> $firstname</p>";
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<p><strong>User Role:</strong> $userrole</p>";
    echo "<p><strong>Phone Number:</strong> $phonenumber</p>";
    echo "<p><a href='Newlogin.html'><button class='btn'>Back to Login</button></a></p>";
} else {
    // Store error in session
    $_SESSION['error_message'] = "Unable to create record: " . $stmt->error;
    
    echo "<h2 style='color:red;'>Unable to create the record</h2>";
    echo "<p>Error: " . $stmt->error . "</p>";
}

$stmt->close();
$conn->close();
?>
    </div>
</body>
</html>
