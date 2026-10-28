<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$server = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$db     = DB_NAME;

$conn = new mysqli($server, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$message = "";

// Handle appointment status update
if(isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    if($action === 'completed') {
        // Update uses singular table name
        $stmt = $conn->prepare("UPDATE appointment SET status='Completed' WHERE appointment_id=?");
        $stmt->bind_param("i", $id);
        if($stmt->execute()) {
            $message = "Appointment marked as completed!";
        } else {
            $message = "Failed to update appointment.";
        }
        $stmt->close();
    }
}

// Fetch all appointments with adopter and animal info
$result = $conn->query("
    SELECT ap.appointment_id, ap.appointment_date, ap.notes, ap.status,
           a.full_name, a.email, ani.Aname, ani.Species, ani.Animal_breed
    FROM appointment ap
    JOIN adoptions a ON ap.adoption_id = a.id
    JOIN animals ani ON a.animal_id = ani.id
    ORDER BY ap.appointment_date DESC
");
$appointments = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Follow-Up Appointments</title>
<style>
body { 
    font-family: Arial, sans-serif; 
    background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed; 
    background-size: cover; 
    margin:0; 
    padding:0; 
    color:#fff; 
}
.overlay { 
    background: rgba(19, 79, 92, 0.85); 
    min-height: 100vh; 
    padding: 30px; 
}
h1 { 
    text-align:center; 
    margin-bottom:20px; 
    color:#e0f7fa; 
}
table { 
    width:100%; 
    border-collapse: collapse; 
    margin-top:20px; 
}
th, td { 
    border:1px solid #60a5fa; 
    padding:8px; 
    text-align:left; 
}
th { 
    background:#134f5c; 
    color:#fff; 
}
td { 
    background: rgba(255,255,255,0.1); 
}
button { 
    padding:6px 12px; 
    border:none; 
    border-radius:6px; 
    cursor:pointer; 
    font-weight:bold; 
    margin:2px; 
}
button.complete { 
    background:#4CAF50; 
    color:white; 
}
button.complete:hover { 
    background:#388e3c; 
}
p.message { 
    text-align:center; 
    font-weight:bold; 
    font-size:1.1rem; 
    color:#ffd700; 
}
.back-btn { 
    display:block; 
    width:fit-content; 
    background:#60a5fa; 
    color:#fff; 
    padding:10px 16px; 
    border-radius:6px; 
    font-weight:bold; 
    margin:20px auto; 
    text-align:center; 
    text-decoration:none; 
}
.back-btn:hover { 
    background:#3b82f6; 
}
</style>
</head>
<body>
<div class="overlay">
<h1>Follow-Up Appointments</h1>

<?php if($message): ?>
<p class="message"><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<?php if(empty($appointments)): ?>
<p style="text-align:center;">No appointments scheduled.</p>
<?php else: ?>
<table>
<tr>
<th>Animal</th>
<th>Species / Breed</th>
<th>Adopter</th>
<th>Email</th>
<th>Date & Time</th>
<th>Notes</th>
<th>Status</th>
<th>Actions</th>
</tr>

<?php foreach($appointments as $a): ?>
<tr>
<td><?php echo htmlspecialchars($a['Aname']); ?></td>
<td><?php echo htmlspecialchars($a['Species'] . ' / ' . $a['Animal_breed']); ?></td>
<td><?php echo htmlspecialchars($a['full_name']); ?></td>
<td><?php echo htmlspecialchars($a['email']); ?></td>
<td><?php echo htmlspecialchars($a['appointment_date']); ?></td>
<td><?php echo htmlspecialchars($a['notes']); ?></td>
<td><?php echo htmlspecialchars($a['status'] ?? 'Pending'); ?></td>
<td>
<?php if($a['status'] !== 'Completed'): ?>
    <a href="?action=completed&id=<?php echo $a['appointment_id']; ?>"><button class="complete">Completed</button></a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<a href="admin dashboard.php" class="back-btn">Back to Dashboard</a>
</div>
</body>
</html>
