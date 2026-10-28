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

// Fetch all approved adoptions
$result = $conn->query("
    SELECT a.id AS adoption_id, ani.Aname, ani.Species, ani.Animal_breed,
           a.full_name, a.id_number, a.date_of_birth, a.phone, a.email,
           CONCAT(a.address_street, ', ', a.address_city, ', ', a.address_province, ' ', a.address_postal_code) AS full_address,
           a.employment_status, a.monthly_income, a.housing_type, a.has_fenced_yard, a.has_other_pets, a.other_pets_details,
           a.reason, a.id_copy, a.created_at
    FROM adoptions a
    JOIN animals ani ON a.animal_id = ani.id
    WHERE a.status='Approved'
    ORDER BY a.created_at DESC
");
$approved_adoptions = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>Approved Adoptions</title>
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
}

h1 {
    text-align: center;
    margin-bottom: 20px;
    color: #e0f7fa;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    border: 1px solid #60a5fa;
    padding: 8px;
    text-align: left;
}

th {
    background-color: #134f5c;
    color: #fff;
}

td {
    background-color: rgba(255,255,255,0.1);
}

button.view-id {
    display: inline-block;
    padding: 6px 12px;
    margin: 2px;
    border-radius: 6px;
    background-color: #60a5fa;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    border: none;
}

button.view-id:hover { background-color: #3b82f6; }

.back-btn {
    display: block;
    width: fit-content;
    background: #60a5fa;
    color: #fff;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: bold;
    margin: 20px auto;
    text-align: center;
    text-decoration: none;
}

.back-btn:hover {
    background: #3b82f6;
}

/* Modal for ID view */
#idModal {
    display: none;
    position: fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background: rgba(0,0,0,0.85);
    z-index:9999;
    text-align:center;
}
#idModal iframe {
    width: 80%;
    height: 80vh;
    margin-top: 50px;
    border: none;
    border-radius: 8px;
}
#closeModal {
    position:absolute;
    top:20px;
    left:20px;
    background:#60a5fa;
    color:#fff;
    padding:10px 18px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight: bold;
}
#closeModal:hover {
    background: #3b82f6;
}
</style>
</head>
<body>
<div class="overlay">
<h1> Approved Adoption Applications</h1>

<?php if(count($approved_adoptions) === 0): ?>
    <p style="text-align:center;">No approved adoptions found.</p>
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
        <th>Approved On</th>
    </tr>
    <?php foreach($approved_adoptions as $a): ?>
    <tr>
        <td><?php echo htmlspecialchars($a['Aname']); ?></td>
        <td><?php echo htmlspecialchars($a['Species'] . ' / ' . $a['Animal_breed']); ?></td>
        <td><?php echo htmlspecialchars($a['full_name'] . ' (' . $a['id_number'] . ')'); ?></td>
        <td><?php echo htmlspecialchars($a['date_of_birth']); ?></td>
        <td><?php echo htmlspecialchars($a['phone'] . ' / ' . $a['email']); ?></td>
        <td><?php echo htmlspecialchars($a['full_address']); ?></td>
        <td><?php echo htmlspecialchars($a['employment_status'] . ' / ' . $a['monthly_income']); ?></td>
        <td><?php echo htmlspecialchars($a['housing_type'] . ' / ' . ($a['has_fenced_yard'] ? 'Yes' : 'No')); ?></td>
        <td><?php echo htmlspecialchars($a['has_other_pets'] ? 'Yes: ' . $a['other_pets_details'] : 'No'); ?></td>
        <td><?php echo htmlspecialchars($a['reason']); ?></td>
        <td>
            <?php if($a['id_copy']): ?>
                <button class="view-id" data-file="<?php echo htmlspecialchars($a['id_copy']); ?>">View</button>
            <?php else: ?>N/A<?php endif; ?>
        </td>
        <td><?php echo htmlspecialchars($a['created_at']); ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<a href="admin dashboard.php" class="back-btn">⬅ Back to Dashboard</a>

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
