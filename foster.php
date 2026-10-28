<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Available Animals for Fostering</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
    color: #134f5c;
}

.overlay {
    background: rgba(19, 79, 92, 0.85);
    min-height: 100vh;
    padding: 30px 0;
}

.container {
    max-width: 1100px;
    margin: auto;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.35);
}

h1 {
    text-align: center;
    margin-bottom: 25px;
    color: #134f5c;
}

.grid {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
}

.card {
    background: #ffffff;
    border: 1px solid #134f5c;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    transition: transform 0.2s;
}

.card:hover {
    transform: translateY(-5px);
}

.card img {
    width: 100%;
    height: 170px;
    object-fit: cover;
}

.card .info {
    padding: 12px;
}

.card h3 {
    margin: 4px 0 6px;
    font-size: 1.1rem;
    color: #134f5c;
}

.muted {
    color: #3b82f6;
    font-size: 0.9rem;
}

.btn {
    display: inline-block;
    background: #134f5c;
    color: #fff;
    padding: 10px 14px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    margin-top: 10px;
}

.btn:hover {
    background: #0f3f4a;
}

.back-btn {
    display: block;
    width: fit-content;
    background: #60a5b3;
    color: #fff;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    margin: 20px auto;
    text-align: center;
    cursor: pointer;
}

.back-btn:hover {
    background: #3d8a95;
}

p.no-animals {
    text-align: center;
    font-weight: bold;
    color: #134f5c;
    font-size: 1.2rem;
}

.default-img {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 170px;
    background: #f0f0f0;
    color: #666;
    font-style: italic;
}
</style>
</head>
<body>
<div class="overlay">
<div class="container">
<h1>Your Support Can Make a Difference</h1>

<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$serverName = DB_HOST;
$username   = DB_USER;
$password   = DB_PASS;
$database   = DB_NAME;

$conn = new mysqli($serverName, $username, $password, $database);
if ($conn->connect_error) die("DB connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// Fetch animals eligible for fostering (Healthy, Available, not Adopted)
$animals = [];
$res = $conn->query("
    SELECT id, Aname, Species, Animal_breed, Aage, Color, animal_picture
    FROM animals
    WHERE Health_status = 'Healthy' AND status = 'Available' AND is_deleted = 0
    ORDER BY created_at DESC, id DESC
");
while ($row = $res->fetch_assoc()) $animals[] = $row;

$conn->close();
?>

<?php if(count($animals) === 0): ?>
    <p class="no-animals">No healthy animals are available for fostering right now.</p>
<?php else: ?>
<div class="grid">
<?php foreach($animals as $pet): ?>
    <div class="card">
        <?php if(!empty($pet['animal_picture']) && file_exists($pet['animal_picture'])): ?>
            <img src="<?php echo htmlspecialchars($pet['animal_picture']); ?>" alt="<?php echo htmlspecialchars($pet['Aname']); ?>">
        <?php else: ?>
            <div class="default-img">
                No Image Available
            </div>
        <?php endif; ?>
        <div class="info">
            <h3><?php echo htmlspecialchars($pet['Aname']); ?></h3>
            <div class="muted">
                <?php echo htmlspecialchars($pet['Species']); ?> •
                <?php echo htmlspecialchars($pet['Animal_breed']); ?><br>
                Age: <?php echo htmlspecialchars($pet['Aage']); ?> •
                Color: <?php echo htmlspecialchars($pet['Color']); ?>
            </div>
            <a class="btn" href="foster_form.php?animal_id=<?php echo $pet['id']; ?>">Foster Me</a>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Back button always visible -->
<button class="back-btn" onclick="window.history.back()"> Back</button>

</div>
</div>
</body>
</html>

