<?php
require_once __DIR__ . '/config.php';
// Database connection
$serverName = DB_HOST;
$username = DB_USER; 
$password = DB_PASS;
$database = DB_NAME;
$conn = new mysqli($serverName, $username, $password, $database);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch Data for Cruelty Reports by Animal Type
$animalTypeQuery = "SELECT animal_type, COUNT(*) AS count FROM cruelty_report GROUP BY animal_type";
$animalTypeResult = $conn->query($animalTypeQuery);

// Fetch Data for Cruelty Reports by Cruelty Type
$crueltyTypeQuery = "SELECT cruelty_type, COUNT(*) AS count FROM cruelty_report GROUP BY cruelty_type";
$crueltyTypeResult = $conn->query($crueltyTypeQuery);

// Fetch Data for Investigations by Case Status
$caseStatusQuery = "SELECT status, COUNT(*) AS count FROM cruelty_investigation GROUP BY status";
$caseStatusResult = $conn->query($caseStatusQuery);

// Prepare Data for Google Charts (convert it to a JSON format)
$animalData = [];
while ($row = $animalTypeResult->fetch_assoc()) {
    $animalData[] = [$row['animal_type'], (int)$row['count']];
}

$crueltyData = [];
while ($row = $crueltyTypeResult->fetch_assoc()) {
    $crueltyData[] = [$row['cruelty_type'], (int)$row['count']];
}

$caseStatusData = [];
while ($row = $caseStatusResult->fetch_assoc()) {
    $caseStatusData[] = [$row['status'], (int)$row['count']];
}

$conn->close();

// Encode the data in JSON format for use in Google Charts
$animalDataJson = json_encode($animalData);
$crueltyDataJson = json_encode($crueltyData);
$caseStatusDataJson = json_encode($caseStatusData);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cruelty Reports and Investigations Analysis</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {packages: ['corechart', 'bar']});
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            // Animal Types Chart
            var animalData = google.visualization.arrayToDataTable(<?php echo $animalDataJson; ?>);
            var animalOptions = {
                title: 'Cruelty Reports by Animal Type',
                hAxis: {title: 'Animal Type'},
                vAxis: {title: 'Number of Reports'},
                bars: 'vertical'
            };
            var animalChart = new google.visualization.BarChart(document.getElementById('animal_chart'));
            animalChart.draw(animalData, animalOptions);

            // Cruelty Types Chart
            var crueltyData = google.visualization.arrayToDataTable(<?php echo $crueltyDataJson; ?>);
            var crueltyOptions = {
                title: 'Cruelty Reports by Type',
                hAxis: {title: 'Cruelty Type'},
                vAxis: {title: 'Number of Reports'},
                bars: 'vertical'
            };
            var crueltyChart = new google.visualization.BarChart(document.getElementById('cruelty_chart'));
            crueltyChart.draw(crueltyData, crueltyOptions);

            // Case Status Chart
            var caseStatusData = google.visualization.arrayToDataTable(<?php echo $caseStatusDataJson; ?>);
            var caseStatusOptions = {
                title: 'Investigation Case Status',
                hAxis: {title: 'Case Status'},
                vAxis: {title: 'Number of Investigations'},
                bars: 'vertical'
            };
            var caseStatusChart = new google.visualization.BarChart(document.getElementById('case_status_chart'));
            caseStatusChart.draw(caseStatusData, caseStatusOptions);
        }
    </script>
</head>
<body>
    <h1>Cruelty Reports and Investigations Analysis</h1>
    <div id="animal_chart" style="width: 900px; height: 500px;"></div>
    <div id="cruelty_chart" style="width: 900px; height: 500px;"></div>
    <div id="case_status_chart" style="width: 900px; height: 500px;"></div>
</body>
</html>
