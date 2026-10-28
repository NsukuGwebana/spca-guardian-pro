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

// Handle approve/reject
$message = "";
if(isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];

    if($action === 'approve') {
        $stmt = $conn->prepare("UPDATE fosters SET status='Approved' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Update animal status to 'Fostered'
        $stmt2 = $conn->prepare("UPDATE animals SET status='Pending Foster' WHERE id=(SELECT animal_id FROM fosters WHERE id=?)");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();

        $message = "Foster application approved successfully!";
    } elseif($action === 'reject') {
        $stmt = $conn->prepare("UPDATE fosters SET status='Rejected' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Update animal status back to Available
        $stmt2 = $conn->prepare("UPDATE animals SET status='Available' WHERE id=(SELECT animal_id FROM fosters WHERE id=?)");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();

        $message = "Foster application rejected successfully!";
    }
}

// Fetch all pending foster applications
$result = $conn->query("
    SELECT f.id AS foster_id, ani.Aname, ani.Species, ani.Animal_breed,
           f.full_name, f.id_number, f.date_of_birth, f.phone, f.email,
           CONCAT(f.address_street, ', ', f.address_city, ', ', f.address_province, ' ', f.address_postal_code) AS full_address,
           f.employment_status, f.monthly_income, f.housing_type, f.has_fenced_yard, f.has_other_pets, f.other_pets_details,
           f.reason, f.id_copy, f.status AS foster_status
    FROM fosters f
    JOIN animals ani ON f.animal_id = ani.id
    WHERE f.status='Pending'
    ORDER BY f.id DESC
");
$fosters = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>Screen Foster Applications</title>
<style>
body { font-family: Arial, sans-serif; background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed; background-size: cover; margin: 0; padding: 0; color: #fff; }
.overlay { background: rgba(19, 79, 92, 0.85); min-height: 100vh; padding: 30px; }
h1 { text-align: center; margin-bottom: 20px; color: #e0f7fa; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #60a5fa; padding: 8px; text-align: left; }
th { background-color: #134f5c; color: #fff; }
td { background-color: rgba(255,255,255,0.1); }
a.btn, button.view-id { display: inline-block; padding: 6px 12px; margin: 2px; border-radius: 6px; text-decoration: none; font-weight: bold; color: #fff; cursor: pointer; }
a.approve { background-color: #4caf50; }
a.approve:hover { background-color: #388e3c; }
a.reject { background-color: #f44336; }
a.reject:hover { background-color: #d32f2f; }
button.view-id { background-color: #60a5fa; }
button.view-id:hover { background-color: #3b82f6; }
p.message { text-align: center; font-weight: bold; font-size: 1.1rem; color: #ffd700; }
.back-btn { display: block; width: fit-content; background: #60a5fa; color: #fff; padding: 10px 16px; border-radius: 6px; font-weight: bold; margin: 20px auto; text-align: center; text-decoration: none; }
.back-btn:hover { background: #3b82f6; }
/* Modal for ID view */
#idModal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.85); z-index:9999; text-align:center; }
#idModal iframe { width: 80%; height: 80vh; margin-top: 50px; border: none; border-radius: 8px; }
#closeModal { position:absolute; top:20px; left:20px; background:#60a5fa; color:#fff; padding:10px 18px; border:none; border-radius:6px; cursor:pointer; font-weight: bold; }
#closeModal:hover { background: #3b82f6; }
</style>
</head>
<body>
<div class="overlay">
<h1>Screen Foster Applications</h1>

<?php if($message): ?>
    <p class="message"><?php echo $message; ?></p>
<?php endif; ?>

<?php if(count($fosters) === 0): ?>
    <p style="text-align:center;">No pending foster applications.</p>
<?php else: ?>
<table>
    <tr>
        <th>Animal</th>
        <th>Species / Breed</th>
        <th>Applicant</th>
        <th>DOB</th>
        <th>Contact</th>
        <th>Address</th>
        <th>Employment / Income</th>
        <th>Housing / Fenced Yard</th>
        <th>Other Pets</th>
        <th>Reason</th>
        <th>ID Copy</th>
        <th>Actions</th>
    </tr>
    <?php foreach($fosters as $f): ?>
    <tr>
        <td><?php echo htmlspecialchars($f['Aname']); ?></td>
        <td><?php echo htmlspecialchars($f['Species'] . ' / ' . $f['Animal_breed']); ?></td>
        <td><?php echo htmlspecialchars($f['full_name'] . ' (' . $f['id_number'] . ')'); ?></td>
        <td><?php echo htmlspecialchars($f['date_of_birth']); ?></td>
        <td><?php echo htmlspecialchars($f['phone'] . ' / ' . $f['email']); ?></td>
        <td><?php echo htmlspecialchars($f['full_address']); ?></td>
        <td><?php echo htmlspecialchars($f['employment_status'] . ' / ' . $f['monthly_income']); ?></td>
        <td><?php echo htmlspecialchars($f['housing_type'] . ' / ' . ($f['has_fenced_yard'] ? 'Yes' : 'No')); ?></td>
        <td><?php echo htmlspecialchars($f['has_other_pets'] ? 'Yes: ' . $f['other_pets_details'] : 'No'); ?></td>
        <td><?php echo htmlspecialchars($f['reason']); ?></td>
        <td>
            <?php if($f['id_copy']): ?>
                <button class="view-id" data-file="<?php echo htmlspecialchars($f['id_copy']); ?>">View</button>
            <?php else: ?>N/A<?php endif; ?>
        </td>
        <td>
            <a href="?action=approve&id=<?php echo $f['foster_id']; ?>" class="btn approve">Approve</a>
            <a href="?action=reject&id=<?php echo $f['foster_id']; ?>" class="btn reject">Reject</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<a href="admin dashboard.php" class="back-btn">Back to Dashboard</a>

<!-- Modal for viewing ID -->
<div id="idModal">
    <button id="closeModal">⬅ Back</button>
    <iframe id="idFrame"></iframe>
</div>

<script>
const modal = document.getElementById('idModal');
const iframe = document.getElementById('idFrame');
document.querySelectorAll('.view-id').forEach(btn => {
    btn.addEventListener('click', () => {
        iframe.src = btn.dataset.file;
        modal.style.display = 'block';
    });
});
document.getElementById('closeModal').addEventListener('click', () => {
    modal.style.display = 'none';
    iframe.src = '';
});
</script>

</div>
</body>
</html>
