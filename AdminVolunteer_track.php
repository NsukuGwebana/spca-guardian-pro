<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPCA Guardian Pro - Volunteer Tracking</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            color: #fff;
        }

        .overlay {
            background: rgba(19, 79, 92, 0.85);
            min-height: 100vh;
            padding: 30px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
            background: transparent;
            align-items: flex-start;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 10px;
        }
        
        .logo-icon {
            font-size: 2.5rem;
            color: #76c7d0;
        }
        
        h1 {
            font-size: 2.5rem;
            color: #ffffffff;
            margin-bottom: 10px;
        }
        
        .subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .tracking-section {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 900px) {
            .tracking-section {
                grid-template-columns: 1fr;
            }
        }
        
        .search-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .search-box h2 {
            margin-bottom: 20px;
            color: #76c7d0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-icon {
            font-size: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: #76c7d0;
            color: #0c3540;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        button:hover {
            background: #5bb0bb;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .results-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .results-header h2 {
            color: #76c7d0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .results-icon {
            font-size: 1.5rem;
        }
        
        .volunteer-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: rgba(255, 255, 255, 0.15);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }
        
        .summary-card h3 {
            font-size: 2.5rem;
            margin-bottom: 5px;
            color: fcfcfcff;
        }
        
        .summary-card p {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .activities-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .activities-table th,
        .activities-table td {
            padding: 12px 15px;
            text-align: left;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .activities-table th {
            background: rgba(118, 199, 208, 0.3);
            font-weight: 600;
        }
        
        .activities-table tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .activities-table tr:hover {
            background: rgba(118, 199, 208, 0.1);
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }
        
        .footer-link {
            display: inline-block;
            padding: 12px 25px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .footer-link:hover {
            background: rgba(118, 199, 208, 0.3);
            transform: translateY(-2px);
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .message-error {
            background: rgba(244, 67, 54, 0.2);
            color: #f44336;
        }
        
        .message-success {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }
        
        .volunteer-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .volunteer-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #76c7d0 0%, #134f5c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="overlay">
        <?php
require_once __DIR__ . '/config.php';
        // Enable error reporting for debugging
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        // Database connection details
        $server = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $db = DB_NAME;

        // Create connection
        $conn = new mysqli($server, $user, $pass, $db);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Initialize variables
        $email = "";
        $id_number = "";
        $volunteer_data = null;
        $activity_data = null;
        $summary_data = [
            'unique_activities_list' => 'None',
            'days_volunteered' => '0',
            'animals_helped' => '0',
            'total_activities' => '0'
        ];
        $message = "";
        $message_type = "";
        $total_hours_formatted = "0 hours 00 minutes";

        // Process form submission
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = trim($_POST['email']);
            $id_number = trim($_POST['id_number']);

            if (!empty($email) || !empty($id_number)) {

                $stmt_volunteer = $conn->prepare("
                    SELECT volunteer_Fname, volunteer_Lname, email
                    FROM community_engagement 
                    WHERE email = ? OR volunteer_id = ?
                    LIMIT 1
                ");
                
                if ($stmt_volunteer === false) {
                    $message = "Error preparing main volunteer query: " . $conn->error;
                    $message_type = "error";
                } else {
                    $stmt_volunteer->bind_param("ss", $email, $id_number);
                    $stmt_volunteer->execute();
                    $result_volunteer = $stmt_volunteer->get_result();
                    
                    if ($result_volunteer->num_rows > 0) {
                        $volunteer_data = $result_volunteer->fetch_assoc();
                        $message = "Volunteer records loaded successfully.";
                        $message_type = "success";
                        
                        $email_to_query = $volunteer_data['email'];

                        // Corrected SQL query for activity data
                        $activity_stmt = $conn->prepare("
                            SELECT date, activities_name, activity_description, 
                                start_time, end_time, lapse_time, supervisor_name, supervisor_surname
                            FROM community_engagement 
                            WHERE email = ?
                            ORDER BY date DESC, start_time DESC
                        ");
                        
                        if ($activity_stmt === false) {
                            $message = "Error preparing activity query: " . $conn->error;
                            $message_type = "error";
                        } else {
                            $activity_stmt->bind_param("s", $email_to_query);
                            $activity_stmt->execute();
                            $activity_result = $activity_stmt->get_result();
                            
                            if ($activity_result->num_rows > 0) {
                                $activity_data = $activity_result->fetch_all(MYSQLI_ASSOC);
                                
                                $total_minutes = 0;
                                foreach ($activity_data as $activity) {
                                    // Extract hours and minutes from the "X hours Y minutes" string
                                    if (preg_match('/(\d+)\s+hours\s+(\d+)\s+minutes/', $activity['lapse_time'], $matches)) {
                                        $hours = (int)$matches[1];
                                        $minutes = (int)$matches[2];
                                        $total_minutes += ($hours * 60) + $minutes;
                                    }
                                }
                                
                                // Calculate total hours and minutes from total minutes
                                $hours = floor($total_minutes / 60);
                                $minutes = $total_minutes % 60;
                                $total_hours_formatted = sprintf("%d hours %02d minutes", $hours, $minutes);
                            }
                            $activity_stmt->close();
                        }

                        // Corrected SQL query for summary data
                        $summary_stmt_ce = $conn->prepare("
                            SELECT 
                                GROUP_CONCAT(DISTINCT activities_name SEPARATOR ', ') as unique_activities_list,
                                COUNT(DISTINCT DATE(date)) as days_volunteered,
                                COUNT(*) as total_activities
                            FROM community_engagement
                            WHERE email = ?
                        ");

                        if ($summary_stmt_ce === false) {
                            $message = "Error preparing community engagement summary query: " . $conn->error;
                            $message_type = "error";
                        } else {
                            $summary_stmt_ce->bind_param("s", $email_to_query);
                            $summary_stmt_ce->execute();
                            $summary_result_ce = $summary_stmt_ce->get_result();
                            if ($summary_result_ce->num_rows > 0) {
                                $summary_data_ce = $summary_result_ce->fetch_assoc();
                                $summary_data['unique_activities_list'] = $summary_data_ce['unique_activities_list'];
                                $summary_data['days_volunteered'] = $summary_data_ce['days_volunteered'];
                                $summary_data['total_activities'] = $summary_data_ce['total_activities'];
                            }
                            $summary_stmt_ce->close();
                        }
                        
                    } else {
                        $message = "No volunteer records found for that email address or ID.";
                        $message_type = "error";
                    }
                    
                    $stmt_volunteer->close();
                }
            } else {
                $message = "Please enter your email address or Volunteer ID.";
                $message_type = "error";
            }
        }
        $conn->close();
        ?>
        <div class="container">
            <header>
                <div class="logo">
                    <span class="logo-icon"></span>
                    <h1>SPCA Guardian Pro</h1>
                </div>
                <p class="subtitle">Track  volunteer activities and contributions</p>
            </header>
            
            <div class="tracking-section">
                <div class="search-box">
                    <h2><span class="search-icon"></span> Find  Records</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="Enter email" value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                        <div class="form-group">
                            <label for="id-number">Volunteer ID</label>
                            <input type="text" id="id-number" name="id_number" placeholder="Enter volunteer ID" value="<?php echo htmlspecialchars($id_number); ?>">
                        </div>
                        <button type="submit">Track Activities</button>
                    </form>
                </div>
                
                <div class="results-box">
                    <div class="results-header">
                        <h2><span class="results-icon">📊</span> Volunteer Summary</h2>
                    </div>
                    
                    <?php if (!empty($message)): ?>
                        <div class="message message-<?php echo $message_type; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($volunteer_data): ?>
                        <div class="volunteer-info">
                            <div class="volunteer-avatar">
                                <?php echo strtoupper(substr($volunteer_data['volunteer_Fname'], 0, 1) . substr($volunteer_data['volunteer_Lname'], 0, 1)); ?>
                            </div>
                            <div>
                                <h3><?php echo htmlspecialchars($volunteer_data['volunteer_Fname'] . ' ' . $volunteer_data['volunteer_Lname']); ?></h3>
                                <p><?php echo htmlspecialchars($volunteer_data['email']); ?></p>
                            </div>
                        </div>
                        
                        <div class="volunteer-summary">
                            <div class="summary-card">
                                <h3><?php echo htmlspecialchars($summary_data['total_activities']); ?></h3>
                                <p>Total Activities</p>
                            </div>
                            <div class="summary-card">
                                <h3><?php echo htmlspecialchars($total_hours_formatted); ?></h3>
                                <p>Total Hours</p>
                            </div>
                            <div class="summary-card">
                                <h3><?php echo htmlspecialchars($summary_data['animals_helped']); ?></h3>
                                <p>Animals Helped</p>
                            </div>
                            <div class="summary-card">
                                <h3><?php echo htmlspecialchars($summary_data['unique_activities_list']); ?></h3>
                                <p>Activity Types</p>
                            </div>
                        </div>
                        
                        <h3 style="margin-bottom: 15px; color: #76c7d0;">Recent Activities</h3>
                        
                        <?php if ($activity_data): ?>
                            <table class="activities-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Activity Type</th>
                                        <th>Description</th>
                                        <th>Duration</th>
                                        <th>Supervisor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activity_data as $activity): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($activity['date']); ?></td>
                                            <td><?php echo htmlspecialchars($activity['activities_name']); ?></td>
                                            <td><?php echo htmlspecialchars($activity['activity_description']); ?></td>
                                            <td><?php echo htmlspecialchars($activity['lapse_time']); ?></td>
                                            <td>
                                                <?php 
                                                if (!empty($activity['supervisor_name']) || !empty($activity['supervisor_surname'])) {
                                                    echo htmlspecialchars(($activity['supervisor_name'] ?? '') . ' ' . ($activity['supervisor_surname'] ?? ''));
                                                } else {
                                                    echo 'Not specified';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="no-data">
                                <p>No activities found for this volunteer.</p>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div id="volunteer-results">
                            <p>Enter  email address or Volunteer ID to view  volunteer records.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="footer-links">
<button class="back-btn" onclick="history.back()">Back</button>
                
                <a href="Community.html" class="footer-link">Submit New Activity</a>
            </div>
        </div>
    </div>
</body>
</html>
