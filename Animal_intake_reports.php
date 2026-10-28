
<?php
require_once __DIR__ . '/config.php';
// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission for filtering
$whereClause = "is_deleted = 0"; // Only show non-deleted records
$species_filter = "";
$status_filter = "";
$health_filter = "";
$date_from_filter = "";
$date_to_filter = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $species_filter = $_POST['species'] ?? '';
    $status_filter = $_POST['status'] ?? '';
    $health_filter = $_POST['health_status'] ?? '';
    $date_from_filter = $_POST['date_from'] ?? '';
    $date_to_filter = $_POST['date_to'] ?? '';
    
    if (!empty($species_filter)) {
        $whereClause .= " AND Species = '" . $conn->real_escape_string($species_filter) . "'";
    }
    
    if (!empty($status_filter)) {
        $whereClause .= " AND status = '" . $conn->real_escape_string($status_filter) . "'";
    }
    
    if (!empty($health_filter)) {
        $whereClause .= " AND Health_status = '" . $conn->real_escape_string($health_filter) . "'";
    }
    
    if (!empty($date_from_filter)) {
        $whereClause .= " AND Intake_date >= '" . $conn->real_escape_string($date_from_filter) . "'";
    }
    
    if (!empty($date_to_filter)) {
        $whereClause .= " AND Intake_date <= '" . $conn->real_escape_string($date_to_filter) . "'";
    }
}

// Run query to get animal intake data
$sql = "SELECT * FROM animals WHERE $whereClause ORDER BY Intake_date DESC, created_at DESC";
$result = $conn->query($sql);

// Get summary statistics
$summarySql = "SELECT 
    COUNT(*) as total_animals,
    COUNT(CASE WHEN Health_status = 'Healthy' THEN 1 END) as healthy_count,
    COUNT(CASE WHEN Health_status = 'Unhealthy' THEN 1 END) as unhealthy_count,
    COUNT(CASE WHEN status = 'Available' THEN 1 END) as available_count,
    COUNT(CASE WHEN status = 'Adopted' THEN 1 END) as adopted_count,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'Not Available' THEN 1 END) as not_available_count
    FROM animals WHERE $whereClause";
$summaryResult = $conn->query($summarySql);
$summary = $summaryResult->fetch_assoc();

// Handle CSV export
if (isset($_POST['export_csv'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=animal_intake_report.csv');
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, array('ID', 'Name', 'Species', 'Breed', 'Age', 'Color', 'Kennel Status', 
                           'Weight', 'Width', 'Height', 'Health Status', 'Intake Date', 
                           'Status', 'Treatment Status'));
    
    // Data rows
    $exportSql = "SELECT id, Aname, Species, Animal_breed, Aage, Color, Kennel_status, 
                         Aweight, Width, Height, Health_status, Intake_date, status, treatment_status 
                  FROM animals WHERE $whereClause ORDER BY Intake_date DESC";
    $exportResult = $conn->query($exportSql);
    
    while ($row = $exportResult->fetch_assoc()) {
        fputcsv($output, array(
            $row['id'],
            $row['Aname'],
            $row['Species'],
            $row['Animal_breed'],
            $row['Aage'],
            $row['Color'],
            $row['Kennel_status'],
            $row['Aweight'],
            $row['Width'],
            $row['Height'],
            $row['Health_status'],
            $row['Intake_date'],
            $row['status'],
            $row['treatment_status']
        ));
    }
    
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Animal Intake Reports</title>
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
    background: rgba(19, 79, 92, 0.90);
    min-height: 100vh;
    padding: 30px;
}

h1, h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #e0f7fa;
}

.filter-form {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 15px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input, .form-group select {
    width: 100%;
    padding: 8px;
    border-radius: 4px;
    border: 1px solid #60a5fa;
    background: rgba(255, 255, 255, 0.9);
}

.form-buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    justify-content: center;
}

.btn {
    padding: 10px 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    color: #fff;
}

.btn-primary {
    background-color: #60a5fa;
}

.btn-primary:hover {
    background-color: #3b82f6;
}

.btn-success {
    background-color: #10b981;
}

