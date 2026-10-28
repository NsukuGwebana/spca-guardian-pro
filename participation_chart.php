<?php
require_once __DIR__ . '/config.php';
// Database connection - directly included instead of separate file
$serverName = DB_HOST;
$username = DB_USER; 
$password = DB_PASS;
$database = DB_NAME;

// Create connection
$conn = new mysqli($serverName, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    $dbError = true;
    $errorMessage = "Connection failed: " . $conn->connect_error;
} else {
    $dbError = false;
}

// Initialize variables
$volunteerData = array();

// Get volunteer participation data if connection is successful
if (!$dbError) {
    try {
        // Query based on your community_engagement table structure
        $sql = "SELECT 
                    CONCAT(volunteer_Fname, ' ', volunteer_Lname) as volunteer_name,
                    COUNT(*) as total_participation
                FROM community_engagement 
                WHERE volunteer_Fname IS NOT NULL AND volunteer_Lname IS NOT NULL
                GROUP BY volunteer_Fname, volunteer_Lname
                ORDER BY lapse_time DESC";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $volunteerData[] = $row;
            }
        } else {
            throw new Exception("No volunteer participation data found in database");
        }
    } catch (Exception $e) {
        $dbError = true;
        $errorMessage = $e->getMessage();
    }
}

// If there's an error or no data, use sample data
if ($dbError || empty($volunteerData)) {
    // Sample data for demonstration
    $volunteerData = array(
        array("volunteer_name" => "Sarah Johnson", "total_participation" => 42),
        array("volunteer_name" => "Michael Chen", "total_participation" => 38),
        array("volunteer_name" => "Emma Williams", "total_participation" => 31),
        array("volunteer_name" => "James Wilson", "total_participation" => 27),
        array("volunteer_name" => "Olivia Brown", "total_participation" => 23),
        array("volunteer_name" => "Robert Davis", "total_participation" => 19),
        array("volunteer_name" => "Sophia Miller", "total_participation" => 15)
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Volunteer Participation</title>
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

.db-error {
    background: rgba(255, 100, 100, 0.2);
    border: 1px solid rgba(255, 100, 100, 0.5);
    border-radius: 8px;
    padding: 15px;
    margin: 20px auto;
    max-width: 600px;
    text-align: center;
}

.db-success {
    background: rgba(100, 255, 100, 0.2);
    border: 1px solid rgba(100, 255, 100, 0.5);
    border-radius: 8px;
    padding: 15px;
    margin: 20px auto;
    max-width: 600px;
    text-align: center;
}

.info-box {
    background: rgba(255, 200, 100, 0.2);
    border: 1px solid rgba(255, 200, 100, 0.5);
    border-radius: 8px;
    padding: 15px;
    margin: 20px auto;
    max-width: 600px;
    text-align: center;
}
</style>
</head>
<body>
<div class="overlay">
    <h1>Volunteer Participation</h1>
    
    <?php if ($dbError): ?>
    <div class="db-error">
        <h3>Database Connection Issue</h3>
        <p>Showing sample data. Error: <?php echo $errorMessage; ?></p>
        <p>Please check your database connection parameters.</p>
    </div>
    <?php else: ?>
    <div class="db-success">
        <h3>Data Loaded Successfully</h3>
        <p>Showing volunteer participation data directly from the database.</p>
        <p>Table: community_engagement | Volunteers Found: <?php echo count($volunteerData); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="info-box">
        <h3>About This Report</h3>
        <p>This chart shows volunteer participation based on activity records in the community_engagement table.</p>
    </div>
    
    <div class="chart-container">
        <div class="pie-chart" id="piechart"></div>
        
        <div class="stats-box">
            <h2>Participation Statistics</h2>
            <?php 
            $totalParticipation = 0;
            foreach ($volunteerData as $volunteer) {
                $totalParticipation += $volunteer['total_participation'];
            }
            
            foreach ($volunteerData as $volunteer): 
                $percentage = $totalParticipation > 0 ? round(($volunteer['total_participation'] / $totalParticipation) * 100, 1) : 0;
            ?>
            <div class="stats-item">
                <span><?= htmlspecialchars($volunteer['volunteer_name']) ?>:</span>
                <span><?= $volunteer['total_participation'] ?> (<?= $percentage ?>%)</span>
            </div>
            <?php endforeach; ?>
            
            <div class="stats-item" style="margin-top: 20px; border-top: 2px solid #60a5fa; font-weight: bold;">
                <span>Total Participation:</span>
                <span><?= $totalParticipation ?></span>
            </div>
            
            <div class="stats-item">
                <span>Number of Volunteers:</span>
                <span><?= count($volunteerData) ?></span>
            </div>
        </div>
    </div>

   <button class="back-btn" onclick="history.back()">Back</button>
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
    data.addColumn('string', 'Volunteer');
    data.addColumn('number', 'Participation');
    
    data.addRows([
        <?php
        foreach ($volunteerData as $volunteer) {
            echo "['" . addslashes($volunteer['volunteer_name']) . "', " . $volunteer['total_participation'] . "],";
        }
        ?>
    ]);

    // Set chart options
    var options = {
        title: 'Volunteer Total Hours Spent',
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
            6: { color: '#FF6D01' },
            7: { color: '#46BDC6' },
            8: { color: '#FF6B8B' },
            9: { color: '#7BAAF7' }
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
<?php 
// Close connection if it's open
if (isset($conn) && $conn) {
    $conn->close();
}