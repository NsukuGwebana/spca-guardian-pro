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

// Initialize volunteer-specific data
$assignedTasksCount = 0;
$completedTasksCount = 0;
$upcomingEventsCount = 0;
$volunteerHours = 0;
$recentActivities = [];

// Get volunteer ID from session (this would typically come from your login system)
// For demonstration, I'm using a placeholder
$volunteer_id = 1; // This should be set based on the logged-in volunteer

// Get volunteer email to link with community_engagement table
$volunteerEmail = '';
$sqlEmail = "SELECT email FROM volunteers WHERE id = $volunteer_id";
$resultEmail = $conn->query($sqlEmail);
if ($resultEmail && $resultEmail->num_rows > 0) {
    $rowEmail = $resultEmail->fetch_assoc();
    $volunteerEmail = $rowEmail['email'];
}

// Count assigned tasks with error handling
$sqlTasks = "SELECT COUNT(*) as total FROM volunteer_tasks WHERE volunteer_id = $volunteer_id AND status = 'Assigned'";
$resultTasks = $conn->query($sqlTasks);
if ($resultTasks) {
    $rowTasks = $resultTasks->fetch_assoc();
    $assignedTasksCount = $rowTasks['total'] ?? 0;
} else {
    error_log("Error in tasks query: " . $conn->error);
}

// Count completed tasks with error handling - FIXED to count activities from community_engagement
if (!empty($volunteerEmail)) {
    $sqlCompleted = "SELECT COUNT(DISTINCT activities_name) as total FROM community_engagement WHERE email = '$volunteerEmail' AND status = 'Completed'";
    $resultCompleted = $conn->query($sqlCompleted);
    if ($resultCompleted) {
        $rowCompleted = $resultCompleted->fetch_assoc();
        $completedTasksCount = $rowCompleted['total'] ?? 0;
    } else {
        error_log("Error in completed tasks query: " . $conn->error);
    }
}

// Count upcoming events (next 7 days) with error handling
$sqlEvents = "SELECT COUNT(*) as total FROM volunteer_events WHERE event_date >= CURDATE() AND event_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
$resultEvents = $conn->query($sqlEvents);
if ($resultEvents) {
    $rowEvents = $resultEvents->fetch_assoc();
    $upcomingEventsCount = $rowEvents['total'] ?? 0;
} else {
    error_log("Error in events query: " . $conn->error);
}

// Calculate total volunteer hours with error handling - FIXED to use time elapsed from community_engagement
if (!empty($volunteerEmail)) {
    $sqlHours = "SELECT SUM(TIME_TO_SEC(lapse_time)) as total_seconds FROM community_engagement WHERE email = '$volunteerEmail'";
    $resultHours = $conn->query($sqlHours);
    if ($resultHours) {
        $rowHours = $resultHours->fetch_assoc();
        $totalSeconds = $rowHours['total_seconds'] ?? 0;
        // Convert seconds to hours
        $volunteerHours = round($totalSeconds / 3600, 1);
    } else {
        error_log("Error in hours query: " . $conn->error);
    }
}

// Get volunteer profile information
$sqlProfile = "SELECT first_name, last_name, email, phone, join_date, profile_picture FROM volunteers WHERE id = $volunteer_id";
$resultProfile = $conn->query($sqlProfile);
if ($resultProfile) {
    $volunteerProfile = $resultProfile->fetch_assoc();
} else {
    error_log("Error in profile query: " . $conn->error);
    $volunteerProfile = ['first_name' => 'Volunteer', 'last_name' => 'User', 'email' => 'N/A', 'phone' => 'N/A', 'join_date' => date('Y-m-d')];
}

// Get recent activities for this volunteer with error handling
if (!empty($volunteerEmail)) {
    $sqlActivities = "
        (SELECT 'Task' as type, CONCAT('Task assigned: ', task_description) as description, assigned_date as date 
         FROM volunteer_tasks WHERE volunteer_id = $volunteer_id ORDER BY assigned_date DESC LIMIT 3)
        UNION
        (SELECT 'Event' as type, CONCAT('Upcoming event: ', event_name) as description, event_date as date 
         FROM volunteer_events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3)
        UNION
        (SELECT 'Hours' as type, CONCAT('Hours logged: ', activities_name, ' (', lapse_time, ')') as description, date as date 
         FROM community_engagement WHERE email = '$volunteerEmail' ORDER BY date DESC LIMIT 3)
        ORDER BY date DESC LIMIT 5
    ";
} else {
    $sqlActivities = "
        (SELECT 'Task' as type, CONCAT('Task assigned: ', task_description) as description, assigned_date as date 
         FROM volunteer_tasks WHERE volunteer_id = $volunteer_id ORDER BY assigned_date DESC LIMIT 3)
        UNION
        (SELECT 'Event' as type, CONCAT('Upcoming event: ', event_name) as description, event_date as date 
         FROM volunteer_events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3)
        ORDER BY date DESC LIMIT 5
    ";
}