.btn-success:hover {
    background-color: #059669;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: rgba(255, 255, 255, 0.1);
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.summary-value {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.summary-label {
    font-size: 14px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    border: 1px solid #60a5fa;
    padding: 10px;
    text-align: left;
}

th {
    background-color: #134f5c;
    color: #fff;
    position: sticky;
    top: 0;
}

td {
    background-color: rgba(255,255,255,0.1);
}

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

.no-data {
    text-align: center;
    padding: 20px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}

.health-healthy {
    color: #4ade80;
    font-weight: bold;
}

.health-unhealthy {
    color: #f87171;
    font-weight: bold;
}

.status-available {
    color: #4ade80;
}

.status-pending {
    color: #fbbf24;
}

.status-adopted {
    color: #60a5fa;
}

.status-not-available {
    color: #9ca3af;
}
</style>
</head>
<body>
<div class="overlay">
    <h1>Animal Intake Reports</h1>
    
    <!-- Filter Form -->
    <form method="POST" class="filter-form">
        <h2>Filter Reports</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="species">Species</label>
                <select id="species" name="species">
                    <option value="">All Species</option>
                    <option value="Dog" <?= $species_filter == 'Dog' ? 'selected' : '' ?>>Dog</option>
                    <option value="Cat" <?= $species_filter == 'Cat' ? 'selected' : '' ?>>Cat</option>
                    <option value="Bird" <?= $species_filter == 'Bird' ? 'selected' : '' ?>>Bird</option>
                    <option value="Other" <?= $species_filter == 'Other' ? 'selected' : '' ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="Available" <?= $status_filter == 'Available' ? 'selected' : '' ?>>Available</option>
                    <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Adopted" <?= $status_filter == 'Adopted' ? 'selected' : '' ?>>Adopted</option>
                    <option value="Not Available" <?= $status_filter == 'Not Available' ? 'selected' : '' ?>>Not Available</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="health_status">Health Status</label>
                <select id="health_status" name="health_status">
                    <option value="">All Health Statuses</option>
                    <option value="Healthy" <?= $health_filter == 'Healthy' ? 'selected' : '' ?>>Healthy</option>
                    <option value="Unhealthy" <?= $health_filter == 'Unhealthy' ? 'selected' : '' ?>>Unhealthy</option>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="date_from">Intake Date From</label>
                <input type="date" id="date_from" name="date_from" value="<?= $date_from_filter ?>">
            </div>
            
            <div class="form-group">
                <label for="date_to">Intake Date To</label>
                <input type="date" id="date_to" name="date_to" value="<?= $date_to_filter ?>">
            </div>
        </div>
        
        <div class="form-buttons">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <button type="submit" name="export_csv" value="1" class="btn btn-success">Export to CSV</button>
        </div>
    </form>
    
    <!-- Summary Statistics -->
    <h2>Summary</h2>
    <div class="summary-cards">
        <div class="summary-card">
            <div class="summary-value"><?= $summary['total_animals'] ?></div>
            <div class="summary-label">Total Animals</div>
        </div>
        <div class="summary-card">
            <div class="summary-value"><?= $summary['healthy_count'] ?></div>
            <div class="summary-label">Healthy Animals</div>
        </div>
        <div class="summary-card">
            <div class="summary-value"><?= $summary['unhealthy_count'] ?></div>
            <div class="summary-label">Unhealthy Animals</div>
        </div>
        <div class="summary-card">
            <div class="summary-value"><?= $summary['available_count'] ?></div>
            <div class="summary-label">Available for Adoption</div>
        </div>
        <div class="summary-card">
            <div class="summary-value"><?= $summary['adopted_count'] ?></div>
            <div class="summary-label">Adopted</div>
        </div>
    </div>
    
    <!-- Reports Table -->
    <h2>Detailed Report</h2>
    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Species</th>
                    <th>Breed</th>
                    <th>Age</th>
                    <th>Color</th>
                    <th>Health Status</th>
                    <th>Intake Date</th>
                    <th>Status</th>
                    <th>Treatment Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['Aname']) ?></td>
                    <td><?= htmlspecialchars($row['Species']) ?></td>
                    <td><?= htmlspecialchars($row['Animal_breed']) ?></td>
                    <td><?= htmlspecialchars($row['Aage']) ?></td>
                    <td><?= htmlspecialchars($row['Color']) ?></td>
                    <td class="health-<?= strtolower($row['Health_status']) ?>">
                        <?= htmlspecialchars($row['Health_status']) ?>
                    </td>
                    <td><?= htmlspecialchars($row['Intake_date']) ?></td>
                    <td class="status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                        <?= htmlspecialchars($row['status']) ?>
                    </td>
                    <td><?= htmlspecialchars($row['treatment_status']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-data">
            <p>No animal records found.</p>
        </div>
    <?php endif; ?>

    <a href="admin dashboard.php" class="back-btn"> Back to Dashboard</a>
</div>
</body>
</html>
<?php $conn->close(); ?>