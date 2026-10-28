<?php
require_once __DIR__ . '/config.php';
session_start();

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle filter reset
if (isset($_GET['reset_filters']) && $_GET['reset_filters'] == 1) {
    unset($_SESSION['donation_filters']);
    header("Location: " . str_replace("?reset_filters=1", "", $_SERVER['REQUEST_URI']));
    exit();
}

// Handle form submission for filtering
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['donation_filters'] = [
        'time_period' => $_POST['time_period'] ?? 'monthly',
        'year' => $_POST['year'] ?? date('Y'),
        'dtype' => $_POST['dtype'] ?? ''
    ];
}

// Use session filters or defaults
$time_period = $_SESSION['donation_filters']['time_period'] ?? 'monthly';
$year_filter = $_SESSION['donation_filters']['year'] ?? date('Y');
$dtype_filter = $_SESSION['donation_filters']['dtype'] ?? '';

// Get available years for filter dropdown
$yearSql = "SELECT DISTINCT YEAR(ddate) as year FROM donations ORDER BY year DESC";
$yearResult = $conn->query($yearSql);
$years = array();
while ($row = $yearResult->fetch_assoc()) {
    $years[] = $row['year'];
}

// Get donation types for filter dropdown
$typeSql = "SELECT DISTINCT dtype FROM donations WHERE dtype IS NOT NULL AND dtype != '' ORDER BY dtype";
$typeResult = $conn->query($typeSql);
$types = array();
while ($row = $typeResult->fetch_assoc()) {
    $types[] = $row['dtype'];
}

// Build SQL query based on filters
if ($time_period == 'monthly') {
    $groupBy = "YEAR(ddate), MONTH(ddate)";
    $labelFormat = "CONCAT(YEAR(ddate), '-', LPAD(MONTH(ddate), 2, '0'))";
} else {
    $groupBy = "YEAR(ddate)";
    $labelFormat = "YEAR(ddate)";
}

$whereClause = "1=1";
if (!empty($year_filter)) {
    $whereClause .= " AND YEAR(ddate) = " . intval($year_filter);
}
if (!empty($dtype_filter)) {
    $whereClause .= " AND dtype = '" . $conn->real_escape_string($dtype_filter) . "'";
}

$sql = "SELECT 
            $labelFormat as period, 
            SUM(damount) as total_amount,
            COUNT(*) as donation_count
        FROM donations 
        WHERE $whereClause
        GROUP BY $groupBy 
        ORDER BY period";

$result = $conn->query($sql);

$donationData = array();
while ($row = $result->fetch_assoc()) {
    $donationData[] = $row;
}

// Get summary statistics
$summarySql = "SELECT 
                COUNT(*) as total_donations,
                SUM(damount) as total_amount,
                AVG(damount) as avg_amount,
                MAX(damount) as max_amount,
                MIN(damount) as min_amount
            FROM donations 
            WHERE $whereClause";
$summaryResult = $conn->query($summarySql);
$summary = $summaryResult->fetch_assoc();

