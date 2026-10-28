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

// Database connection for dashboard
$server = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$db     = DB_NAME;

$conn = new mysqli($server, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// Initialize counts with default values
$newCrueltyCount = 0;
$pendingAdoptionsCount = 0;
$availableAnimalsCount = 0;
$recentDonationsCount = 0;
$recentActivities = [];

// Count new cruelty reports with error handling
$sql = "SELECT COUNT(*) as total FROM cruelty_report WHERE seen = 0"; 
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    $newCrueltyCount = $row['total'] ?? 0;
} else {
    error_log("Error in cruelty report query: " . $conn->error);
}

// Count pending adoptions with error handling
$sqlAdoptions = "SELECT COUNT(*) as total FROM adoptions WHERE status='Pending'";
$resultAdoptions = $conn->query($sqlAdoptions);
if ($resultAdoptions) {
    $rowAdoptions = $resultAdoptions->fetch_assoc();
    $pendingAdoptionsCount = $rowAdoptions['total'] ?? 0;
} else {
    error_log("Error in adoptions query: " . $conn->error);
}

// Count available animals with error handling
$sqlAnimals = "SELECT COUNT(*) as total FROM animals WHERE status='Available'";
$resultAnimals = $conn->query($sqlAnimals);
if ($resultAnimals) {
    $rowAnimals = $resultAnimals->fetch_assoc();
    $availableAnimalsCount = $rowAnimals['total'] ?? 0;
} else {
    error_log("Error in animals query: " . $conn->error);
}

// Count recent donations (last 30 days) with error handling - FIXED QUERY
$sqlDonations = "SELECT COUNT(*) as total FROM donations WHERE ddate >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$resultDonations = $conn->query($sqlDonations);
if ($resultDonations) {
    $rowDonations = $resultDonations->fetch_assoc();
    $recentDonationsCount = $rowDonations['total'] ?? 0;
} else {
    error_log("Error in donations query: " . $conn->error);
}

// Get recent activities with error handling - FIXED QUERY
$sqlActivities = "
    (SELECT 'Adoption' as type, CONCAT('New adoption application from ', full_name) as description, created_at as date 
     FROM adoptions ORDER BY created_at DESC LIMIT 3)
    UNION
    (SELECT 'Report' as type, CONCAT('New cruelty report received') as description, created_at as date 
     FROM cruelty_report ORDER by created_at DESC LIMIT 3)
    UNION
    (SELECT 'Donation' as type, CONCAT('New donation received') as description, ddate as date 
     FROM donations ORDER by ddate DESC LIMIT 3)
    ORDER BY date DESC LIMIT 5
";
$resultActivities = $conn->query($sqlActivities);
if ($resultActivities) {
    $recentActivities = $resultActivities->fetch_all(MYSQLI_ASSOC);
} else {
    error_log("Error in activities query: " . $conn->error);
    $recentActivities = [];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Animal Adoption System</title>
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
      justify-content: between;
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

    /* Activity Feed */
    .activity-feed {
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      border-radius: var(--card-radius);
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      margin-bottom: 30px;
      border: 1px solid rgba(255, 255, 255, 0.5);
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;
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
      border-bottom: 1px solid #f1f1f1;
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
      background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
      border-radius: var(--card-radius);
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      margin-bottom: 30px;
      border: 1px solid rgba(255, 255, 255, 0.5);
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
      background: white;
      border-radius: 12px;
      text-decoration: none;
      color: var(--text-color);
      transition: all 0.3s;
      box-shadow: 0 3px 10px rgba(0,0,0,0.05);
      border: 1px solid rgba(255, 255, 255, 0.5);
      text-align: center;
    }

    .action-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 15px rgba(0,0,0,0.1);
      color: var(--accent-color);
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
      
      .header {
        padding: 0 15px;
      }
      
      .dashboard-content {
        padding: 20px;
      }
    }
    .header {
  display: grid;
  grid-template-columns: 1fr auto;
  align-items: center;
  
}
<div class="title-container">
  <span>SPCA GUARDIAN PRO</span>
