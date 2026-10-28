<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Reporting with Google Charts</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #428bca;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="date"], input[type="submit"] {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        input[type="submit"] {
            background-color: #428bca;
            color: white;
            cursor: pointer;
            border: none;
        }
        input[type="submit"]:hover {
            background-color: #3276b1;
        }
        .chart-container {
            width: 100%;
            height: 500px;
            margin: 20px 0;
        }
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PHP Reporting with Google Charts</h1>
        
        <h2>Working with Multiple Tables</h2>
        <p>Most real-world web apps need to get data from multiple tables in a single query using JOINS.</p>
        
        <h3>Scheduling Boards</h3>
        <form action="" method="POST">
            <div class="form-group">
                <label for="date">Boards to schedule for:</label>
                <input type="date" name="date" id="date" required>
                <input type="submit" name="submit_schedule" value="Go">
            </div>
        </form>
        
        <?php
        // Simulate database connection and query
        if (isset($_POST['submit_schedule'])) {
            $date = $_POST['date'];
            echo "<h4>Boards to schedule for: " . htmlspecialchars($date) . "</h4>";
            
            // Simulated database results
            $schedule_data = array(
                array('picture' => 'surfboard.jpg', 'category' => 'Funboard', 'type' => 'Surfboard', 
                      'length' => '6.8', 'rate' => 'R250', 'name' => 'David', 'surname' => 'Xoli', 'mobile' => '0725551234'),
                array('picture' => 'longboard.jpg', 'category' => 'Longboard', 'type' => 'Surfboard', 
                      'length' => '9.2', 'rate' => 'R300', 'name' => 'Themba', 'surname' => 'Bool', 'mobile' => '0831234567')
            );
            
            echo "<table>";
            echo "<tr>
                    <th>Picture</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Length</th>
                    <th>Price</th>
                    <th>Name</th>
                    <th>Mobile</th>
                  </tr>";
            
            foreach ($schedule_data as $row) {
                echo "<tr>";
                echo "<td><img src='boards/{$row['picture']}' alt='{$row['type']}' width='50'></td>";
                echo "<td>{$row['category']}</td>";
                echo "<td>{$row['type']}</td>";
                echo "<td>{$row['length']}</td>";
                echo "<td>{$row['rate']}</td>";
                echo "<td>{$row['name']} {$row['surname']}</td>";
                echo "<td>{$row['mobile']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        ?>
        
        <h2>Reports</h2>
        <p>Reports organise and summarise data using functions like COUNT(), AVG(), SUM(), etc.</p>
        
        <form action="" method="POST">
            <div class="form-group">
                <h3>Please select a report:</h3>
                <input type="radio" id="bestCustomer" name="report" value="bestCustomer" checked>
                <label for="bestCustomer">Top customers</label><br>
                <input type="radio" id="bestBoard" name="report" value="bestBoard">
                <label for="bestBoard">Top boards</label><br><br>
                <input type="submit" name="submit_report" value="Generate Report">
            </div>
        </form>
        
        <?php
        if (isset($_POST['submit_report'])) {
            $reportChosen = $_POST['report'];
            
            if ($reportChosen == "bestCustomer") {
                // Simulated database results
                $customer_data = array(
                    array('name' => 'David', 'surname' => 'Xoli', 'count' => 4),
                    array('name' => 'Themba', 'surname' => 'Bool', 'count' => 2),
                    array('name' => 'Anathi', 'surname' => 'Gobeni', 'count' => 1),
                    array('name' => 'James', 'surname' => 'Smith', 'count' => 1)
                );
                
                echo "<h3>Top Customers</h3>";
                echo "<table>";
                echo "<tr>
                        <th>Board Hire Count</th>
                        <th>Name</th>
                      </tr>";
                
                foreach ($customer_data as $row) {
                    echo "<tr>";
                    echo "<td>{$row['count']}</td>";
                    echo "<td>{$row['name']} {$row['surname']}</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                // Prepare data for Google Chart
                echo "<script>
                    google.charts.load('current', {'packages':['corechart']});
                    google.charts.setOnLoadCallback(drawChart);
                    
                    function drawChart() {
                        var data = google.visualization.arrayToDataTable([
                            ['Name', 'Count'],";
                
                foreach ($customer_data as $row) {
                    echo "['{$row['name']} {$row['surname']}', {$row['count']}],";
                }
                
                echo "]);
                        
                        var options = {
                            title: 'Top Customers by Board Hire Count',
                            is3D: true,
                        };
                        
                        var chart = new google.visualization.PieChart(document.getElementById('customer_chart'));
                        chart.draw(data, options);
                    }
                </script>";
                
                echo "<div id='customer_chart' class='chart-container'></div>";
                
            } elseif ($reportChosen == "bestBoard") {
                echo "<p class='error'>Report still needs to be implemented</p>";
            }
        }
        ?>
        
        <h2>Data Visualisation with Google Charts</h2>
        <p>Graphical presentation of data to facilitate understanding.</p>
        
        <h3>Sample Pie Chart</h3>
        <div id="piechart" class="chart-container"></div>
        
        <script type="text/javascript">
            // Load the Visualization API and the corechart package
            google.charts.load('current', {'packages':['corechart']});
            
            // Set a callback to run when the Google Visualization API is loaded
            google.charts.setOnLoadCallback(drawChart);
            
            // Callback that creates and populates a data table,
            // instantiates the pie chart, passes in the data and draws it
            function drawChart() {
                // Create the data table
                var data = google.visualization.arrayToDataTable([
                    ['Task', 'Hours per Day'],
                    ['Work', 11],
                    ['Eat', 2],
                    ['Commute', 2],
                    ['Watch TV', 2],
                    ['Sleep', 7]
                ]);
                
                // Set chart options
                var options = {
                    title: 'My Daily Activities',
                    is3D: true,
                };
                
                // Instantiate and draw our chart, passing in some options
                var chart = new google.visualization.PieChart(document.getElementById('piechart'));
                chart.draw(data, options);
            }
        </script>
    </div>
</body>
</html>