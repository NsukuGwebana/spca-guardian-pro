<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Reports Analysis</title>
    
    <!-- Load Google Charts Library -->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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

        .chart-container {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }

        .chart {
            width: 100%;
            height: 500px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            padding: 15px;
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
        
        .error {
            color: #e74c3c;
            padding: 10px;
            background: rgba(231, 76, 60, 0.2);
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #e74c3c;
        }
        
        .info {
            color: #3498db;
            padding: 10px;
            background: rgba(52, 152, 219, 0.2);
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #e0f7fa;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #b0e0e6;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="overlay">
    <h1>Medical Reports Analysis</h1>
    <p style="text-align: center; margin-bottom: 30px;">Analyze appointment trends and treatment statistics</p>

    <?php
require_once __DIR__ . '/config.php';
    // Database connection and data fetching
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    
    $serverName = DB_HOST;
    $username   = DB_USER;
    $password   = DB_PASS;
    $database   = DB_NAME;
    
    $conn = new mysqli($serverName, $username, $password, $database);
    
    // Check for connection error
    if ($conn->connect_error) { 
        echo "<div class='error'>Database Connection failed: " . $conn->connect_error . "</div>"; 
        exit; 
    }
    
    // Initialize data arrays
    $monthlyAppointmentsData = [];
    $quarterlyAppointmentsData = [];
    $monthlyTreatmentsData = [];
    $quarterlyTreatmentsData = [];
    $statusData = [];
    $treatmentTypesData = [];
    $errors = [];
    
    // Get filter values from request
    $viewType = isset($_GET['view']) ? $_GET['view'] : 'monthly';
    $yearFilter = isset($_GET['year']) ? $_GET['year'] : date('Y');
    
    // Query to get appointments by month
    $monthlyAppointmentsQuery = "
        SELECT 
            DATE_FORMAT(app_date, '%Y-%m') AS period,
            DATE_FORMAT(app_date, '%b %Y') AS label,
            COUNT(*) AS total_appointments
        FROM appointments 
        WHERE YEAR(app_date) = " . intval($yearFilter) . "
        GROUP BY DATE_FORMAT(app_date, '%Y-%m')
        ORDER BY period
    ";
    $monthlyAppointmentsResult = $conn->query($monthlyAppointmentsQuery);
    
    if ($monthlyAppointmentsResult) {
        if ($monthlyAppointmentsResult->num_rows > 0) {
            while ($row = $monthlyAppointmentsResult->fetch_assoc()) {
                $monthlyAppointmentsData[] = $row;
            }
        } else {
            $errors[] = "No monthly appointment data found for " . $yearFilter;
        }
    } else {
        $errors[] = "Error in monthly appointments query: " . $conn->error;
    }
    
    // Query to get appointments by quarter
    $quarterlyAppointmentsQuery = "
        SELECT 
            CONCAT(YEAR(app_date), '-Q', QUARTER(app_date)) AS period,
            CONCAT('Q', QUARTER(app_date), ' ', YEAR(app_date)) AS label,
            COUNT(*) AS total_appointments
        FROM appointments 
        WHERE YEAR(app_date) = " . intval($yearFilter) . "
        GROUP BY YEAR(app_date), QUARTER(app_date)
        ORDER BY period
    ";
    $quarterlyAppointmentsResult = $conn->query($quarterlyAppointmentsQuery);
    
    if ($quarterlyAppointmentsResult) {
        if ($quarterlyAppointmentsResult->num_rows > 0) {
            while ($row = $quarterlyAppointmentsResult->fetch_assoc()) {
                $quarterlyAppointmentsData[] = $row;
            }
        } else {
            $errors[] = "No quarterly appointment data found for " . $yearFilter;
        }
    } else {
        $errors[] = "Error in quarterly appointments query: " . $conn->error;
    }
    
    // Query to get treatments by month
    $monthlyTreatmentsQuery = "
        SELECT 
            DATE_FORMAT(treatment_date, '%Y-%m') AS period,
            DATE_FORMAT(treatment_date, '%b %Y') AS label,
            COUNT(*) AS total_treatments
        FROM treatments 
        WHERE YEAR(treatment_date) = " . intval($yearFilter) . "
        GROUP BY DATE_FORMAT(treatment_date, '%Y-%m')
        ORDER BY period
    ";
    $monthlyTreatmentsResult = $conn->query($monthlyTreatmentsQuery);
    
    if ($monthlyTreatmentsResult) {
        if ($monthlyTreatmentsResult->num_rows > 0) {
            while ($row = $monthlyTreatmentsResult->fetch_assoc()) {
                $monthlyTreatmentsData[] = $row;
            }
        } else {
            $errors[] = "No monthly treatment data found for " . $yearFilter;
        }
    } else {
        $errors[] = "Error in monthly treatments query: " . $conn->error;
    }
    
    // Query to get treatments by quarter
    $quarterlyTreatmentsQuery = "
        SELECT 
            CONCAT(YEAR(treatment_date), '-Q', QUARTER(treatment_date)) AS period,
            CONCAT('Q', QUARTER(treatment_date), ' ', YEAR(treatment_date)) AS label,
            COUNT(*) AS total_treatments
        FROM treatments 
        WHERE YEAR(treatment_date) = " . intval($yearFilter) . "
        GROUP BY YEAR(treatment_date), QUARTER(treatment_date)
        ORDER BY period
    ";
    $quarterlyTreatmentsResult = $conn->query($quarterlyTreatmentsQuery);
    
    if ($quarterlyTreatmentsResult) {
        if ($quarterlyTreatmentsResult->num_rows > 0) {
            while ($row = $quarterlyTreatmentsResult->fetch_assoc()) {
                $quarterlyTreatmentsData[] = $row;
            }
        } else {
            $errors[] = "No quarterly treatment data found for " . $yearFilter;
        }
    } else {
        $errors[] = "Error in quarterly treatments query: " . $conn->error;
    }
    
    // Query to get appointments by status
    $statusQuery = "
        SELECT 
            app_status, 
            COUNT(*) AS total 
        FROM appointments 
        WHERE YEAR(app_date) = " . intval($yearFilter) . "
        GROUP BY app_status
    ";
    $statusResult = $conn->query($statusQuery);
    
    if ($statusResult) {
        if ($statusResult->num_rows > 0) {
            while ($row = $statusResult->fetch_assoc()) {
                $statusData[] = $row;
            }
        } else {
            $errors[] = "No status data found for " . $yearFilter;
        }
    } else {
        $errors[] = "Error in status query: " . $conn->error;
    }
    
    // Query to get treatment types data
    $treatmentTypesQuery = "
        SELECT 
            treatment_types,
            COUNT(*) AS total_treatments
        FROM treatments 
        WHERE YEAR(treatment_date) = " . intval($yearFilter) . "
        GROUP BY treatment_types
        ORDER BY total_treatments DESC
    ";
    $treatmentTypesResult = $conn->query($treatmentTypesQuery);
    
    if ($treatmentTypesResult) {
        if ($treatmentTypesResult->num_rows > 0) {
            while ($row = $treatmentTypesResult->fetch_assoc()) {
                $treatmentTypesData[] = $row;
            }
        } else {
            $errors[] = "No treatment types data found for " . $yearFilter;
        }
    } else {
        $errors[] = "Error in treatment types query: " . $conn->error;
    }
    
    // Get unique years for filter dropdown
    $yearsQuery = "SELECT DISTINCT YEAR(app_date) as year FROM appointments WHERE app_date IS NOT NULL ORDER BY year DESC";
    $yearsResult = $conn->query($yearsQuery);
    $years = [];
    if ($yearsResult) {
        while ($row = $yearsResult->fetch_assoc()) {
            $years[] = $row['year'];
        }
    }
    
    // Close the connection
    $conn->close();
    
    // Display any errors
    if (!empty($errors)) {
        echo "<div class='info'><strong>Information:</strong><ul>";
        foreach ($errors as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul></div>";
    }
    ?>

    <!-- Filter Form -->
    <div class="filter-form">
        <h2>Filter Reports</h2>
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="view">View By:</label>
                    <select name="view" id="view">
                        <option value="monthly" <?php echo $viewType == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                        <option value="quarterly" <?php echo $viewType == 'quarterly' ? 'selected' : ''; ?>>Quarterly</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="year">Year:</label>
                    <select name="year" id="year">
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo $year; ?>" <?php echo $year == $yearFilter ? 'selected' : ''; ?>>
                                <?php echo $year; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-buttons">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-container">
        <?php
        // Calculate stats
        $totalAppointments = 0;
        $completedAppointments = 0;
        $totalTreatments = 0;
        
        if ($viewType === 'monthly') {
            foreach ($monthlyAppointmentsData as $data) {
                $totalAppointments += $data['total_appointments'];
            }
            foreach ($monthlyTreatmentsData as $data) {
                $totalTreatments += $data['total_treatments'];
            }
        } else {
            foreach ($quarterlyAppointmentsData as $data) {
                $totalAppointments += $data['total_appointments'];
            }
            foreach ($quarterlyTreatmentsData as $data) {
                $totalTreatments += $data['total_treatments'];
            }
        }
        
        foreach ($statusData as $data) {
            if ($data['app_status'] === 'Completed') {
                $completedAppointments = $data['total'];
            }
        }
        
        $completionRate = $totalAppointments > 0 ? round(($completedAppointments / $totalAppointments) * 100, 1) : 0;
        ?>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $totalAppointments; ?></div>
            <div class="stat-label">Total Appointments</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $completedAppointments; ?></div>
            <div class="stat-label">Completed Appointments</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $completionRate; ?>%</div>
            <div class="stat-label">Completion Rate</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?php echo $totalTreatments; ?></div>
            <div class="stat-label">Total Treatments</div>
        </div>
    </div>
    
    <!-- Chart Containers -->
    <div class="chart-container">
        <h2>Appointments by <?php echo $viewType === 'monthly' ? 'Month' : 'Quarter'; ?> (<?php echo $yearFilter; ?>)</h2>
        <div class="chart">
            <canvas id="appointmentsChart"></canvas>
        </div>
    </div>
    
    <div class="chart-container">
        <h2>Treatments by <?php echo $viewType === 'monthly' ? 'Month' : 'Quarter'; ?> (<?php echo $yearFilter; ?>)</h2>
        <div class="chart">
            <canvas id="treatmentsChart"></canvas>
        </div>
    </div>
    
    <div class="chart-container">
        <h2>Appointment Status (<?php echo $yearFilter; ?>)</h2>
        <div class="chart">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
    
    <div class="chart-container">
        <h2>Treatment Types (<?php echo $yearFilter; ?>)</h2>
        <div class="chart">
            <canvas id="treatmentTypesChart"></canvas>
        </div>
    </div>

   <div style="display: flex; justify-content: center; gap: 15px; margin-top: 30px;">
    <a href="admin dashboard.php" class="back-btn" onclick="history.back(); return false;">
        Back to Dashboard
    </a>
    <a href="javascript:window.print()" class="back-btn">
         Print
    </a>
</div>

</div>

<script>
    // Prepare data for charts
    const viewType = '<?php echo $viewType; ?>';
    const monthlyAppointmentsData = <?php echo json_encode($monthlyAppointmentsData); ?>;
    const quarterlyAppointmentsData = <?php echo json_encode($quarterlyAppointmentsData); ?>;
    const monthlyTreatmentsData = <?php echo json_encode($monthlyTreatmentsData); ?>;
    const quarterlyTreatmentsData = <?php echo json_encode($quarterlyTreatmentsData); ?>;
    const statusData = <?php echo json_encode($statusData); ?>;
    const treatmentTypesData = <?php echo json_encode($treatmentTypesData); ?>;
    
    // Format data based on view type
    let appointmentsLabels, appointmentsValues, treatmentsLabels, treatmentsValues;
    
    if (viewType === 'monthly') {
        appointmentsLabels = monthlyAppointmentsData.map(item => {
            const [year, month] = item.period.split('-');
            return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short' });
        });
        appointmentsValues = monthlyAppointmentsData.map(item => item.total_appointments);
        
        treatmentsLabels = monthlyTreatmentsData.map(item => {
            const [year, month] = item.period.split('-');
            return new Date(year, month - 1).toLocaleDateString('en-US', { month: 'short' });
        });
        treatmentsValues = monthlyTreatmentsData.map(item => item.total_treatments);
    } else {
        appointmentsLabels = quarterlyAppointmentsData.map(item => item.label);
        appointmentsValues = quarterlyAppointmentsData.map(item => item.total_appointments);
        
        treatmentsLabels = quarterlyTreatmentsData.map(item => item.label);
        treatmentsValues = quarterlyTreatmentsData.map(item => item.total_treatments);
    }
    
    // Format status data for chart
    const statusLabels = statusData.map(item => item.app_status);
    const statusValues = statusData.map(item => item.total);
    const statusColors = statusLabels.map(status => {
        if (status === 'Completed') return '#2ecc71';
        if (status === 'Cancelled') return '#e74c3c';
        if (status === 'Scheduled') return '#f39c12';
        return '#3498db';
    });
    
    // Format treatment types data for chart
    const treatmentTypesLabels = treatmentTypesData.map(item => item.treatment_types);
    const treatmentTypesValues = treatmentTypesData.map(item => item.total_treatments);
    const treatmentTypesColors = [
        '#3498db', '#2ecc71', '#f39c12', '#e74c3c', '#9b59b6', '#1abc9c',
        '#34495e', '#16a085', '#27ae60', '#2980b9', '#8e44ad', '#d35400',
        '#c0392b', '#bdc3c7', '#7f8c8d', '#f1c40f', '#e67e22', '#95a5a6'
    ];
    
    // Create charts when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        // Appointments chart (Column chart)
        new Chart(document.getElementById('appointmentsChart'), {
            type: 'bar',
            data: {
                labels: appointmentsLabels,
                datasets: [{
                    label: 'Appointments',
                    data: appointmentsValues,
                    backgroundColor: '#3498db',
                    borderColor: '#2980b9',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Appointments'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: viewType === 'monthly' ? 'Month' : 'Quarter'
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#333'
                        }
                    }
                }
            }
        });
        
        // Treatments chart (Column chart)
        new Chart(document.getElementById('treatmentsChart'), {
            type: 'bar',
            data: {
                labels: treatmentsLabels,
                datasets: [{
                    label: 'Treatments',
                    data: treatmentsValues,
                    backgroundColor: '#2ecc71',
                    borderColor: '#27ae60',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Treatments'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: viewType === 'monthly' ? 'Month' : 'Quarter'
                        },
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        labels: {
                            color: '#333'
                        }
                    }
                }
            }
        });
        
        // Appointment status chart (Pie chart)
        new Chart(document.getElementById('statusChart'), {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusValues,
                    backgroundColor: statusColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#333'
                        }
                    }
                }
            }
        });
        
        // Treatment types chart (Pie chart)
        new Chart(document.getElementById('treatmentTypesChart'), {
            type: 'pie',
            data: {
                labels: treatmentTypesLabels,
                datasets: [{
                    data: treatmentTypesValues,
                    backgroundColor: treatmentTypesColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#333'
                        }
                    }
                }
            }
        });
    });
</script>
</body>
</html>