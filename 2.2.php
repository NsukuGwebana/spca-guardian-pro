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

// Fetch all adoptions (history)
$result = $conn->query("
    SELECT a.id AS adoption_id, a.status AS adoption_status, a.full_name, a.email, a.phone,
           a.date_of_birth, a.address_street, a.address_city, a.address_province, a.address_postal_code,
           a.employment_status, a.monthly_income, a.housing_type, a.has_fenced_yard, a.has_other_pets, a.other_pets_details,
           a.reason, ani.Aname, ani.Species, ani.Animal_breed
    FROM adoptions a
    JOIN animals ani ON a.animal_id = ani.id
    ORDER BY a.id DESC
");
$adoptions = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>Adoption History</title>
<style>
body { font-family: Arial, sans-serif; background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed; background-size: cover; margin:0; padding:0; color:#fff; }
.overlay { background: rgba(19, 79, 92, 0.85); min-height: 100vh; padding: 30px; }
h1 { text-align:center; margin-bottom:20px; color:#e0f7fa; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
th, td { border:1px solid #60a5fa; padding:8px; text-align:left; }
th { background:#134f5c; color:#fff; }
td { background: rgba(255,255,255,0.1); }
p.message { text-align:center; font-weight:bold; font-size:1.1rem; color:#ffd700; }
.back-btn { display:block; width:fit-content; background:#60a5fa; color:#fff; padding:10px 16px; border-radius:6px; font-weight:bold; margin:20px auto; text-align:center; text-decoration:none; }
.back-btn:hover { background:#3b82f6; }
/* Status badge colors */
.status-approved { color:#fff; background:#4CAF50; padding:3px 8px; border-radius:6px; font-weight:bold; }
.status-rejected { color:#fff; background:#f44336; padding:3px 8px; border-radius:6px; font-weight:bold; }
.status-pending { color:#fff; background:#ff9800; padding:3px 8px; border-radius:6px; font-weight:bold; }
</style>
</head>
<body>
<div class="overlay">
<h1>Adoption History</h1>

<?php if(count($adoptions) === 0): ?>
<p style="text-align:center;">No adoption records found.</p>
<?php else: ?>
<table>
<tr>
<th>Animal</th>
<th>Species / Breed</th>
<th>Adopter</th>
<th>DOB</th>
<th>Contact</th>
<th>Address</th>
<th>Employment / Income</th>
<th>Housing / Fenced Yard</th>
<th>Other Pets</th>
<th>Reason</th>
<th>Status</th>
</tr>

<?php foreach($adoptions as $a): ?>
<tr>
<td><?php echo htmlspecialchars($a['Aname']); ?></td>
<td><?php echo htmlspecialchars($a['Species'] . ' / ' . $a['Animal_breed']); ?></td>
<td><?php echo htmlspecialchars($a['full_name']); ?></td>
<td><?php echo htmlspecialchars($a['date_of_birth']); ?></td>
<td><?php echo htmlspecialchars($a['phone'] . ' / ' . $a['email']); ?></td>
<td><?php echo htmlspecialchars($a['address_street'] . ', ' . $a['address_city'] . ', ' . $a['address_province'] . ' ' . $a['address_postal_code']); ?></td>
<td><?php echo htmlspecialchars($a['employment_status'] . ' / ' . $a['monthly_income']); ?></td>
<td><?php echo htmlspecialchars($a['housing_type'] . ' / ' . ($a['has_fenced_yard'] ? 'Yes' : 'No')); ?></td>
<td><?php echo htmlspecialchars($a['has_other_pets'] ? 'Yes: ' . $a['other_pets_details'] : 'No'); ?></td>
<td><?php echo htmlspecialchars($a['reason']); ?></td>
<td>
<?php 
$status = $a['adoption_status'];
if($status === 'Approved') echo "<span class='status-approved'>Approved</span>";
elseif($status === 'Rejected') echo "<span class='status-rejected'>Rejected</span>";
else echo "<span class='status-pending'>Pending</span>";
?>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<a href="admin dashboard.php" class="back-btn"> Back to Dashboard</a>
</div>
</body>
</html>
