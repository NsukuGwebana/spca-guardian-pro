<?php
require_once __DIR__ . '/config.php';
// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get species distribution data
$sql = "SELECT Species, COUNT(*) as count 
        FROM animals 
        WHERE is_deleted = 0 
        GROUP BY Species 
        ORDER BY count DESC";
$result = $conn->query($sql);

$speciesData = array();
while ($row = $result->fetch_assoc()) {
    $speciesData[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Animal Species Distribution</title>
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

.chart-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 30px;
    margin: 30px 0;
}

.pie-chart {
    width: 600px;
    height: 400px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 20px;
}

.stats-box {
    width: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 20px;
}

.stats-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
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

.legend {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 20px;
    justify-content: center;
}

.legend-item {
    display: flex;
    align-items: center;
    margin-right: 15px;
}

.legend-color {
    width: 20px;
    height: 20px;
    margin-right: 5px;
    border-radius: 3px;
}
</style>
</head>
<body>
<div class="overlay">
    <h1>Animal Species Distribution</h1>
    
    <div class="chart-container">
        <div class="pie-chart" id="piechart"></div>
        
        <div class="stats-box">
            <h2>Species Statistics</h2>
            <?php 
            $totalAnimals = 0;
            foreach ($speciesData as $species) {
                $totalAnimals += $species['count'];
            }
            
            foreach ($speciesData as $species): 
                $percentage = round(($species['count'] / $totalAnimals) * 100, 1);
            ?>
            <div class="stats-item">
                <span><?= htmlspecialchars($species['Species']) ?>:</span>
                <span><?= $species['count'] ?> (<?= $percentage ?>%)</span>
            </div>
            <?php endforeach; ?>
            
            <div class="stats-item" style="margin-top: 20px; border-top: 2px solid #60a5fa; font-weight: bold;">
                <span>Total Animals:</span>
                <span><?= $totalAnimals ?></span>
            </div>
        </div>
    </div>

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
// instantiates the pie chart, passes in the data and draws it
function drawChart() {
    // Create the data table
    var data = new google.visualization.DataTable();
    data.addColumn('string', 'Species');
    data.addColumn('number', 'Count');
    
    data.addRows([
        <?php
        foreach ($speciesData as $species) {
            echo "['" . addslashes($species['Species']) . "', " . $species['count'] . "],";
        }
        ?>
    ]);

    // Set chart options
    var options = {
        title: 'Animal Distribution by Species',
        titleTextStyle: {
            color: '#fff',
            fontSize: 20,
            bold: true
        },
        width: 600,
        height: 400,
        backgroundColor: 'transparent',
        legend: {
            textStyle: {
                color: '#fff'
            },
            position: 'labeled'
        },
        pieSliceText: 'value',
        slices: {
            0: { color: '#4285F4' },
            1: { color: '#EA4335' },
            2: { color: '#FBBC05' },
            3: { color: '#34A853' },
            4: { color: '#9334E6' },
            5: { color: '#00B5F7' },
            6: { color: '#FF6D01' }
        },
        chartArea: {
            width: '90%',
            height: '80%'
        }
    };

    // Instantiate and draw our chart, passing in some options
    var chart = new google.visualization.PieChart(document.getElementById('piechart'));
    chart.draw(data, options);
}
</script>
</body>
</html>
<?php $conn->close(); ?>