</div>
  </style>
</head>
<body>
  <div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header">
      <div class="sidebar-brand">
    <img src="Images/hug.PNG" alt="SPCA Guardian Pro Logo" class="new-logo-image" style="width: 70px; height: 70px;padding: 5px;border: 12px; object-fit:cover;border-radius: 50%;"/>
    <span>Admin Dashboard</span> <br><br>
   
</div><div class="title-container">
  
</div>



        <button class="toggle-sidebar">
          <span class="material-icons-outlined">chevron_left</span>
        </button>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-title">Main</div>
          <a href=## class="nav-item active">
            <span class="material-icons-outlined nav-icon">dashboard</span>
            <span class="nav-label">Dashboard</span>
          </a>
        </div>
        <div class="nav-section">
          <div class="nav-title">Management</div>
          <div class="nav-item" onclick="toggleDropdown('animals-dropdown')">
            <span class="material-icons-outlined nav-icon">pets</span>
            <span class="nav-label">Animals</span>
            <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
          </div>
          <div id="animals-dropdown" class="nav-dropdown">
            <a href="regA.php" class="dropdown-item">All Animals</a>
            <a href="register_animal.html" class="dropdown-item">Add New Animal</a>
            <a href="search_animals.html" class="dropdown-item">Search animal</a>
             <a href="manual allo.php" class="dropdown-item">Manual kennel allocation</a>
          </div>

           <div class="nav-item" onclick="toggleDropdown('kennels-dropdown')">
            <span class="material-icons-outlined nav-icon">house</span>
            <span class="nav-label">Kennels</span>
            <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
        </div>
        <div id="kennels-dropdown" class="nav-dropdown">
            <a href="register_kennel.html" class="dropdown-item">Add New kennel</a>
           <a href="admin_kennel.php" class="dropdown-item">All Kennels</a>
        </div>
        
          <div class="nav-item" onclick="toggleDropdown('reports-dropdown')">
            <span class="material-icons-outlined nav-icon">report</span>
            <span class="nav-label">Cruelty Reports</span>
           
            <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
          </div>
          <div id="reports-dropdown" class="nav-dropdown">
           
            <a href="view_reports.php" class="dropdown-item">View Reports</a>
          </div>
          
          <div class="nav-item" onclick="toggleDropdown('adoptions-dropdown')">
            <span class="material-icons-outlined nav-icon">favorite</span>
            <span class="nav-label">Adoptions</span>
           
            <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
          </div>
          <div id="adoptions-dropdown" class="nav-dropdown">

            <a href="admin_adoptions.php" class="dropdown-item">New Adoptions</a>
           
           <a href="1.php" class="dropdown-item">Follow up</a>
            <a href="2.php" class="dropdown-item">Complete Follow Up</a>
            <a href="2.2.php" class="dropdown-item">Adoption History</a>

          </div>


      <div class="nav-item" onclick="toggleDropdown('fosters-dropdown')">
            <span class="material-icons-outlined nav-icon">favorite</span>
            <span class="nav-label">Fosters</span>
           
        <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
          </div>
          <div id="fosters-dropdown" class="nav-dropdown">   
  <a href="foster-screen.php" class="dropdown-item">New Foster Applications</a>
           
          
           
   <a href="foster_history.php" class="dropdown-item">Foster History</a>

          </div>
         
        </div>
        
        <div class="nav-section">
          <div class="nav-title">Support</div>
          <div class="nav-item" onclick="toggleDropdown('donations-dropdown')">
            <span class="material-icons-outlined nav-icon">money</span>
            <span class="nav-label">Donations</span>
            <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
          </div>
          <div id="donations-dropdown" class="nav-dropdown">
           
            <a href="viewDonations.php" class="dropdown-item">View Donations</a>
          </div>
          
          <div class="nav-item" onclick="toggleDropdown('volunteers-dropdown')">
            <span class="material-icons-outlined nav-icon">people</span>
            <span class="nav-label">Volunteers</span>
            <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
          </div>
          <div id="volunteers-dropdown" class="nav-dropdown">
           
            <a href="Admin_Community.php" class="dropdown-item">Manage Volunteers</a>
            <a href="AdminVolunteer_track.php" class="dropdown-item">Track Volunteers</a>
          </div>

          <div class="nav-item" onclick="toggleDropdown('events-dropdown')">
            <span class="material-icons-outlined nav-icon">house</span>
            <span class="nav-label">Events</span>
            <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
          </div>
          <div id="events-dropdown" class="nav-dropdown">
            <a href="Event.html" class="dropdown-item">Create Events</a>
            <a href="viewEvents.php" class="dropdown-item">View Events</a>
            
          </div>

          <div class="nav-item" onclick="toggleDropdown('users-dropdown')">
            <span class="material-icons-outlined nav-icon">face</span>
            <span class="nav-label">Users</span>
            <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
          </div>
          <div id="users-dropdown" class="nav-dropdown">
            <a href="displaySystemUser.php" class="dropdown-item">View Users</a>
          </div>

          <div class="nav-item" onclick="toggleDropdown('subscribers-dropdown')">
            <span class="material-icons-outlined nav-icon">face</span>
            <span class="nav-label">Subscribers</span>
            <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
          </div>
          <div id="subscribers-dropdown" class="nav-dropdown">
            <a href="view_subscribers.php" class="dropdown-item">View Subscribers</a>
          </div>

          <div class="nav-item" onclick="toggleDropdown('reports-analysis-dropdown')">
    <span class="material-icons-outlined">analytics</span>
    <span class="nav-label">Reports and Analysis</span>
    <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
