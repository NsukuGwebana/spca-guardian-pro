
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registration Result</title>
<style>
/* Consistent CSS for the form and the result page */
body {
    font-family: Arial, sans-serif;
    background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 20px;
    color: #134f5c;
}

.overlay {
    background: rgba(19, 79, 92, 0.85);
    padding: 30px;
    border-radius: 12px;
    max-width: 800px;
    margin: 0 auto;
}

.container {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.35);
    text-align: center;
}

h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #134f5c;
}

.btn {
    display: inline-block;
    background: #134f5c;
    color: #fff;
    padding: 12px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
    border: none;
    margin: 10px;
}

.btn:hover {
    background: #0f3f4a;
}

.back-btn {
    background: #60a5b3;
}

.back-btn:hover {
    background: #3d8a95;
}

.success-message {
    color: green;
    font-size: 1.2em;
    font-weight: bold;
    margin-bottom: 20px;
}

.error-message {
    color: red;
    font-size: 1.2em;
    font-weight: bold;
    margin-bottom: 20px;
}

.profile-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: left;
}

.profile-img {
    width: 200px;
    height: 200px;
    object-fit: cover;
    border-radius: 50%;
    border: 5px solid #134f5c;
    margin-bottom: 20px;
}

.profile-details {
    width: 100%;
}

.detail-item {
    padding: 8px 0;
    border-bottom: 1px dashed #ddd;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: bold;
    color: #0f3f4a;
    display: inline-block;
    width: 120px;
}
</style>
</head>
<body>
<div class="overlay">
<div class="container">
    <?php
    require_once 'databaseConnection.php';

    // Check for success or error message in URL
    if (isset($_GET['success']) && $_GET['success'] == 'true') {
        echo '<h2>Animal Registered Successfully!</h2>';
        echo '<div class="success-message">Animal profile created and kennel allocated.</div>';

        $animalId = (int)($_GET['animalId'] ?? 0);
        $sql = "SELECT * FROM animals a
                LEFT JOIN animal_kennel ak ON ak.animal_id = a.id
                LEFT JOIN kennels k ON ak.kennel_id = k.kennel_id
                WHERE a.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $animalId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $animal = $result->fetch_assoc();
            
            // Display the animal's profile
            echo '<div class="profile-card">';
            $pictureSrc = empty($animal['picture_path']) ? 'placeholder.png' : $animal['picture_path'];
            echo '<img src="' . htmlspecialchars($pictureSrc) . '" alt="Animal Photo" class="profile-img">';
            echo '<div class="profile-details">';
            echo '<div class="detail-item"><span class="detail-label">Name:</span> ' . htmlspecialchars($animal['Aname']) . '</div>';
            echo '<div class="detail-item"><span class="detail-label">Species:</span> ' . htmlspecialchars($animal['Species']) . '</div>';
            echo '<div class="detail-item"><span class="detail-label">Breed:</span> ' . htmlspecialchars($animal['Animal_breed']) . '</div>';
            echo '<div class="detail-item"><span class="detail-label">Age:</span> ' . htmlspecialchars($animal['Aage']) . ' years</div>';
            echo '<div class="detail-item"><span class="detail-label">Color:</span> ' . htmlspecialchars($animal['Color']) . '</div>';
            echo '<div class="detail-item"><span class="detail-label">Weight:</span> ' . htmlspecialchars($animal['Aweight']) . ' kg</div>';
            echo '<div class="detail-item"><span class="detail-label">Health:</span> ' . htmlspecialchars($animal['Health_status']) . '</div>';
            echo '<div class="detail-item"><span class="detail-label">Status:</span> ' . htmlspecialchars($animal['status']) . '</div>';
            echo '<div class="detail-item"><span class="detail-label">Intake Date:</span> ' . htmlspecialchars($animal['Intake_date']) . '</div>';
            echo '<div class="detail-item"><span class="detail-label">Allocated Kennel:</span> ' . htmlspecialchars($animal['kennel_name']) . '</div>';
            echo '</div>'; // close profile-details
            echo '</div>'; // close profile-card
            
        } else {
            echo '<p>Could not load animal profile.</p>';
        }

    } else {
        echo '<h2>Registration Failed</h2>';
        $errorMessage = $_GET['message'] ?? "An unknown error occurred.";
        echo '<div class="error-message">Error: ' . htmlspecialchars($errorMessage) . '</div>';
    }
    ?>
    <a href='admin dashboard.php' class="btn back-btn">Back to Dashboard</a>
    <a href='register_animal.html' class="btn back-btn">Register Another Animal</a>
</div>
</div>
</body>
</html>