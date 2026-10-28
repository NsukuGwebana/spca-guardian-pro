<?php
require 'databaseConnection.php';
$message = "";

if(isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['action'] === 'complete' ? 'Completed' : 'Missed';

    $stmt = $conn->prepare("UPDATE appointments SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    if($stmt->execute()) {
        $message = "Appointment marked as {$status}.";
    }
}

// Fetch all appointments
$result = $conn->query("
    SELECT ap.id, ap.appointment_date, ap.status, ap.notes,
           ad.full_name, ad.email, ani.Aname
    FROM appointments ap
    JOIN adoptions ad ON ap.adoption_id = ad.id
    JOIN animals ani ON ad.animal_id = ani.id
    ORDER BY ap.appointment_date DESC
");
$appointments = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Appointments List</title>
<style>
body { font-family: Arial, sans-serif; background: #f8f8f8; padding: 20px; color:#333; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
th, td { border:1px solid #ccc; padding:8px; text-align:left; }
th { background:#4CAF50; color:white; }
button { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; }
.complete { background:#4CAF50; color:white; }
.complete:hover { background:#388e3c; }
.missed { background:#f44336; color:white; }
.missed:hover { background:#d32f2f; }
.message { font-weight:bold; margin-bottom:10px; color:#2E7D32; }
</style>
</head>
<body>
<h2>Appointments</h2>
<?php if($message) echo "<p class='message'>$message</p>"; ?>

<table>
<tr>
<th>Animal</th>
<th>Adopter</th>
<th>Email</th>
<th>Date & Time</th>
<th>Notes</th>
<th>Status</th>
<th>Actions</th>
</tr>

<?php foreach($appointments as $ap): ?>
<tr>
<td><?php echo htmlspecialchars($ap['Aname']); ?></td>
<td><?php echo htmlspecialchars($ap['full_name']); ?></td>
<td><?php echo htmlspecialchars($ap['email']); ?></td>
<td><?php echo htmlspecialchars($ap['appointment_date']); ?></td>
<td><?php echo htmlspecialchars($ap['notes']); ?></td>
<td><?php echo htmlspecialchars($ap['status']); ?></td>
<td>
<?php if($ap['status'] === 'Scheduled'): ?>
    <a href="?action=complete&id=<?php echo $ap['id']; ?>"><button class="complete">Mark Completed</button></a>
    <a href="?action=missed&id=<?php echo $ap['id']; ?>"><button class="missed">Mark Missed</button></a>
<?php else: ?>N/A<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
