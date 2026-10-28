<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection for volunteer dashboard
$server = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$db     = DB_NAME;

$conn = new mysqli($server, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// Initialize statistics
$totalVolunteers = 0;
$totalActivities = 0;
$upcomingEventsCount = 0;
$totalHours = 0;
$recentActivities = [];

// Count total volunteers
$sqlVolunteers = "SELECT COUNT(DISTINCT volunteer_id) as total FROM community_engagement";
$resultVolunteers = $conn->query($sqlVolunteers);
if ($resultVolunteers) {
    $row = $resultVolunteers->fetch_assoc();
    $totalVolunteers = $row['total'] ?? 0;
} else {
    error_log("Error in volunteers query: " . $conn->error);
}

// Count total activities
$sqlActivities = "SELECT COUNT(*) as total FROM community_engagement";
$resultActivities = $conn->query($sqlActivities);
if ($resultActivities) {
    $row = $resultActivities->fetch_assoc();
    $totalActivities = $row['total'] ?? 0;
} else {
    error_log("Error in activities query: " . $conn->error);
}

// Count upcoming events (next 7 days)
$sqlEvents = "SELECT COUNT(*) as total 
              FROM events 
              WHERE event_date >= CURDATE() 
              AND event_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
$resultEvents = $conn->query($sqlEvents);
if ($resultEvents) {
    $row = $resultEvents->fetch_assoc();
    $upcomingEventsCount = $row['total'] ?? 0;
} else {
    error_log("Error in events query: " . $conn->error);
}

// Calculate total volunteer hours
$sqlHours = "SELECT 
                SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))) AS total_seconds
             FROM community_engagement
             WHERE start_time IS NOT NULL AND end_time IS NOT NULL";

$resultHours = $conn->query($sqlHours);
if ($resultHours) {
    $row = $resultHours->fetch_assoc();
    $totalSeconds = $row['total_seconds'] ?? 0;
    $totalHours = round($totalSeconds / 3600, 2); // Convert to hours
} else {
    error_log("Error in hours query: " . $conn->error);
    $totalHours = 0;
}



// Get recent activities
$sqlRecent = "
   
    (SELECT 'Event' as type, CONCAT('Upcoming event: ', event_name) as description, event_date as date 
     FROM events 
     WHERE event_date >= CURDATE() 
     ORDER BY event_date ASC LIMIT 3)
    ORDER BY date DESC LIMIT 5
";

