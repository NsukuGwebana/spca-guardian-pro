<?php
$ALLOWED_ROLES = ['Administrator']; // who may open this page
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

    header("Location: view_registeredAnimals.php?message=".urlencode($message)."&view=".(($_GET['view'] ?? '') === 'archive' ? 'archive' : 'active'));
    exit();
}

// Determine view
$view=isset($_GET['view']) && $_GET['view']=='archive' ? 'archive' : 'active';
$where_clause=$view=='archive' ? "WHERE is_deleted=1" : "WHERE is_deleted=0";

// Fetch animals
$sql="SELECT * FROM animals $where_clause ORDER BY created_at DESC";
$result=$conn->query($sql);

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
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed; background-size:cover; color:var(--text-dark); line-height:1.6; margin:0; padding:0;}
.overlay{background:linear-gradient(135deg, rgba(19,79,92,0.92) 0%, rgba(15,63,74,0.95) 100%); min-height:100vh; padding:40px 20px;}
.container{max-width:95%; margin:auto; padding:30px; background:#fff; border-radius:16px; box-shadow:0 12px 30px rgba(0,0,0,0.4);}
.header{display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; padding-bottom:15px; border-bottom:2px solid var(--primary-light);}
.header h2{margin:0; color:var(--primary-color); position:relative; padding-bottom:10px;}
.header h2::after{content:''; position:absolute; bottom:-2px; left:0; width:60px; height:4px; background:linear-gradient(90deg, var(--primary-light), var(--primary-color)); border-radius:2px;}
.user-info{display:flex; align-items:center; gap:10px; color:var(--primary-color); font-weight:600;}
.user-info i{font-size:1.2rem;}
.message{text-align:center; padding:15px; margin-bottom:20px; border-radius:10px; background-color:#d4edda; color:#155724; box-shadow:var(--shadow); transition:var(--transition); border-left:5px solid var(--success-color);}
.view-toggle{text-align:center; margin-bottom:25px; display:flex; justify-content:center; gap:10px;}
.view-toggle input[type="submit"]{background:var(--primary-light); color:#fff; padding:12px 25px; border:none; border-radius:10px; cursor:pointer; font-weight:600; transition:var(--transition); box-shadow:var(--shadow);}
.view-toggle input[type="submit"]:hover{background:var(--primary-color); transform:translateY(-2px);}
.view-toggle input[type="submit"][style*="font-weight:bold"]{background:var(--primary-color); font-weight:bold; box-shadow:0 4px 8px rgba(0,0,0,0.2);}
.table-container{overflow-x:auto; border-radius:12px; box-shadow:var(--shadow); margin-bottom:25px;}
table{width:100%; border-collapse:separate; border-spacing:0; background:#fff; border-radius:12px;}
table th{background:linear-gradient(to bottom, var(--primary-color), var(--primary-dark)); color:white; padding:16px; text-align:left; font-weight:600; position:sticky; top:0;}
table th:first-child{border-top-left-radius:12px;}
table th:last-child{border-top-right-radius:12px;}
table td{padding:14px 16px; border-bottom:1px solid #e0e0e0; transition:var(--transition);}
table tr:nth-child(even){background-color:#f8f9fa;}
table tr:last-child td:first-child{border-bottom-left-radius:12px;}
table tr:last-child td:last-child{border-bottom-right-radius:12px;}
table tr:hover{background-color:#e9f7f9; transform:scale(1.01); box-shadow:0 2px 8px rgba(0,0,0,0.05);}
.btn{display:inline-flex; align-items:center; gap:8px; background:var(--primary-color); color:#fff; padding:10px 16px; border-radius:8px; text-decoration:none; font-weight:600; cursor:pointer; transition:var(--transition); box-shadow:var(--shadow);}
.btn:hover{background:var(--primary-dark); transform:translateY(-3px); box-shadow:0 6px 12px rgba(0,0,0,0.15);}
.animal-thumb{width:80px; height:80px; object-fit:cover; border-radius:8px; box-shadow:var(--shadow); transition:var(--transition);}
.animal-thumb:hover{transform:scale(1.1); box-shadow:0 4px 8px rgba(0,0,0,0.2);}
.status-badge{display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:20px; font-size:0.85rem; font-weight:600;}
.status-healthy{background-color:rgba(39,174,96,0.2); color:#27ae60;}
.status-unhealthy{background-color:rgba(231,76,60,0.2); color:#e74c3c;}
.status-available{background-color:rgba(46,204,113,0.2); color:#2ecc71;}
.status-adopted{background-color:rgba(149,165,166,0.2); color:#95a5a6;}
.action-buttons{display:flex; gap:8px; justify-content:center; flex-wrap:wrap;}
.action-container{text-align:center; margin-top:25px;}
</style>
<link rel="stylesheet" href="spca-theme.css">
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<div class="overlay">
<div class="container">
<?php if(isset($_GET['message'])){ echo "<div class='message'>".htmlspecialchars($_GET['message'])."</div>"; } ?>
<div class="header">
    <h2>Registered Animals</h2>
    <div class="user-info"><i class="fas fa-user-shield"></i> <?= e($_SESSION['userrole']) ?></div>
</div>

<div class="view-toggle">
<form method="GET">
    <input type="submit" name="view" value="active" <?php echo $view=='active'?'style="font-weight:bold;"':''; ?>>
    <input type="submit" name="view" value="archive" <?php echo $view=='archive'?'style="font-weight:bold;"':''; ?>>
</form>
</div>

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
<th>Status</th>
<th>Intake</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php
if($result->num_rows>0){
    while($row=$result->fetch_assoc()){
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
        echo "<td><span class='status-badge ".($row['Health_status']=='Healthy'?'status-healthy':'status-unhealthy')."'><i class='fas ".($row['Health_status']=='Healthy'?'fa-heart':'fa-heartbeat')."'></i>" . e($row['Health_status']) . "</span></td>";
        echo "<td><span class='status-badge ".($row['status']=='Available'?'status-available':'status-adopted')."'><i class='fas ".($row['status']=='Available'?'fa-home':'fa-check-circle')."'></i>" . e($row['status']) . "</span></td>";
        echo "<td>" . e($row['Intake_date']) . "</td>";

        echo "<td class='action-buttons'>";
        // Preserve original buttons: View, Edit, PDF
        echo "<a class='btn' href='view_animalProfile.php?id=" . e($row['id']) . "'><i class='fas fa-eye'></i> View</a>";
        echo "<a class='btn' href='edit_animal.php?id=" . e($row['id']) . "'><i class='fas fa-edit'></i> Edit</a>";
        echo "<a class='btn' href='generate_pdf.php?id=" . e($row['id']) . "' target='_blank'><i class='fas fa-file-pdf'></i> PDF</a>";

        if($view=='active'){
            echo "<a class='btn' href='?action=delete&id=" . e($row['id']) . "&view={$view}' onclick='return confirm(\"Move to archive?\")'><i class='fas fa-archive'></i> Archive</a>";
        } else {
            echo "<a class='btn' href='?action=restore&id=" . e($row['id']) . "&view={$view}' onclick='return confirm(\"Restore this animal?\")'><i class='fas fa-undo'></i> Restore</a>";
            echo "<a class='btn' href='?action=permanent_delete&id=" . e($row['id']) . "&view={$view}' onclick='return confirm(\"Permanently delete? This cannot be undone.\")'><i class='fas fa-trash'></i> Delete</a>";
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
</div>
</div>
</body>
</html>





