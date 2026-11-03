<?php
$ALLOWED_ROLES = ['Administrator']; // who may open this page
require_once __DIR__ . '/Session_handler.php'; // staff only: redirects to login if not signed in
require_once __DIR__ . '/bootstrap.php';

$conn = db();

// Handle approve/reject
$message = "";
if (isset($_GET['action'], $_GET['id']) && in_array($_GET['action'], ['approve', 'reject'], true)) {
    $id = (int)$_GET['id'];
    $to = $_GET['action'] === 'approve' ? 'Approved' : 'Rejected';
    $f  = q("SELECT f.id, f.status, f.full_name, f.email, f.animal_id, a.Aname
             FROM fosters f JOIN animals a ON a.id = f.animal_id WHERE f.id = ?", [$id])->fetch_assoc();

    if (!$f) {
        $message = "That foster application no longer exists.";
    } elseif ($f['status'] !== 'Pending') {
        $message = "That application was already {$f['status']}.";
    } else {
        q("UPDATE fosters SET status = ? WHERE id = ?", [$to, $id]);
        // An approved foster takes the animal off the adoption list; a rejection puts it back.
        q("UPDATE animals SET status = ? WHERE id = ?", [$to === 'Approved' ? 'Not Available' : 'Available', (int)$f['animal_id']]);

        $html = '<p>Dear ' . e($f['full_name']) . ',</p>'
              . ($to === 'Approved'
                  ? '<p>Good news: your application to foster <strong>' . e($f['Aname']) . '</strong> has been approved. We will phone you to arrange collection. Please bring your ID document.</p>'
                  : '<p>Thank you for offering to foster <strong>' . e($f['Aname']) . '</strong>. Unfortunately we cannot approve your application this time.</p>')
              . '<p>Kind regards,<br>SPCA Guardian Pro team</p>';
        $sent = send_mail((string)$f['email'], "Your foster application for {$f['Aname']}", $html);
        log_activity("Foster application $to", "Application #$id ({$f['Aname']}, {$f['full_name']})");
        $message = "{$f['full_name']}'s foster application was " . strtolower($to) . '.'
                 . ($sent ? ' The applicant has been emailed.' : ' Please phone the applicant to let them know.');
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
?>

<!DOCTYPE html>
<html>
<head>
<title>Screen Foster Applications | SPCA Guardian Pro</title>
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
<link rel="stylesheet" href="spca-theme.css">
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<div class="overlay">
<h1>Screen Foster Applications</h1>

<?php if($message): ?>
    <p class="message"><?= e($message) ?></p>
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
        <td><?php echo htmlspecialchars($f['Aname'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($f['Species'] . ' / ' . $f['Animal_breed']); ?></td>
        <td><?php echo htmlspecialchars($f['full_name'] . ' (' . $f['id_number'] . ')'); ?></td>
        <td><?php echo htmlspecialchars($f['date_of_birth'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($f['phone'] . ' / ' . $f['email']); ?></td>
        <td><?php echo htmlspecialchars($f['full_address'] ?? ''); ?></td>
        <td><?php echo htmlspecialchars($f['employment_status'] . ' / ' . $f['monthly_income']); ?></td>
        <td><?php echo htmlspecialchars($f['housing_type'] . ' / ' . ($f['has_fenced_yard'] ? 'Yes' : 'No')); ?></td>
        <td><?php echo htmlspecialchars($f['has_other_pets'] ? 'Yes: ' . $f['other_pets_details'] : 'No'); ?></td>
        <td><?php echo htmlspecialchars($f['reason'] ?? ''); ?></td>
        <td>
            <?php if($f['id_copy']): ?>
                <button class="view-id" data-file="<?php echo htmlspecialchars($f['id_copy'] ?? ''); ?>">View</button>
            <?php else: ?>N/A<?php endif; ?>
        </td>
        <td>
            <a href="?action=approve&id=<?= e($f['foster_id']) ?>" class="btn approve">Approve</a>
            <a href="?action=reject&id=<?= e($f['foster_id']) ?>" class="btn reject">Reject</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<a href="dashboard.php" class="back-btn">&larr; Back to dashboard</a>

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