$resultRecent = $conn->query($sqlRecent);
if ($resultRecent) {
    $recentActivities = $resultRecent->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Error in recent activities query: " . $conn->error);
    $recentActivities = [];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Volunteer Dashboard - Animal Adoption System</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
      background: rgba(255, 255, 255, 0.8);
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
      background: rgba(255, 255, 255, 0.6);
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
      background: rgba(255, 255, 255, 0.8);
      border-radius: var(--card-radius);
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      display: flex;
      flex-direction: column;
      transition: all 0.3s ease;
      backdrop-filter: blur(5px);
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
      background: rgba(255, 255, 255, 0.95);
    }

    .stat-header {
      display: flex;
      justify-content: between;
      align-items: flex-start;
      margin-bottom: 20px;
      position: relative;
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
      position: absolute;
      top: 0;
      right: 0;
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

    /* Activity Feed */
    .activity-feed {
      background: rgba(255, 255, 255, 0.8);
      border-radius: var(--card-radius);
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      margin-bottom: 30px;
      backdrop-filter: blur(5px);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid rgba(0,0,0,0.1);
    }

    .section-title {
      font-size: 20px;
      font-weight: 700;
      color: var(--dark-color);
    }

    .view-all {
      color: var(--accent-color);
      text-decoration: none;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 600;
      transition: all 0.2s;
    }

    .view-all:hover {
      gap: 12px;
      color: var(--secondary-color);
    }

    .activity-list {
      list-style: none;
    }

    .activity-item {
      display: flex;
      gap: 15px;
      padding: 18px 0;
      border-bottom: 1px solid rgba(0,0,0,0.05);
      transition: background 0.2s;
    }

    .activity-item:last-child {
      border-bottom: none;
    }

    .activity-item:hover {
      background: rgba(52, 152, 219, 0.03);
    }

    .activity-icon {
      width: 45px;
      height: 45px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-size: 20px;
    }

    .activity-content {
      flex-grow: 1;
    }

    .activity-description {
      margin-bottom: 6px;
      font-weight: 500;
    }

    .activity-date {
      font-size: 12px;
      color: #7f8c8d;
    }

    /* Quick Actions */
    .quick-actions {
      background: rgba(255, 255, 255, 0.8);
      border-radius: var(--card-radius);
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      margin-bottom: 30px;
      backdrop-filter: blur(5px);
    }

    .action-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
    }

    .action-button {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 20px 15px;
      background: rgba(255, 255, 255, 0.7);
      border-radius: 12px;
      text-decoration: none;
      color: var(--text-color);
      transition: all 0.3s;
      box-shadow: 0 3px 10px rgba(0,0,0,0.05);
      text-align: center;
    }

    .action-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 15px rgba(0,0,0,0.1);
      color: var(--accent-color);
      background: rgba(255, 255, 255, 0.9);
    }

    .action-button-icon {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(52, 152, 219, 0.1);
      color: var(--accent-color);
      font-size: 24px;
      margin-bottom: 12px;
    }

    .action-button-text {
      font-weight: 600;
      font-size: 14px;
    }

    /* System Overview */
    .system-overview {
      background: rgba(255, 255, 255, 0.8);
      border-radius: var(--card-radius);
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      margin-bottom: 30px;
      backdrop-filter: blur(5px);
    }

    .overview-header {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .overview-icon {
      width: 60px;
      height: 60px;
      border-radius: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      background: rgba(52, 152, 219, 0.1);
      color: var(--accent-color);
      margin-right: 20px;
    }

    .overview-info {
      flex: 1;
    }

    .overview-title {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark-color);
    }

    .overview-subtitle {
      font-size: 14px;
      color: #7f8c8d;
    }

    .overview-details {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
    }

    .overview-detail {
      display: flex;
      flex-direction: column;
    }

    .detail-label {
      font-size: 12px;
      color: #7f8c8d;
      margin-bottom: 5px;
    }

    .detail-value {
      font-size: 16px;
      font-weight: 500;
    }

    .overview-actions {
      margin-top: 20px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .overview-btn {
      padding: 10px 15px;
      border-radius: 8px;
      border: none;
      background: var(--accent-color);
      color: white;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .overview-btn:hover {
      background: var(--secondary-color);
    }

    .overview-btn.secondary {
      background: rgba(236, 240, 241, 0.8);
      color: var(--text-color);
    }

    .overview-btn.secondary:hover {
      background: rgba(223, 230, 233, 0.9);
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
      
      .action-grid {
        grid-template-columns: 1fr;
      }
      
      .overview-details {
        grid-template-columns: 1fr;
      }
      
      .header {
        padding: 0 15px;
      }
      
      .dashboard-content {
        padding: 20px;
      }
      
      .overview-header {
        flex-direction: column;
        text-align: center;
      }
      
      .overview-icon {
        margin-right: 0;
        margin-bottom: 15px;
      }
      
      .overview-actions {
        flex-direction: column;
      }
    }
  </style>
</head>
<body>
  <div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-brand">
          <img src="images/hug.PNG" alt="SPCA Guardian Pro Logo" class="new-logo-image" style="width: 70px; height: 70px;padding: 5px;border: 12px; object-fit:cover;border-radius: 50%;"/>
    <span>Volunteer Dashboard</span> <br><br>
        </div>
        <button class="toggle-sidebar">
          <span class="material-icons-outlined">chevron_left</span>
        </button>
      </div>
      
      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-title">Main</div>
          <a href="#" class="nav-item active">
            <span class="material-icons-outlined nav-icon">dashboard</span>
            <span class="nav-label">Dashboard</span>
          </a>
        </div>
        <div class="nav-section">
          <div class="nav-title">Volunteer</div>
          <div class="nav-item" onclick="toggleDropdown('activities-dropdown')">
            <span class="material-icons-outlined nav-icon">assignment</span>
            <span class="nav-label">Activities</span>
            <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
          </div>
          <div id="activities-dropdown" class="nav-dropdown">
            
            <a href="community.html" class="dropdown-item">Sign Up</a>
             <a href="foster.php" class="dropdown-item">Apply to Foster </a>
            <a href="track_foster.php" class="dropdown-item">Track Foster Application</a>
             <a href="track_.php" class="dropdown-item">Track Volunteer Application</a>
            
          </div>
          
         
      
      <div class="sidebar-footer">
        <div class="user-info">
          <div class="user-avatar">
            <span class="material-icons-outlined">groups</span>
          </div>
          <div class="user-details">
            <div class="user-name">Volunteer System</div>
            <div class="user-role">Overview Dashboard</div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Header -->
      <header class="header">
        <h1 class="page-title">Volunteer Dashboard - System Overview</h1>
        <div class="header-actions">
          
          
          <div class="action-btn">
            
          </div>
        </div>
      </header>
      
      <!-- Dashboard Content -->
      <div class="dashboard-content">
        <!-- Stats Overview -->
        <div class="stats-overview">
          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon">
                <span class="material-icons-outlined">groups</span>
              </div>
            </div>
            <div class="stat-value"><?php echo $totalVolunteers; ?></div>
            <div class="stat-title">Total Volunteers</div>
           
          </div>
          
          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #27ae60;">
                <span class="material-icons-outlined">checklist</span>
              </div>
            </div>
            <div class="stat-value"><?php echo $totalActivities; ?></div>
            <div class="stat-title">Total Activities</div>
            
          </div>
          
          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                <span class="material-icons-outlined">event</span>
              </div>
            </div>
            <div class="stat-value"><?php echo $upcomingEventsCount; ?></div>
            <div class="stat-title">Upcoming Events</div>
            
          </div>
          
          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                <span class="material-icons-outlined">schedule</span>
              </div>
            </div>
            <div class="stat-value"><?php echo $totalHours; ?></div>
            <div class="stat-title">Total Volunteer Hours</div>
            
              
          </div>
        </div>
        
        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
          <!-- Left Column -->
          <div>
            <!-- Activity Feed -->
            <div class="activity-feed">
              <div class="section-header">
                <h2 class="section-title">Recent Activities</h2>
             
                 
              </div>
              
              <ul class="activity-list">
                <?php if (count($recentActivities) > 0): ?>
                  <?php foreach ($recentActivities as $activity): ?>
                    <li class="activity-item">
                      <div class="activity-icon" style="
                        <?php if ($activity['type'] == 'Activity'): ?>
                          background: rgba(52, 152, 219, 0.1); color: #2980b9;
                        <?php elseif ($activity['type'] == 'Event'): ?>
                          background: rgba(243, 156, 18, 0.1); color: #f39c12;
                        <?php else: ?>
                          background: rgba(46, 204, 113, 0.1); color: #27ae60;
                        <?php endif; ?>
                      ">
                        <?php if ($activity['type'] == 'Activity'): ?>
                          <span class="material-icons-outlined">assignment</span>
                        <?php elseif ($activity['type'] == 'Event'): ?>
                          <span class="material-icons-outlined">event</span>
                        <?php else: ?>
                          <span class="material-icons-outlined">schedule</span>
                        <?php endif; ?>
                      </div>
                      <div class="activity-content">
                        <div class="activity-description"><?php echo $activity['description']; ?></div>
                        <div class="activity-date"><?php echo date('M j, Y g:i A', strtotime($activity['date'])); ?></div>
                      </div>
                    </li>
                  <?php endforeach; ?>
                <?php else: ?>
                  <li class="activity-item">
                    <div class="activity-content">
                      <div class="activity-description">No recent activities found</div>
                    </div>
                  </li>
                <?php endif; ?>
              </ul>
            </div>
            
           
          <!-- Right Column -->
          <div>
            <!-- System Overview -->
           
                </div>
                
                
              
              

  <script>
    // Toggle sidebar collapse
    const toggleSidebar = document.querySelector('.toggle-sidebar');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (toggleSidebar) {
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
    }

    // Toggle dropdowns in sidebar
    function toggleDropdown(id) {
      const dropdown = document.getElementById(id);
      if (dropdown) {
        const arrow = dropdown.previousElementSibling.querySelector('.dropdown-arrow');
        
        dropdown.classList.toggle('open');
        if (arrow) {
          arrow.classList.toggle('rotated');
        }
      }
    }

    // Notification bell functionality
    const notificationBtn = document.querySelector('.action-btn:not([href])');
    if (notificationBtn) {
      notificationBtn.addEventListener('click', () => {
        alert('Notifications would open here');
      });
    }
  </script>
</body>
</html>  