// Store report data in session for potential export functionality
$_SESSION['last_report_data'] = [
    'filters' => [
        'time_period' => $time_period,
        'year' => $year_filter,
        'dtype' => $dtype_filter
    ],
    'summary' => $summary,
    'donation_data' => $donationData,
    'generated_at' => date('Y-m-d H:i:s')
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Donation Trends Report</title>
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
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 15px;
    justify-content: center;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group select, .form-group input {
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

.btn-reset {
    background-color: #60a5fa;   
}

.btn-primary {
    background-color: #10b981;   
}

.btn-primary:hover {
    background-color: #00c723;
}

.btn-reset:hover {
    background-color: #d10a0a;
}

.chart-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 30px;
    margin: 30px 0;
}

.line-chart {
    width: 800px;
    height: 400px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 20px;
}

.stats-box {
    width: 80%;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 20px;
    margin: 0 auto 30px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.stats-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 6px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: rgba(255, 255, 255, 0.1);
}

.data-table th, .data-table td {
    border: 1px solid #60a5fa;
    padding: 10px;
    text-align: left;
}

.data-table th {
    background-color: #134f5c;
    color: #fff;
}

.data-table td {
    background-color: rgba(255,255,255,0.1);
}

.back-btn {
    display: block;
    width: fit-content;
    background: #275792;
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

.positive-trend {
    color: #00f62d;
}

.negative-trend {
    color: #f87171;
}
</style>
</head>
<body>
<div class="overlay">
    <h1>Donation Trends Report</h1>
    
    <!-- Filter Form -->
    <form method="POST" class="filter-form">
        <h2>Filter Data</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="time_period">Time Period</label>
                <select id="time_period" name="time_period">
                    <option value="monthly" <?= $time_period == 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="yearly" <?= $time_period == 'yearly' ? 'selected' : '' ?>>Yearly</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="year">Year</label>
                <select id="year" name="year">
                    <option value="">All Years</option>
                    <?php foreach ($years as $year): ?>
                    <option value="<?= $year ?>" <?= $year_filter == $year ? 'selected' : '' ?>><?= $year ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="dtype">Donation Type</label>
                <select id="dtype" name="dtype">
                    <option value="">All Types</option>
                    <?php foreach ($types as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>" <?= $dtype_filter == $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-buttons">
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <button type="button" class="btn btn-reset" onclick="resetFilters()">Reset Filters</button>
        </div>
    </form>
    
    <!-- Summary Statistics -->
    <div class="stats-box">
        <h2>Summary Statistics</h2>
        <div class="stats-grid">
            <div class="stats-item">
                <span>Total Donations:</span>
                <span><?= $summary['total_donations'] ?? 0 ?></span>
            </div>
            <div class="stats-item">
                <span>Total Amount:</span>
                <span>R<?= number_format($summary['total_amount'] ?? 0, 2) ?></span>
            </div>
            <div class="stats-item">
                <span>Average Donation:</span>
                <span>R<?= number_format($summary['avg_amount'] ?? 0, 2) ?></span>
            </div>
            <div class="stats-item">
                <span>Largest Donation:</span>
                <span>R<?= number_format($summary['max_amount'] ?? 0, 2) ?></span>
            </div>
        </div>
    </div>
    
    <!-- Chart Container -->
    <div class="chart-container">
        <div class="line-chart" id="linechart"></div>
    </div>
    
    <!-- Data Table -->
    <h2>Detailed Data</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Period</th>
                <th>Donation Count</th>
                <th>Total Amount</th>
                <th>Average Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $prevAmount = null;
            foreach ($donationData as $data): 
                $trendClass = '';
                if ($prevAmount !== null) {
                    $trendClass = $data['total_amount'] > $prevAmount ? 'positive-trend' : 'negative-trend';
                }
                $prevAmount = $data['total_amount'];
                
                // Calculate average, avoiding division by zero
                $average = $data['donation_count'] > 0 ? $data['total_amount'] / $data['donation_count'] : 0;
            ?>
            <tr>
                <td><?= htmlspecialchars($data['period']) ?></td>
                <td><?= $data['donation_count'] ?></td>
                <td class="<?= $trendClass ?>">R<?= number_format($data['total_amount'], 2) ?></td>
                <td>R<?= number_format($average, 2) ?></td>
            </tr>
            <?php endforeach; ?>
            
            <?php if (empty($donationData)): ?>
            <tr>
                <td colspan="4" style="text-align: center;">No donation data found for the selected filters</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="admin dashboard.php" class="back-btn">Back to Dashboard</a>
</div>

<!-- Load Google Charts -->
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript">
// Load the Visualization API and the corechart package
google.charts.load('current', {'packages':['corechart']});

// Set a callback to run when the Google Visualization API is loaded
google.charts.setOnLoadCallback(drawChart);

// Callback that creates and populates a data table,
// instantiates the line chart, passes in the data and draws it
function drawChart() {
    // Create the data table
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Period');
    data.addColumn('number', 'Total Amount');
    data.addColumn('number', 'Donation Count');
    
    data.addRows([
        <?php
        foreach ($donationData as $data) {
            echo "['" . $data['period'] . "', " . $data['total_amount'] . ", " . $data['donation_count'] . "],";
        }
        ?>
    ]);

    // Set chart options
    var options = {
        title: 'Donation Trends Over Time',
        titleTextStyle: {
            color: '#fff',
            fontSize: 20,
            bold: true
        },
        width: 800,
        height: 400,
        backgroundColor: 'transparent',
        legend: {
            textStyle: {
                color: '#fff'
            },
            position: 'top'
        },
        hAxis: {
            title: 'Period',
            titleTextStyle: {
                color: '#fff'
            },
            textStyle: {
                color: '#fff'
            },
            gridlines: {
                color: 'transparent'
            }
        },
        vAxis: {
            title: 'Amount (R)',
            titleTextStyle: {
                color: '#fff'
            },
            textStyle: {
                color: '#fff'
            },
            gridlines: {
                color: 'rgba(255, 255, 255, 0.1)'
            }
        },
        colors: ['#e2c507', '#47f867'],
        chartArea: {
            width: '85%',
            height: '70%'
        }
    };

    // Instantiate and draw our chart, passing in some options
    var chart = new google.visualization.LineChart(document.getElementById('linechart'));
    chart.draw(data, options);
}

// Function to reset filters
function resetFilters() {
    window.location.href = '?reset_filters=1';
}

// Handle window resize to redraw chart
window.addEventListener('resize', drawChart);
</script>
</body>
</html>
<?php 
$conn->close(); 
?>