</div>
<div id="reports-analysis-dropdown" class="nav-dropdown">
    <a href="cruelty_trends.html" class="dropdown-item">Cruelty Trends</a>
    <a href="reports_analysis.html" class="dropdown-item">Reports Analysis</a>
    <a href="adoption_analysis.php" class="dropdown-item">Adoption Rates Analysis</a>
    <a href="medcare_reports.php" class="dropdown-item">Medcare Analysis</a>
    <a href="Animal_intake_reports.php" class="dropdown-item">Animal Intake Reports</a>
    <a href="PieChart.php" class="dropdown-item">Animal Species</a>
    <a href="DonationTrends.php" class="dropdown-item">Donation Trends</a>
    <a href="participation_chart.php" class="dropdown-item">Volunteer Participation Trends</a>
        </div>
      </nav>

      <div id="userguide-dropdown" class="nav-dropdown">
    
    <a href="adminguide.pdf" target="_blank">
    📘 User Guide
  </a>
        </div>
     
     
      <div class="nav-section">
  <div class="nav-title">Help & Guides</div>
  
  <div class="nav-item" onclick="toggleDropdown('help-dropdown')">
    <span class="material-icons-outlined nav-icon">help_outline</span>
    <span class="nav-label">Help</span>
    <span class="material-icons-outlined dropdown-arrow">chevron_right</span>
  </div>
  
  <div id="help-dropdown" class="nav-dropdown">
    <a href="adminguide.pdf" class="dropdown-item">System Guide</a>
   
   
  </div>
