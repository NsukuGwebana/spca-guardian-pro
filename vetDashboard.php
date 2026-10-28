<?php
require_once __DIR__ . '/config.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in, otherwise redirect to login
if (!isset($_SESSION['username'])) {
    header("Location: Newlogin.html");
    exit();
}

// Get user information from session
$firstName = $_SESSION['firstname'] ?? '';
$surname = $_SESSION['surname'] ?? '';
$userRole = $_SESSION['userrole'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinary Dashboard</title>
    
    <!-- Poppins Font from Google -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Material Icons-->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Outlined" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0d3b47;
            --secondary-color: #134f5c;
            --accent-color: #3498db;
            --light-accent: #5dade2;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --text-color: #34495e;
            --text-light: #ffffff;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 70px;
            --header-height: 80px;
            --transition-speed: 0.3s;
            --card-radius: 16px;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--text-color);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Layout */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
            color: var(--text-light);
            height: 100vh;
            position: sticky;
            top: 0;
            overflow-y: auto;
            padding: 25px 0;
            transition: all var(--transition-speed) ease;
            z-index: 100;
            box-shadow: 3px 0 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .sidebar.collapsed .sidebar-brand span:not(.logo),
        .sidebar.collapsed .nav-title,
        .sidebar.collapsed .nav-label,
        .sidebar.collapsed .nav-badge,
        .sidebar.collapsed .user-name,
        .sidebar.collapsed .user-role,
        .sidebar.collapsed .dropdown-arrow {
            display: none;
        }

        .sidebar.collapsed .nav-item {
            padding: 12px 20px;
            justify-content: center;
        }

        .sidebar.collapsed .nav-icon {
            margin-right: 0;
        }

        .sidebar.collapsed .sidebar-footer {
            padding: 15px;
        }

        .sidebar-header {
            padding: 0 25px 25px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .toggle-sidebar {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 22px;
            font-weight: 600;
        }

        .logo {
            width: 40px;
            height: 40px;
            background-color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--primary-color);
        }

        .sidebar-nav {
            flex: 1;
        }

        .nav-section {
            margin-bottom: 30px;
        }

        .nav-title {
            padding: 0 25px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
            opacity: 0.7;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 25px;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.2s;
            position: relative;
            border-left: 4px solid transparent;
            cursor: pointer;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: var(--light-accent);
        }

        .nav-item.active {
            background: rgba(255,255,255,0.15);
            border-left-color: var(--light-accent);
        }

        .nav-icon {
            margin-right: 15px;
            font-size: 22px;
            transition: margin var(--transition-speed);
        }

        .nav-label {
            flex: 1;
        }

        .nav-badge {
            background: var(--danger-color);
            color: white;
            border-radius: 20px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
        }

        .dropdown-arrow {
            transition: transform var(--transition-speed);
        }

        .dropdown-arrow.rotated {
            transform: rotate(90deg);
        }

        .nav-dropdown {
            max-height: 0;
            overflow: hidden;
            transition: max-height var(--transition-speed);
        }

        .nav-dropdown.open {
            max-height: 500px;
        }

        .dropdown-item {
            padding: 12px 25px 12px 60px;
            display: block;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }

        .dropdown-item:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: var(--light-accent);
        }

        .sidebar-footer {
            padding: 25px;
            border-top: 1px solid rgba(255,255,255,0.2);
            margin-top: auto;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: 500;
            margin-bottom: 3px;
        }

        .user-role {
            font-size: 12px;
            opacity: 0.8;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            transition: margin var(--transition-speed);
        }

        .main-content.expanded {
            margin-left: calc(var(--sidebar-collapsed-width) - var(--sidebar-width));
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 30px;
            height: var(--header-height);
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 99;
            backdrop-filter: blur(5px);
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark-color);
            margin-right: auto;
        }
        
        .welcome-message {
            margin-top: 5px;
            color: #666;
            font-size: 16px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .action-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            position: relative;
            color: var(--text-color);
            text-decoration: none;
        }

        .action-btn:hover {
            background: #f5f7fa;
            color: var(--accent-color);
        }

        .notification-badge {
            position: absolute;
            top: -3px;
            right: -3px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
        }

        /* Dashboard Content */
        .dashboard-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* Stats Overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: var(--card-radius);
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(to right, var(--accent-color), var(--secondary-color));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            background: rgba(52, 152, 219, 0.1);
            color: var(--accent-color);
        }

        .stat-trend {
            font-size: 14px;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 20px;
            background: rgba(46, 204, 113, 0.1);
            color: var(--success-color);
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .stat-title {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .stat-link {
            color: var(--accent-color);
            text-decoration: none;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: auto;
            font-weight: 600;
            transition: all 0.2s;
        }

        .stat-link:hover {
            gap: 12px;
            color: var(--secondary-color);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        /* Charts */
        .chart-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: var(--card-radius);
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .chart-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 25px;
        }

        .chart-container {
            height: 300px; 
            background: #f8f9fa; 
            border-radius: 8px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
        }

        /* Appointments */
        .appointments {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: var(--card-radius);
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .appointment-list {
            list-style: none;
        }

        .appointment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 0;
            border-bottom: 1px solid #f1f1f1;
            transition: background 0.2s;
        }

        .appointment-item:last-child {
            border-bottom: none;
        }

        .appointment-item:hover {
            background: rgba(52, 152, 219, 0.03);
        }

        .appointment-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .appointment-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(52, 152, 219, 0.1);
            color: var(--accent-color);
            font-size: 20px;
        }

        .appointment-details h4 {
            font-weight: 500;
            margin-bottom: 6px;
            font-size: 16px;
        }

        .appointment-details p {
            font-size: 13px;
            color: #7f8c8d;
        }

        .appointment-time {
            text-align: right;
        }

        .appointment-time .time {
            font-weight: 500;
            color: var(--dark-color);
            font-size: 15px;
        }

        .appointment-time .date {
            font-size: 13px;
            color: #7f8c8d;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 992px) {
            .app-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .sidebar.collapsed {
                width: 100%;
            }
            
            .sidebar.collapsed .sidebar-brand span:not(.logo),
            .sidebar.collapsed .nav-title,
            .sidebar.collapsed .nav-label,
            .sidebar.collapsed .nav-badge,
            .sidebar.collapsed .user-name,
            .sidebar.collapsed .user-role,
            .sidebar.collapsed .dropdown-arrow {
                display: block;
            }
            
            .sidebar.collapsed .nav-item {
                padding: 12px 25px;
                justify-content: flex-start;
            }
            
            .sidebar.collapsed .nav-icon {
                margin-right: 15px;
            }
            
            .main-content {
                width: 100%;
            }
            
            .main-content.expanded {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .stats-overview {
                grid-template-columns: 1fr;
            }
            
            .header {
                padding: 0 15px;
            }
            
            .dashboard-content {
                padding: 20px;
            }
            
            .appointment-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .appointment-time {
                text-align: left;
                margin-left: 60px;
            }
        }

        .header {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
}

.header-content {
    display: flex;
    flex-direction: column;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 20px;
    justify-self: end; /* This pushes the actions to the right */
}
    </style>
</head>
<body>
    <?php
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
    
    // Get today's date
    $today = date('Y-m-d');
    
    // Query to get today's appointments count
    $todayAppointmentsQuery = "
        SELECT COUNT(*) as count 
        FROM appointments 
        WHERE DATE(app_date) = '$today'
    ";
    $todayAppointmentsResult = $conn->query($todayAppointmentsQuery);
    $todayAppointments = $todayAppointmentsResult ? $todayAppointmentsResult->fetch_assoc()['count'] : 0;
    
    // Query to get pending checkups count (appointments with status 'Scheduled')
    $pendingCheckupsQuery = "
        SELECT COUNT(*) as count 
        FROM appointments 
        WHERE app_status = 'Scheduled'
    ";
    $pendingCheckupsResult = $conn->query($pendingCheckupsQuery);
    $pendingCheckups = $pendingCheckupsResult ? $pendingCheckupsResult->fetch_assoc()['count'] : 0;

    
    // Query to get vaccinations due count (assuming a vaccinations table or field)
    $vaccinationsDueQuery = "
        SELECT COUNT(*) as count 
        FROM vaccinations 
        WHERE due_date <= DATE_ADD('$today', INTERVAL 7 DAY) AND status = 'Pending'
    ";
    $vaccinationsDueResult = $conn->query($vaccinationsDueQuery);
    $vaccinationsDue = $vaccinationsDueResult ? $vaccinationsDueResult->fetch_assoc()['count'] : 0;
    
    // Query to get today's appointments with details
    $todaysAppointmentsQuery = "
        SELECT 
            a.Aname AS animal_name,
            a.species,
            ap.app_date,
            ap.vet_id,
            ap.Reason AS reason
        FROM appointments ap
        JOIN animals a ON ap.animal_id = a.id
        WHERE DATE(ap.app_date) = '$today'
        ORDER BY ap.app_date
        LIMIT 5
    ";
    $todaysAppointmentsResult = $conn->query($todaysAppointmentsQuery);
    $todaysAppointments = [];
    
    if ($todaysAppointmentsResult) {
        if ($todaysAppointmentsResult->num_rows > 0) {
            while ($row = $todaysAppointmentsResult->fetch_assoc()) {
                $todaysAppointments[] = $row;
            }
        }
    }
    
    // Close the connection
    $conn->close();
    ?>
    
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <img src="images/hug.PNG" alt="SPCA Guardian Pro Logo" class="new-logo-image" style="width: 70px; height: 70px;padding: 5px;border: 12px; object-fit:cover;border-radius: 50%;"/>
                    <span>Vet Dashboard</span>
                </div>
                <button class="toggle-sidebar">
                    <span class="material-icons-outlined">chevron_left</span>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-title">Main</div>
                    <a href="#" class="nav-item active">
                        <span class="material-icons-outlined nav-icon">space_dashboard</span>
                        <span class="nav-label">Dashboard</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Management</div>
                    <a href="regA.PHP" class="nav-item">
                        <span class="material-icons-outlined nav-icon">pets</span>
                        <span class="nav-label">View Animals</span>
                    </a>
                    <div class="nav-item" onclick="toggleDropdown('appointment-dropdown')">
                        <span class="material-icons-outlined nav-icon">calendar_today</span>
                        <span class="nav-label">Appointments</span>
                        <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
                    </div>
                    <div id="appointment-dropdown" class="nav-dropdown">
                        <a href="medappointments.php" class="dropdown-item">Schedule Appointment</a>
                        <a href="fetchapp.php" class="dropdown-item">View Appointments</a>
                    </div>
                    
                    <div class="nav-item" onclick="toggleDropdown('treatments-dropdown')">
                        <span class="material-icons-outlined nav-icon">medical_services</span>
                        <span class="nav-label">Treatments</span>
                        <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
                    </div>
                    <div id="treatments-dropdown" class="nav-dropdown">
                        <a href="medicaltreatments.php" class="dropdown-item">Log Treatment</a>
                        <a href="fetchmed.php" class="dropdown-item">View Animal Treatment</a>
                    </div>
                </div>
                
                <div class="nav-section">
                    <div class="nav-title">Reports</div>
                    <div class="nav-item" onclick="toggleDropdown('reports-dropdown')">
                        <span class="material-icons-outlined nav-icon">summarize</span>
                        <span class="nav-label">Reports</span>
                        <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
                    </div>
                    <div id="reports-dropdown" class="nav-dropdown">
                        <a href="medcare_reports.php" class="dropdown-item">Medical Reports</a>
                   
                    </div>
                    
                   
                </div>
            <div class="nav-section">
  <div class="nav-title">Help & Guides</div>
  
  <div class="nav-item" onclick="toggleDropdown('help-dropdown')">
    <span class="material-icons-outlined nav-icon">help_outline</span>
    <span class="nav-label">Help</span>
    <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
  </div>
  
  <div id="help-dropdown" class="nav-dropdown">
    <a href="vetguide.pdf" class="dropdown-item">Vet Guide</a>
   
   
  </div>
</div></nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <span class="material-icons-outlined">account_circle</span>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($firstName . ' ' . $surname); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($userRole); ?></div>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div>
                    <h1 class="page-title">SPCA GUARDIAN PRO</h1>
                    <div class="welcome-message">Welcome back, <?php echo htmlspecialchars($firstName); ?>!</div>
                </div>
                <div class="header-actions">
                   
                    <a href="logoutForm.html" class="action-btn" title="Logout">
                        <span class="material-icons-outlined">logout</span>
                    </a>
                </div>
            </header>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Stats Overview -->
                <div class="stats-overview">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <span class="material-icons-outlined">calendar_today</span>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $todayAppointments; ?></div>
                        <div class="stat-title">Today's Appointments</div>
                        <a href="fetchapp.php" class="stat-link">
                            <span>View all appointments</span>
                            <span class="material-icons-outlined">arrow_forward</span>
                        </a>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;">
                                <span class="material-icons-outlined">medical_services</span>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $pendingCheckups; ?></div>
                        <div class="stat-title">Pending Checkups</div>
                        <a href="fetchapp.php?status=Scheduled" class="stat-link">
                            <span>Review pending cases</span>
                            <span class="material-icons-outlined">arrow_forward</span>
                        </a>
                    </div>
                    
   
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #27ae60;">
                                <span class="material-icons-outlined">vaccines</span>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $vaccinationsDue; ?></div>
                        <div class="stat-title">Vaccinations Due</div>
                        
                    </div>
                </div>
                
                <!-- Dashboard Grid -->
                <div class="dashboard-grid">
                    <!-- Left Column -->
                    <div>
                      
                        </div>
                        
                        
                    </div>
                    
                    <!-- Right Column -->
                    <div>
                        <!-- Appointments -->
                        <div class="appointments">
                            <h3 class="chart-title">Today's Appointments</h3>
                            <ul class="appointment-list">
                                <?php if (!empty($todaysAppointments)): ?>
                                    <?php foreach ($todaysAppointments as $appointment): ?>
                                        <li class="appointment-item">
                                            <div class="appointment-info">
                                                <div class="appointment-icon">
                                                    <span class="material-icons-outlined">pets</span>
                                                </div>
                                                <div class="appointment-details">
                                                    <h4><?php echo htmlspecialchars($appointment['animal_name']); ?> (<?php echo htmlspecialchars($appointment['species']); ?>)</h4>
                                                    <p><?php echo htmlspecialchars($appointment['reason']); ?></p>
                                                </div>
                                            </div>
                                            <div class="appointment-time">
                                                <div class="time"><?php echo date('g:i A', strtotime($appointment['app_date'])); ?></div>
                                                <div class="date"><?php echo htmlspecialchars($appointment['vet_id']); ?></div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li class="appointment-item">
                                        <div class="appointment-info">
                                            <div class="appointment-icon">
                                                <span class="material-icons-outlined">event_busy</span>
                                            </div>
                                            <div class="appointment-details">
                                                <h4>No Appointments Today</h4>
                                                <p>No appointments scheduled for today</p>
                                            </div>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Toggle sidebar collapse
        const toggleSidebar = document.querySelector('.toggle-sidebar');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            
            // Change the toggle icon direction
            const icon = toggleSidebar.querySelector('span');
            if (sidebar.classList.contains('collapsed')) {
                icon.textContent = 'chevron_right';
            } else {
                icon.textContent = 'chevron_left';
            }
        });

        // Toggle dropdowns in sidebar
        function toggleDropdown(id) {
            const dropdown = document.getElementById(id);
            const arrow = dropdown.previousElementSibling.querySelector('.dropdown-arrow');
            
            dropdown.classList.toggle('open');
            arrow.classList.toggle('rotated');
        }

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth < 992 && sidebar.classList.contains('collapsed') &&
                !sidebar.contains(e.target) && !document.querySelector('.toggle-sidebar').contains(e.target)) {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
                const icon = toggleSidebar.querySelector('span');
                icon.textContent = 'chevron_left';
            }
        });
    </script>
</body>
</html>