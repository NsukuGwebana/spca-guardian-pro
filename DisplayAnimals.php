<?php
require_once __DIR__ . '/config.php';
// Database connection
$servername = "IS3-DEV.ICT.RU.AC.ZA";
$username = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $sql = "DELETE FROM animals WHERE id= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']); // Refresh the page
    exit();
}

// Fetch all records 
$sql = "SELECT * FROM animals";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Animal Records</title>
   
</head>
<body>
    <h1>Animal Records</h1>
    
    <a href="RegisterAnimal.html" class="back-btn">Back to Registration Form</a>
    
    <table>
        <thead>
            <tr>

                <th>Animal ID</th>
                <th>Name</th>
                <th>Breed</th>
                <th>Age</th>
                <th>Animal Color</th>
                <th>Kennel Status</th>
                <th>Height</th>
                <th>Weight</th>
                <th>Health Status</th>
                <th>Intake Date</th>
                <th>Status</th>
                <th>animal picture</th>
                <th>species</th>
                <th>Width</th>
                
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>".$row['id']."</td>
                            <td>".$row['Aname']."</td>
                            <td>".$row['Animal_breed']."</td>
                            <td>".$row['Aage']."</td>
                            <td>".$row['Color']."</td>
                            <td>".$row['Kennel_status']."</td>
                            <td>".$row['height']."</td>
                            <td>".$row['Aweight']."</td>
                            <td>".$row['Health_status']."</td>
                            <td>".$row['Intake_date']."</td>
                            <td>".$row['status']."</td>
                            <td>".$row['animal_picture']."</td>
                            <td>".$row['species']."</td>
                            <td>".$row['Width']."</td>

                            <td class='action-btns'>
                                <a href='EditAnimal.php?id=".$row['dog_id']."' class='btn update-btn'>Update</a>
                                
                                <a href='ShowAnimal_profile.php?id=".$row['dog_id']."' class='btn profile-btn'>View animal profile</a>
                                
                                <a href='?delete_id=".$row['dog_id']."' class='btn delete-btn' 
                                   onclick='return confirm(\"Are you sure you want to delete this record?\")'>Delete</a>
                            </td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='11'>No records found</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>

<?php
$conn->close();
?>