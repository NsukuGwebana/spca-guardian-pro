<?php
$ALLOWED_ROLES = ['Administrator', 'Vet']; // who may open this page
require_once __DIR__ . '/Session_handler.php'; // staff only: redirects to login if not signed in 
include("databaseConnection.php");
if (session_status() === PHP_SESSION_NONE) { if (session_status() === PHP_SESSION_NONE) { session_start(); } }

if(!isset($_SESSION['access']) || $_SESSION['access']!="yes"){
    header("Location: loginFormSysdev"); exit();
}

// Handle actions BEFORE HTML output
if(isset($_GET['action']) && isset($_GET['id'])){
    $id=intval($_GET['id']);
    $message="";

    switch($_GET['action']){
        case 'delete':
            q("UPDATE animals SET is_deleted=1, deleted_at=NOW() WHERE id = ?", [$id]);
            $message = "Animal moved to archive.";
            log_activity('Archived animal', 'Animal #' . $id);
            break;

        case 'permanent_delete':
            // First delete related medical records
            q("DELETE FROM animal_kennel WHERE animal_id = ?", [$id]);
            q("DELETE FROM animals WHERE id = ?", [$id]);
            $message = "Animal permanently deleted.";
            log_activity('Deleted animal', 'Animal #' . $id);
            break;

        case 'restore':
            q("UPDATE animals SET is_deleted=0, deleted_at=NULL WHERE id = ?", [$id]);
            $message = "Animal restored.";
            log_activity('Restored animal', 'Animal #' . $id);
            break;
    }

    header("Location: regA.php?message=".urlencode($message)."&view=".(($_GET['view'] ?? '') === 'archive' ? 'archive' : 'active'));
    exit();
} 

// Check if user is a vet
$isVet = ($_SESSION['userrole'] == 'vet' || $_SESSION['userrole'] == 'Vet');

// Determine view - vets can only see active animals
if ($isVet) {
    $view = 'active';
    $where_clause = "WHERE is_deleted=0";
} else {
    $view = isset($_GET['view']) && $_GET['view']=='archive' ? 'archive' : 'active';
    $where_clause = $view=='archive' ? "WHERE is_deleted=1" : "WHERE is_deleted=0";
}

// Search and filters
$f = [
    'view'    => $view,
    'q'       => trim($_GET['q'] ?? ''),
    'species' => trim($_GET['species'] ?? ''),
    'status'  => in_array($_GET['status'] ?? '', ['Available', 'Pending', 'Not Available', 'Adopted'], true) ? $_GET['status'] : '',
    'health'  => in_array($_GET['health'] ?? '', ['Healthy', 'Unhealthy'], true) ? $_GET['health'] : '',
];
$params = [];
if ($f['q'] !== '') {
    $where_clause .= " AND (Aname LIKE ? OR Animal_breed LIKE ? OR Color LIKE ? OR CAST(id AS CHAR) = ?)";
    array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", $f['q']);
}
if ($f['species'] !== '') { $where_clause .= " AND Species = ?";       $params[] = $f['species']; }
if ($f['status'] !== '')  { $where_clause .= " AND status = ?";        $params[] = $f['status']; }
if ($f['health'] !== '')  { $where_clause .= " AND Health_status = ?"; $params[] = $f['health']; }

