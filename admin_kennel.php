<?php
include("databaseConnection.php");

// Handle actions BEFORE HTML output
if(isset($_GET['action']) && isset($_GET['id'])){
    $id=intval($_GET['id']);
    $message="";

    switch($_GET['action']){
        case 'delete':
            $sql="UPDATE kennels SET is_deleted=1, deleted_at=NOW() WHERE kennel_id=$id";
            $message=$conn->query($sql) ? "Kennel moved to archive successfully." : "Error: ".$conn->error;
            break;

        case 'restore':
            $sql="UPDATE kennels SET is_deleted=0, deleted_at=NULL WHERE kennel_id=$id";
            $message=$conn->query($sql) ? "Kennel restored successfully." : "Error: ".$conn->error;
            break;

        case 'permanent_delete':
            $sql="DELETE FROM kennels WHERE kennel_id=$id";
            $message=$conn->query($sql) ? "Kennel permanently deleted." : "Error: ".$conn->error;
            break;
    }

    header("Location: admin_kennel.php?message=".urlencode($message)."&view=".(isset($_GET['view'])?$_GET['view']:'active'));
    exit();
}

// Determine view
$view=isset($_GET['view']) && $_GET['view']=='archive' ? 'archive' : 'active';
$where_clause=$view=='archive' ? "WHERE is_deleted=1" : "WHERE is_deleted=0";

// Fetch kennels
$sql="SELECT * FROM kennels $where_clause ORDER BY created_at DESC";
$result=$conn->query($sql);
if($result===FALSE){ die("<p>Unable to retrieve kennel data: ".$conn->error."</p>"); }

