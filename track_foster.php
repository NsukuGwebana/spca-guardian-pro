<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$server = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$db     = DB_NAME;

$conn = new mysqli($server, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$application = null;
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_number = trim($_POST['id_number']);

    $stmt = $conn->prepare("
        SELECT f.*, ani.Aname AS animal_name, ani.Species, ani.Animal_breed
        FROM fosters f
        JOIN animals ani ON f.animal_id = ani.id
        WHERE f.id_number = ?
        ORDER BY f.created_at DESC LIMIT 1
    ");
    $stmt->bind_param("s", $id_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $application = $result->fetch_assoc();
    } else {
        $message = "No foster application found for that ID number.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Track Foster Application</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            color: #fff;
        }

        .overlay {
            background: rgba(19, 79, 92, 0.85);
            min-height: 100vh;
            padding: 30px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .tracking-box {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #e0f7fa;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        input[type="text"] {
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
        }

        button {
            background: #60a5fa;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: bold;
            color: #fff;
            cursor: pointer;
        }

        button:hover {
            background: #3b82f6;
        }

        .status-box {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }

        .Pending { background-color: rgba(255, 193, 7, 0.3); color: #ffeb3b; }
        .Approved { background-color: rgba(76, 175, 80, 0.3); color: #8bc34a; }
        .Rejected { background-color: rgba(244, 67, 54, 0.3); color: #f44336; }

        .back-btn {
            display: block;
            text-align: center;
            margin-top: 20px;
            background: #60a5fa;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
        .back-btn:hover { background: #3b82f6; }
    </style>
</head>
<body>
<div class="overlay">
    <div class="tracking-box">
        <h1>Track Your Foster Application</h1>
        <form method="POST">
            <input type="text" name="id_number" placeholder="Enter your ID number" required>
            <button type="submit">Track</button>
        </form>

        <?php if ($message): ?>
            <p style="text-align:center; margin-top:15px; color:#ffd700;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($application): ?>
            <div class="status-box <?php echo htmlspecialchars($application['status']); ?>">
                <p><strong>Animal:</strong> <?php echo htmlspecialchars($application['animal_name']); ?> (<?php echo htmlspecialchars($application['Species']); ?> / <?php echo htmlspecialchars($application['Animal_breed']); ?>)</p>
                <p><strong>Submitted:</strong> <?php echo htmlspecialchars($application['created_at']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($application['status']); ?></p>
            </div>
        <?php endif; ?>

        
        <a href="Homepage.html" class="back-btn"> Back </a>
    </div>
</div>
</body>
</html>