if (($_GET['export'] ?? '') === 'csv') {
    $rows = q("SELECT id, Aname, Species, Animal_breed, Aage, Color, Aweight, Kennel_status, Health_status, status, Intake_date
               FROM animals $where_clause ORDER BY created_at DESC", $params)->fetch_all(MYSQLI_NUM);
    log_activity('Exported animals CSV', count($rows) . ' rows');
    send_csv('animals_' . $view . '_' . date('Y-m-d') . '.csv',
        ['ID', 'Name', 'Species', 'Breed', 'Age (years)', 'Colour', 'Weight (kg)', 'Kennel', 'Health', 'Adoption status', 'Intake date'], $rows);
}

$total = (int)q("SELECT COUNT(*) n FROM animals $where_clause", $params)->fetch_assoc()['n'];
[$page, $pages, $offset] = paginate($total, (int)($_GET['page'] ?? 1), 15);
$result = q("SELECT * FROM animals $where_clause ORDER BY created_at DESC LIMIT 15 OFFSET $offset", $params);
$speciesList = q("SELECT DISTINCT Species FROM animals WHERE Species <> '' ORDER BY Species")->fetch_all(MYSQLI_NUM);
$keep = array_filter($f, fn($v) => $v !== '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registered Animals | SPCA Guardian Pro</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root { --primary-color:#134f5c; --primary-light:#60a5b3; --primary-dark:#0f3f4a; --accent-color:#ff9f43; --success-color:#27ae60; --warning-color:#e67e22; --danger-color:#e74c3c; --light-bg:#f8f9fa; --text-dark:#2c3e50; --text-light:#7f8c8d; --shadow:0 4px 6px rgba(0,0,0,0.1); --transition:all 0.3s ease; }
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
     background:url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed; 
     background-size:cover; 
     color:var(--text-dark);
     line-height:1.6; 
     margin:0;
     padding:0;}
.overlay{background:linear-gradient(135deg, rgba(19,79,92,0.92) 0%, rgba(15,63,74,0.95) 100%); 
    min-height:100vh;
    padding:40px 20px;}
.container{max-width:95%; 
           margin:auto; 
           padding:30px;
           background:#fff;
           border-radius:16px;
           box-shadow:0 12px 30px rgba(0,0,0,0.4);}
.header{display:flex;
       justify-content:space-between;
       align-items:center;
       margin-bottom:25px; 
       padding-bottom:15px;
       border-bottom:2px solid var(--primary-light);}
.header h2{margin:0; 
       color:var(--primary-color);}
.user-info{display:flex; 
          align-items:center;
          gap:10px; 
          color:var(--primary-color); 
          font-weight:600;}
.message{text-align:center; 
        padding:15px;
        margin-bottom:20px;
        border-radius:10px; 
        background-color:#d4edda; 
        color:#155724; 
        box-shadow:var(--shadow); 
        border-left:5px solid var(--success-color);}
.view-toggle{text-align:center; 
        margin-bottom:25px;
        display:flex; 
        justify-content:center;
         gap:10px;}
.view-toggle input[type="submit"]{background:var(--primary-light);
                                  color:#fff; 
                                  padding:12px 25px; 
                                  border:none; 
                                  border-radius:10px; 
                                  cursor:pointer;
                                  font-weight:600;
                                  transition:var(--transition);
                                  box-shadow:var(--shadow);}
.view-toggle input[type="submit"]:hover{background:var(--primary-color);
                                       transform:translateY(-2px);}
.view-toggle input[type="submit"][style*="font-weight:bold"]{background:var(--primary-color);
                                                             font-weight:bold;}
.table-container{overflow-x:auto; 
                border-radius:12px; 
                box-shadow:var(--shadow);
                margin-bottom:25px;}
table{width:100%; 
    border-collapse:separate;
    border-spacing:0; 
    background:#fff; 
    border-radius:12px;}
table th{background:linear-gradient(to bottom, var(--primary-color),
         var(--primary-dark)); 
         color:white; padding:16px; 
         text-align:left; 
         font-weight:600;}
table td{padding:14px 16px;
     border-bottom:1px solid #e0e0e0;}
table tr:nth-child(even){background-color:#f8f9fa;}
.table tr:hover{background-color:#e9f7f9;}
.btn{display:inline-flex; 
    align-items:center; gap:8px; 
    background:var(--primary-color); 
    color:#fff; padding:10px 16px; 
    border-radius:8px; 
    text-decoration:none;
    font-weight:600; 
    cursor:pointer; 
    transition:var(--transition);}
.btn:hover{background:var(--primary-dark);}
.animal-thumb{width:80px; height:80px;
             object-fit:cover; 
             border-radius:8px;}
.status-badge{display:inline-flex;
             align-items:center; 
             gap:5px; padding:5px 10px; 
             border-radius:20px; 
             font-size:0.85rem; 
             font-weight:600;}
.status-healthy{background-color:rgba(39,174,96,0.2); color:#27ae60;}
.status-unhealthy{background-color:rgba(231,76,60,0.2); color:#e74c3c;}
.status-available{background-color:rgba(46,204,113,0.2); color:#2ecc71;}
.status-adopted{background-color:rgba(149,165,166,0.2); color:#95a5a6;}
.status-warning{background-color:rgba(255,159,67,0.2); color:#e67e22;}
.status-info{background-color:rgba(52,152,219,0.2); color:#3498db;}
.action-buttons{display:flex; gap:8px; justify-content:center; flex-wrap:wrap;}
.vet-notice {text-align: center; 
             padding: 15px;
             margin-bottom: 20px;
             background-color: #e3f2fd; 
             color: #0d47a1; border-radius: 
             10px; box-shadow: var(--shadow);}
</style>
<link rel="stylesheet" href="spca-theme.css">
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<div class="overlay">
<div class="container">
<?php if(isset($_GET['message'])){ echo "<div class='message'>".htmlspecialchars($_GET['message'] ?? '')."</div>"; } ?>
<div class="header">
    <h2>Registered Animals</h2>
    <div class="user-info"><i class="fas fa-user-shield"></i> <?= e($_SESSION['userrole']) ?></div>
</div>

<?php if($isVet): ?>
<div class="vet-notice">
    <i class="fas fa-info-circle"></i> As a veterinarian, you can only view active animals and log medical procedures.
</div>
<?php else: ?>
<nav class="tabs" aria-label="Which animals">
    <a class="<?= $view === 'active' ? 'on' : '' ?>" href="?view=active">In care</a>
    <a class="<?= $view === 'archive' ? 'on' : '' ?>" href="?view=archive">Archived</a>
</nav>
<?php endif; ?>

<form class="filter-bar" method="get">
    <input type="hidden" name="view" value="<?= e($view) ?>">
    <label class="grow">Name, breed, colour or ID<input type="search" name="q" value="<?= e($f['q']) ?>"></label>
    <label>Species<select name="species"><option value="">All</option>
        <?php foreach ($speciesList as [$sp]): ?><option <?= $sp === $f['species'] ? 'selected' : '' ?>><?= e($sp) ?></option><?php endforeach; ?>
    </select></label>
    <label>Adoption status<select name="status"><option value="">All</option>
        <?php foreach (['Available', 'Pending', 'Not Available', 'Adopted'] as $st): ?><option <?= $st === $f['status'] ? 'selected' : '' ?>><?= $st ?></option><?php endforeach; ?>
    </select></label>
    <label>Health<select name="health"><option value="">All</option>
        <?php foreach (['Healthy', 'Unhealthy'] as $h): ?><option <?= $h === $f['health'] ? 'selected' : '' ?>><?= $h ?></option><?php endforeach; ?>
    </select></label>
    <button type="submit">Filter</button>
    <?php if (count($keep) > 1): ?><a class="tool-btn light" href="?view=<?= e($view) ?>">Clear</a><?php endif; ?>
    <a class="tool-btn light" href="?<?= e(http_build_query($keep + ['export' => 'csv'])) ?>">Download CSV</a>
</form>
<div class="list-meta"><span><?= $total ?> animal<?= $total === 1 ? '' : 's' ?></span><span>Newest first</span></div>

<div class="table-container">
<table>
<thead>
<tr>
<th>ID</th>
<th>Picture</th>
<th>Name</th>
<th>Species</th>
<th>Breed</th>
<th>Age</th>
<th>Color</th>
<th>Weight</th>
<th>Width</th>
<th>Height</th>
<th>Kennel</th>
<th>Health</th>
<th>Medical treatment Status</th>
<th>Adoption status</th>
<th>Intake</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php
if($result->num_rows>0){
    while($row=$result->fetch_assoc()){
$treatment_status = 'Not Available';

// Get latest appointment
$app_stmt = $conn->prepare("
    SELECT app_id 
    FROM appointments 
    WHERE animal_id = ? 
    ORDER BY app_date DESC LIMIT 1
");
$app_stmt->bind_param("i", $row['id']);
$app_stmt->execute();
$app_result = $app_stmt->get_result();
$app_row = $app_result->fetch_assoc();

$appointment_id = $app_row['app_id'] ?? null;

if ($appointment_id) {
    $treat_stmt = $conn->prepare("SELECT treatment_types FROM treatments WHERE app_id = ?");
    $treat_stmt->bind_param("i", $appointment_id);
    $treat_stmt->execute();
    $treat_result = $treat_stmt->get_result();

    $completed = [];
    while ($t = $treat_result->fetch_assoc()) {
        $completed[] = $t['treatment_types'];
    }

    $compulsory = ['Vaccination', 'Spaying', 'Neutering'];
    $unique_completed = array_unique($completed);

    if (count(array_intersect($compulsory, $unique_completed)) === count($compulsory)) {
        $treatment_status = 'Complete';
    } elseif (count(array_intersect($compulsory, $unique_completed)) > 0) {
        $treatment_status = 'In Progress';
    } else {
        $treatment_status = 'Scheduled';
    }
}

$status_class = [
    'Scheduled' => 'status-info',
    'In Progress' => 'status-warning',
    'Complete' => 'status-healthy',
    'Not Available' => 'status-unhealthy'
];

        echo "<tr>";
        echo "<td>" . e($row['id']) . "</td>";
        echo "<td>".(!empty($row['animal_picture'])?"<img class='animal-thumb' src='".htmlspecialchars($row['animal_picture'])."' alt='' onerror=\"this.onerror=null;this.src='Images/no-photo.svg'\" />":"<img class='animal-thumb' src='Images/no-photo.svg' alt='No photo yet' />")."</td>";
        echo "<td>" . e($row['Aname']) . "</td>";
        echo "<td>" . e($row['Species']) . "</td>";
        echo "<td>" . e($row['Animal_breed']) . "</td>";
        echo "<td>" . e($row['Aage']) . "</td>";
        echo "<td>" . e($row['Color']) . "</td>";
        echo "<td>" . e($row['Aweight']) . "</td>";
        echo "<td>" . e($row['Width']) . "</td>";
        echo "<td>" . e($row['Height']) . "</td>";
        echo "<td>" . e($row['Kennel_status']) . "</td>";

        // Health status badge
        echo "<td><span class='status-badge ".($row['Health_status']=='Healthy'?'status-healthy':'status-unhealthy')."'><i class='fas ".($row['Health_status']=='Healthy'?'fa-heart':'fa-heartbeat')."'></i>" . e($row['Health_status']) . "</span></td>";

        // Treatment status badge
        echo "<td><span class='status-badge ".$status_class[$treatment_status]."'><i class='fas fa-notes-medical'></i> {$treatment_status}</span></td>";

        // Adoption status badge
        echo "<td><span class='status-badge ".($row['status']=='Available'?'status-available':'status-adopted')."'><i class='fas ".($row['status']=='Available'?'fa-home':'fa-check-circle')."'></i>" . e($row['status']) . "</span></td>";

        echo "<td>" . e($row['Intake_date']) . "</td>";

        echo "<td class='action-buttons'>";
        
       if ($isVet) {
        echo "<a class='btn' href='view_animalProfile.php?id=" . e($row['id']) . "'><i class='fas fa-file-medical'></i> View full animal profile</a>";
        echo "<a class='btn' href='generate_pdf.php?id=" . e($row['id']) . "' target='_blank'><i class='fas fa-file-pdf'></i> PDF</a>";
        } else {
            echo "<a class='btn' href='view_animalProfile.php?id=" . e($row['id']) . "'><i class='fas fa-eye'></i> View</a>";
            echo "<a class='btn' href='edit_animal.php?id=" . e($row['id']) . "'><i class='fas fa-edit'></i> Edit</a>";
            echo "<a class='btn' href='generate_pdf.php?id=" . e($row['id']) . "' target='_blank'><i class='fas fa-file-pdf'></i> PDF</a>";

            if($view=='active'){
                echo "<a class='btn' href='?action=delete&id=" . e($row['id']) . "&view={$view}' onclick='return confirm(\"Move to archive?\")'><i class='fas fa-archive'></i> Archive</a>";
            } else {
                echo "<a class='btn' href='?action=restore&id=" . e($row['id']) . "&view={$view}' onclick='return confirm(\"Restore this animal?\")'><i class='fas fa-undo'></i> Restore</a>";
                echo "<a class='btn' href='?action=permanent_delete&id=" . e($row['id']) . "&view={$view}' onclick='return confirm(\"Permanently delete? This cannot be undone.\")'><i class='fas fa-trash'></i> Delete</a>";
            }
        }
        
        echo "</td>";

        echo "</tr>";
    }
}else{
    echo "<tr><td colspan='15' style='text-align:center; padding:20px;'>No animals found.</td></tr>";
}
?>
</tbody>
</table>
</div>
<?= pager_html($page, $pages, $keep) ?>

</div>
<?php if ($isVet) { ?>
    <div class="back-btn-container">
        <a href="dashboard.php" class="back-btn">&larr; Back to dashboard</a>
        <a class="btn" href="javascript:window.print()">
            <i></i> Print
        </a>
    </div>
<?php } else { ?>
    <div class="back-btn-container">
        <a href="dashboard.php" class="back-btn">&larr; Back to dashboard</a>
        <a class="btn" href="javascript:window.print()">
            <i></i> Print
        </a>
    </div>
<?php } ?>

</div>
</body>
</html>