// Function to calculate capacity
function calculate_kennel_capacity($width, $height) {
    $area = $width * $height;
    return [
        'small' => floor($area / 1),
        'medium' => floor($area / 2),
        'large' => floor($area / 4)
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registered Kennels - AnimalGuardian</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root { 
    --primary-color:#134f5c; 
    --primary-light:#60a5b3; 
    --primary-dark:#0f3f4a; 
    --success-color:#27ae60; 
    --danger-color:#e74c3c; 
    --warning-color:#f1c40f; 
    --shadow:0 4px 6px rgba(0,0,0,0.1); 
    --transition:all 0.3s ease; 
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed; background-size:cover; color:#134f5c; line-height:1.6;}
.overlay{background:linear-gradient(135deg, rgba(19,79,92,0.92) 0%, rgba(15,63,74,0.95) 100%); min-height:100vh; padding:40px 20px;}
.container{max-width:98%; margin:auto; padding:30px; background:#fff; border-radius:16px; box-shadow:0 12px 30px rgba(0,0,0,0.4);}
.header{display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; padding-bottom:15px; border-bottom:2px solid var(--primary-light);}
.header h2{margin:0; color:var(--primary-color);}
.message{text-align:center; padding:15px; margin-bottom:20px; border-radius:10px; background-color:#d4edda; color:#155724; box-shadow:var(--shadow);}
.view-toggle{text-align:center; margin-bottom:25px; display:flex; justify-content:center; gap:10px;}
.view-toggle input[type="submit"]{background:var(--primary-light); color:#fff; padding:12px 25px; border:none; border-radius:10px; cursor:pointer; font-weight:600; transition:var(--transition);}
.view-toggle input[type="submit"]:hover{background:var(--primary-color);}
.view-toggle input[type="submit"][style*="font-weight:bold"]{background:var(--primary-color); font-weight:bold;}
.table-container{overflow-x:auto; border-radius:12px; box-shadow:var(--shadow); margin-bottom:25px;}
table{width:100%; border-collapse:separate; border-spacing:0; background:#fff; border-radius:12px;}
table th{background:linear-gradient(to bottom, var(--primary-color), var(--primary-dark)); color:white; padding:16px; text-align:left; font-weight:600; position:sticky; top:0;}
table td{padding:14px 16px; border-bottom:1px solid #e0e0e0; transition:var(--transition);}
table tr:nth-child(even){background-color:#f8f9fa;}
table tr:hover{background-color:#e9f7f9; transform:scale(1.01); box-shadow:0 2px 8px rgba(0,0,0,0.05);}
.btn{display:inline-flex; align-items:center; gap:8px; background:var(--primary-color); color:#fff; padding:8px 12px; border-radius:8px; text-decoration:none; font-weight:600; cursor:pointer; transition:var(--transition);}
.btn:hover{background:var(--primary-dark);}
.status-badge{display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:20px; font-size:0.85rem; font-weight:600;}
.status-available{background-color:rgba(46,204,113,0.2); color:#2ecc71;}
.status-occupied{background-color:rgba(231,76,60,0.2); color:#e74c3c;}
.status-maintenance{background-color:rgba(241,196,15,0.2); color:#f1c40f;}
.species-badge{display:inline-block; padding:4px 8px; border-radius:12px; font-size:0.75rem; font-weight:600; margin:2px;}
.species-dog{background-color:rgba(155,89,182,0.2); color:#9b59b6;}
.species-cat{background-color:rgba(52,152,219,0.2); color:#3498db;}
.species-bird{background-color:rgba(241,196,15,0.2); color:#f1c40f;}
.species-rabbit{background-color:rgba(230,126,34,0.2); color:#e67e22;}
.species-general{background-color:rgba(149,165,166,0.2); color:#95a5a6;}
.species-emergency{background-color:rgba(231,76,60,0.2); color:#e74c3c;}
.type-badge{display:inline-block; padding:4px 8px; border-radius:12px; font-size:0.75rem; font-weight:600; margin:2px; background-color:rgba(19,79,92,0.15); color:#134f5c;}
.action-buttons{display:flex; gap:8px; justify-content:center; flex-wrap:wrap;}
.back-btn{text-decoration:none; display:inline-block; margin-top:20px; padding:10px 16px; background:#60a5b3; color:#fff; border-radius:6px; margin-right:10px;}
</style>
</head>
<body>
<div class="overlay">
<div class="container">
<?php if(isset($_GET['message'])){ echo "<div class='message'>".htmlspecialchars($_GET['message'])."</div>"; } ?>

<div class="header">
    <h2>Registered Kennels</h2>
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
<th>Name</th>
<th>Dimensions</th>
<th>Species</th>
<th>Type</th>
<th>Status</th>
<th>Notes</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php
if($result->num_rows>0){
    while($row=$result->fetch_assoc()){
        $capacity=calculate_kennel_capacity($row['max_width'],$row['max_height']);
        echo "<tr>";
        echo "<td>{$row['kennel_id']}</td>";
        echo "<td>{$row['kennel_name']}</td>";
        echo "<td>{$row['max_width']}m × {$row['max_height']}m</td>";
        
        // Species compatibility badge
        echo "<td><span class='species-badge species-".strtolower($row['species_compatibility'])."'>";
        echo "<i class='fas fa-";
        switch(strtolower($row['species_compatibility'])) {
            case 'dog': echo "dog"; break;
            case 'cat': echo "cat"; break;
            case 'bird': echo "crow"; break;
            case 'rabbit': echo "paw"; break;
            case 'emergency': echo "first-aid"; break;
            default: echo "paw";
        }
        echo "'></i> {$row['species_compatibility']}</span></td>";
        
        // Kennel type badge
        echo "<td><span class='type-badge'>{$row['kennel_type']}</span></td>";
        
        // Status badge
        echo "<td><span class='status-badge ";
        switch($row['status']){
            case 'Available': echo 'status-available'; break;
            case 'Occupied': echo 'status-occupied'; break;
            case 'Maintenance': echo 'status-maintenance'; break;
        }
        echo "'><i class='fas fa-";
        switch($row['status']){
            case 'Available': echo 'check-circle'; break;
            case 'Occupied': echo 'times-circle'; break;
            case 'Maintenance': echo 'tools'; break;
        }
        echo "'></i> {$row['status']}</span></td>";
        
        echo "<td>".htmlspecialchars($row['notes'])."</td>";
        echo "<td class='action-buttons'>";
        if($view=='active'){
            echo "<a class='btn' href='edit_kennel.php?id={$row['kennel_id']}'><i class='fas fa-edit'></i> Edit</a>";
            echo "<a class='btn' href='?action=delete&id={$row['kennel_id']}&view={$view}' onclick='return confirm(\"Move to archive?\")'><i class='fas fa-archive'></i> Archive</a>";
        } else {
            echo "<a class='btn' href='?action=restore&id={$row['kennel_id']}&view={$view}' onclick='return confirm(\"Restore this kennel?\")'><i class='fas fa-undo'></i> Restore</a>";
            echo "<a class='btn' href='?action=permanent_delete&id={$row['kennel_id']}&view={$view}' onclick='return confirm(\"Permanently delete? This cannot be undone.\")'><i class='fas fa-trash'></i> Delete</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
}else{
    echo "<tr><td colspan='8' style='text-align:center; padding:20px;'>No kennels found.</td></tr>";
}
?>
</tbody>
</table>
</div>

<div style="display: flex; justify-content: center; margin-top: 20px;">
    <a href="register_kennel.html" class="back-btn">Register New Kennel</a>
    <a href="admin dashboard.php" class="back-btn">Back to Dashboard</a>
</div>

</div>
</div>
</body>
</html>