$resultActivities = $conn->query($sqlActivities);
if ($resultActivities) {
    $recentActivities = $resultActivities->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Error in volunteer activities query: " . $conn->error);
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

    /* Profile Card */
    .profile-card {
      background: rgba(255, 255, 255, 0.8);
      border-radius: var(--card-radius);
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      margin-bottom: 30px;
      backdrop-filter: blur(5px);
    }

    .profile-header {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .profile-avatar {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 32px;
      margin-right: 20px;
    }

    .profile-info {
      flex: 1;
    }

    .profile-name {
      font-size: 24px;
      font-weight: 600;
      margin-bottom: 5px;
      color: var(--dark-color);
    }

    .profile-join-date {
      font-size: 14px;
      color: #7f8c8d;
    }

    .profile-details {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 15px;
    }

    .profile-detail {
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

    .profile-actions {
      margin-top: 20px;
      display: flex;
      gap: 10px;
    }

    .profile-btn {
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

    .profile-btn:hover {
      background: var(--secondary-color);
    }

    .profile-btn.secondary {
      background: rgba(236, 240, 241, 0.8);
      color: var(--text-color);
    }

    .profile-btn.secondary:hover {
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
      
      .profile-details {
        grid-template-columns: 1fr;
      }
      
      .header {
        padding: 0 15px;
      }
      
      .dashboard-content {
        padding: 20px;
      }
      
      .profile-header {
        flex-direction: column;
        text-align: center;
      }
      
      .profile-avatar {
        margin-right: 0;
        margin-bottom: 15px;
      }
      
      .profile-actions {
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
          <span>Volunteer Dashboard</span>
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
          <div class="nav-item" onclick="toggleDropdown('tasks-dropdown')">
            <span class="material-icons-outlined nav-icon">assignment</span>
            <span class="nav-label">My Activities</span>
            <?php if ($assignedTasksCount > 0): ?>
              <span class="nav-badge"><?php echo $assignedTasksCount; ?></span>
            <?php endif; ?>
            <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
          </div>
          <div id="tasks-dropdown" class="nav-dropdown">
            <a href="track_volunteer.php" class="dropdown-item">View My Activities</a>
             <a href="track_foster.php" class="dropdown-item"> View Foster</a>
          </div>
          
          <div class="nav-item" onclick="toggleDropdown('events-dropdown')">
            <span class="material-icons-outlined nav-icon">event</span>
            <span class="nav-label">Events</span>
            <?php if ($upcomingEventsCount > 0): ?>
              <span class="nav-badge"><?php echo $upcomingEventsCount; ?></span>
            <?php endif; ?>
            <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
          </div>
          <div id="events-dropdown" class="nav-dropdown">
            <a href="volunteer_events.php" class="dropdown-item">Upcoming Events</a>
            <a href="event_signup.php" class="dropdown-item">Sign Up for Events</a>
          </div>
          
          
        
      </nav>
      
      <div class="sidebar-footer">
        <div class="user-info">
          <div class="user-avatar">
            <span class="material-icons-outlined">account_circle</span>
          </div>
          <div class="user-details">
            <div class="user-name"><?php echo $volunteerProfile['first_name'] . ' ' . $volunteerProfile['last_name']; ?></div>
            <div class="user-role">Animal Care Volunteer</div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <!-- Header -->
      <header class="header">
        <h1 class="page-title">Volunteer Dashboard</h1>
        <div class="header-actions">
          <a href="volunteer_messages.php" class="action-btn">
            <span class="material-icons-outlined">mail</span>
          </a>
          
          <div class="action-btn">
            <span class="material-icons-outlined">notifications</span>
            <?php if ($assignedTasksCount > 0): ?>
              <span class="notification-badge"><?php echo $assignedTasksCount; ?></span>
            <?php endif; ?>
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
                <span class="material-icons-outlined">assignment</span>
              </div>
            </div>
            <div class="stat-value"><?php echo $assignedTasksCount; ?></div>
            <div class="stat-title">Assigned Tasks</div>
            <a href="track_volunteer.php" class="stat-link">
              <span>View my tasks</span>
              <span class="material-icons-outlined">arrow_forward</span>
            </a>
          </div>
          
          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon" style="background: rgba(46, 204, 113, 0.1); color: #27ae60;">
                <span class="material-icons-outlined">check_circle</span>
              </div>
            </div>
            <div class="stat-value"><?php echo $completedTasksCount; ?></div>
            <div class="stat-title">Completed Activities</div>
            <a href="track_volunteer.php" class="stat-link">
              <span>View completed activities</span>
              <span class="material-icons-outlined">arrow_forward</span>
            </a>
          </div>
          
          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                <span class="material-icons-outlined">event</span>
              </div>
            </div>
            <div class="stat-value"><?php echo $upcomingEventsCount; ?></div>
            <div class="stat-title">Upcoming Events</div>
            <a href="volunteer_events.php" class="stat-link">
              <span>View all events</span>
              <span class="material-icons-outlined">arrow_forward</span>
            </a>
          </div>
          
          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                <span class="material-icons-outlined">schedule</span>
              </div>
            </div>
            <div class="stat-value"><?php echo $volunteerHours; ?></div>
            <div class="stat-title">Total Volunteer Hours</div>
            <a href="track_volunteer.php" class="stat-link">
              <span>View hours details</span>
              <span class="material-icons-outlined">arrow_forward</span>
            </a>
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
                <a href="activity_log.php" class="view-all">
                  <span>View All</span>
                  <span class="material-icons-outlined">arrow_forward</span>
                </a>
              </div>
              
              <ul class="activity-list">
                <?php if (count($recentActivities) > 0): ?>
                  <?php foreach ($recentActivities as $activity): ?>
                    <li class="activity-item">
                      <div class="activity-icon" style="
                        <?php if ($activity['type'] == 'Task'): ?>
                          background: rgba(52, 152, 219, 0.1); color: #2980b9;
                        <?php elseif ($activity['type'] == 'Event'): ?>
                          background: rgba(243, 156, 18, 0.1); color: #f39c12;
                        <?php else: ?>
                          background: rgba(46, 204, 113, 0.1); color: #27ae60;
                        <?php endif; ?>
                      ">
                        <?php if ($activity['type'] == 'Task'): ?>
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
            
            <!-- Quick Actions -->
            <div class="quick-actions">
              <div class="section-header">
                <h2 class="section-title">Quick Actions</h2>
              </div>
              
              <div class="action-grid">
                <a href="log_hours.php" class="action-button">
                  <div class="action-button-icon">
                    <span class="material-icons-outlined">add</span>
                  </div>
                  <div class="action-button-text">Log Hours</div>
                </a>
                
                <a href="report_issue.php" class="action-button">
                  <div class="action-button-icon">
                    <span class="material-icons-outlined">report_problem</span>
                  </div>
                  <div class="action-button-text">Report Issue</div>
                </a>
                
                <a href="request_support.php" class="action-button">
                  <div class="action-button-icon">
                    <span class="material-icons-outlined">support</span>
                  </div>
                  <div class="action-button-text">Request Support</div>
                </a>
                
                <a href="view_schedule.php" class="action-button">
                  <div class="action-button-icon">
                    <span class="material-icons-outlined">calendar_today</span>
                  </div>
                  <div class="action-button-text">View Schedule</div>
                </a>
              </div>
            </div>
          </div>
          
          <!-- Right Column -->
          <div>
            <!-- Profile Card -->
            <div class="profile-card">
              <div class="profile-header">
                <div class="profile-avatar">
                  <span class="material-icons-outlined">account_circle</span>
                </div>
                <div class="profile-info">
                  <div class="profile-name"><?php echo $volunteerProfile['first_name'] . ' ' . $volunteerProfile['last_name']; ?></div>
                  <div class="profile-join-date">Member since <?php echo date('M Y', strtotime($volunteerProfile['join_date'])); ?></div>
                </div>
              </div>
              
              <div class="profile-details">
                <div class="profile-detail">
                  <div class="detail-label">Email</div>
                  <div class="detail-value"><?php echo $volunteerProfile['email']; ?></div>
                </div>
                
                <div class="profile-detail">
                  <div class="detail-label">Phone</div>
                  <div class="detail-value"><?php echo $volunteerProfile['phone']; ?></div>
                </div>
                
                <div class="profile-detail">
                  <div class="detail-label">Completed Activities</div>
                  <div class="detail-value"><?php echo $completedTasksCount; ?></div>
                </div>
                
                <div class="profile-detail">
                  <div class="detail-label">Total Hours</div>
                  <div class="detail-value"><?php echo $volunteerHours; ?> hrs</div>
                </div>
              </div>
              
              <div class="profile-actions">
                <button class="profile-btn">
                  <span class="material-icons-outlined">edit</span>
                  <span>Edit Profile</span>
                </button>
                <button class="profile-btn secondary">
                  <span class="material-icons-outlined">settings</span>
                  <span>Settings</span>
                </button>
              </div>
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