</div>
  </nav>
      
      <div class="sidebar-footer">
        <div class="user-info">
          <div class="user-avatar">
            <span class="material-icons-outlined">account_circle</span>
          </div>
          <div class="user-details">
            <div class="user-name"><?php echo htmlspecialchars($firstName); ?></div>
            <div class="user-role"><?php echo htmlspecialchars(ucfirst($userRole)); ?></div>
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
          <p class="welcome-message">
            Welcome back, <?php echo htmlspecialchars($firstName); ?>!
          </p>
        </div>
        <div class="header-actions">
          <a href="4.php" class="action-btn">
            <span class="material-icons-outlined">mail</span>
          </a>
          
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
                <span class="material-icons-outlined">pets</span>
              </div>
            </div>
            <div class="stat-value"><?php echo $availableAnimalsCount; ?></div>
            <div class="stat-title"> Animals Ready For Adoption</div>
            <a href="regA.php" class="stat-link">
              <span>View all animals</span>
              <span class="material-icons-outlined">arrow_forward</span>
            </a>
          </div>
          
          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon" style="background: rgba(231, 76, 60, 0.1); color: #e74c3c;">
                <span class="material-icons-outlined">favorite</span>
              </div>
            </div>
            <div class="stat-value"><?php echo $pendingAdoptionsCount; ?></div>
            <div class="stat-title">Pending Adoptions</div>
            <a href="admin_adoptions.php" class="stat-link">
              <span>Review applications</span>
              <span class="material-icons-outlined">arrow_forward</span>
            </a>
          </div>
          
          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                <span class="material-icons-outlined">report</span>
              </div>
            </div>
            <div class="stat-value"><?php echo $newCrueltyCount; ?></div>
            <div class="stat-title">New Cruelty Reports</div>
            <a href="view_reports.php" class="stat-link">
              <span>View reports</span>
              <span class="material-icons-outlined">arrow_forward</span>
            </a>
          </div>
          
          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon" style="background: rgba(243, 156, 18, 0.1); color: #f39c12;">
                <span class="material-icons-outlined">money</span>
              </div>
            </div>
            <div class="stat-value"><?php echo $recentDonationsCount; ?></div>
            <div class="stat-title">Recent Donations</div>
            <a href="viewDonations.php" class="stat-link">
              <span>View donations</span>
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
              </div>
              
              <ul class="activity-list">
                <?php if (count($recentActivities) > 0): ?>
                  <?php foreach ($recentActivities as $activity): ?>
                    <li class="activity-item">
                      <div class="activity-icon" style="
                        <?php if ($activity['type'] == 'Adoption'): ?>
                          background: rgba(46, 204, 113, 0.1); color: #27ae60;
                        <?php elseif ($activity['type'] == 'Report'): ?>
                          background: rgba(231, 76, 60, 0.1); color: #c0392b;
                        <?php else: ?>
                          background: rgba(52, 152, 219, 0.1); color: #2980b9;
                        <?php endif; ?>
                      ">
                        <?php if ($activity['type'] == 'Adoption'): ?>
                          <span class="material-icons-outlined">favorite</span>
                        <?php elseif ($activity['type'] == 'Report'): ?>
                          <span class="material-icons-outlined">report</span>
                        <?php else: ?>
                          <span class="material-icons-outlined">money</span>
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
                      <div class="activity-description">No recent activities</div>
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
                <a href="register_animal.php" class="action-button">
                  <div class="action-button-icon">
                    <span class="material-icons-outlined">add</span>
                  </div>
                  <span class="action-button-text">Register New Animal</span>
                </a>
                
                <a href="admin_adoptions.php" class="action-button">
                  <div class="action-button-icon">
                    <span class="material-icons-outlined">rate_review</span>
                  </div>
                  <span class="action-button-text">Review Adoptions</span>
                </a>
                
                <a href="view_reports.php" class="action-button">
                  <div class="action-button-icon">
                    <span class="material-icons-outlined">list_alt</span>
                  </div>
                  <span class="action-button-text">Check Reports</span>
                </a>
                
                <a href="viewDonations.php" class="action-button">
                  <div class="action-button-icon">
                    <span class="material-icons-outlined">analytics</span>
                  </div>
                  <span class="action-button-text">View Donations</span>
                </a>
              </div>
            </div>
          </div>
          
          <!-- Right Column -->
          <div>
            <!-- Quick Actions Placeholder -->
            <!-- Additional content can be added here -->
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

  </script>
</body>
</html>
