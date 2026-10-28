<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Database connection
$server = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$db     = DB_NAME;

// Initialize variables
$firstName = "Guest";
$userRole = "Visitor";
$registrationDate = date('Y-m-d');
$userId = null;
$loginError = '';
$isLoggedIn = false;

// Check if user is already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'volunteer') {
    $userId = $_SESSION['user_id'];
    $firstName = $_SESSION['first_name'];
    $userRole = $_SESSION['user_role'];
    $isLoggedIn = true;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (!empty($username) && !empty($password)) {
        try {
            $conn = new mysqli($server, $user, $pass, $db);
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            $conn->set_charset("utf8mb4");
            
            // Check if user exists and is a volunteer
            $stmt = $conn->prepare("SELECT idnumber, firstname, userpassword, userrole FROM users WHERE username = ? AND userrole = 'volunteer'");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $userData = $result->fetch_assoc();
                
                // Verify password (assuming passwords are hashed)
                if (password_verify($password, $userData['userpassword'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $userData['idnumber'];
                    $_SESSION['first_name'] = $userData['firstname'];
                    $_SESSION['user_role'] = $userData['userrole'];
                    
                    // Set local variables
                    $userId = $userData['idnumber'];
                    $firstName = $userData['firstname'];
                    $userRole = $userData['userrole'];
                    $isLoggedIn = true;
                    
                    // Redirect to avoid form resubmission
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $loginError = "Invalid password";
                }
            } else {
                $loginError = "Volunteer not found with that username";
            }
            
            $stmt->close();
            $conn->close();
            
        } catch (Exception $e) {
            $loginError = "Database error: " . $e->getMessage();
        }
    } else {
        $loginError = "Please enter both username and password";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// If logged in, get additional user data
if ($isLoggedIn) {
    try {
        $conn = new mysqli($server, $user, $pass, $db);
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        $conn->set_charset("utf8mb4");
        
        $stmt = $conn->prepare("SELECT firstname, surname, username, userrole, registration_date FROM users WHERE idnumber = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $userData = $result->fetch_assoc();
            $firstName = $userData['firstname'];
            $userRole = $userData['userrole'];
            $registrationDate = $userData['registration_date'];
        }
        
        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        // Silently handle error - we already have basic user info from session
    }
}
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
      line-height: 1.6;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }

    header {
      background: rgba(255, 255, 255, 0.8);
      padding: 20px;
      border-radius: var(--card-radius);
      margin-bottom: 30px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
      backdrop-filter: blur(5px);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .welcome-message h1 {
      font-size: 32px;
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 5px;
    }

    .welcome-message p {
      color: #7f8c8d;
      font-size: 16px;
    }

    .user-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .user-avatar {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 24px;
    }

    .user-details {
      text-align: right;
    }

    .user-name {
      font-weight: 600;
      margin-bottom: 3px;
    }

    .user-role {
      font-size: 14px;
      color: #7f8c8d;
    }

    .logout-btn {
      background: var(--danger-color);
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 5px;
      transition: background 0.2s;
    }

    .logout-btn:hover {
      background: #c0392b;
    }

    .dashboard-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 30px;
    }

    .main-content {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
      justify-content: space-between;
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

    .login-container {
      max-width: 400px;
      margin: 50px auto;
      background: rgba(255, 255, 255, 0.9);
      padding: 30px;
      border-radius: var(--card-radius);
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      backdrop-filter: blur(5px);
    }

    .login-title {
      text-align: center;
      margin-bottom: 30px;
      color: var(--primary-color);
    }

    .login-form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .form-group label {
      font-weight: 500;
      color: var(--dark-color);
    }

    .form-group input {
      padding: 12px 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 16px;
      transition: border-color 0.2s;
    }

    .form-group input:focus {
      outline: none;
      border-color: var(--accent-color);
      box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }

    .login-btn {
      background: var(--accent-color);
      color: white;
      border: none;
      padding: 12px;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .login-btn:hover {
      background: var(--secondary-color);
    }

    .error-message {
      background: rgba(231, 76, 60, 0.1);
      color: var(--danger-color);
      padding: 10px;
      border-radius: 5px;
      border-left: 4px solid var(--danger-color);
      margin-bottom: 15px;
    }

    .quick-actions {
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

    @media (max-width: 768px) {
      .dashboard-grid {
        grid-template-columns: 1fr;
      }
      
      header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      
      .user-info {
        flex-direction: column;
      }
      
      .user-details {
        text-align: center;
      }
      
      .action-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <?php if ($isLoggedIn): ?>
      <!-- Dashboard for logged in volunteers -->
      <header>
        <div class="welcome-message">
          <h1>Welcome, <?php echo htmlspecialchars($firstName); ?>!</h1>
          <p>Thank you for volunteering with our Animal Adoption System</p>
        </div>
        <div class="user-info">
          <div class="user-avatar">
            <span class="material-icons-outlined">account_circle</span>
          </div>
          <div class="user-details">
            <div class="user-name"><?php echo htmlspecialchars($firstName); ?></div>
            <div class="user-role"><?php echo ucfirst($userRole); ?></div>
          </div>
          <a href="?logout=true" class="logout-btn">
            <span class="material-icons-outlined">logout</span>
            Logout
          </a>
        </div>
      </header>

      <div class="dashboard-grid">
        <div>
          <div class="main-content">
            <div class="stat-card">
              <div class="stat-header">
                <div class="stat-icon">
                  <span class="material-icons-outlined">assignment</span>
                </div>
              </div>
              <div class="stat-value">5</div>
              <div class="stat-title">Assigned Tasks</div>
              <a href="#" class="stat-link">
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
              <div class="stat-value">12</div>
              <div class="stat-title">Completed Activities</div>
              <a href="#" class="stat-link">
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
              <div class="stat-value">3</div>
              <div class="stat-title">Upcoming Events</div>
              <a href="#" class="stat-link">
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
              <div class="stat-value">24.5</div>
              <div class="stat-title">Total Volunteer Hours</div>
              <a href="#" class="stat-link">
                <span>View hours details</span>
                <span class="material-icons-outlined">arrow_forward</span>
              </a>
            </div>
          </div>
          
          <div class="quick-actions">
            <div class="section-header">
              <h2 class="section-title">Quick Actions</h2>
            </div>
            
            <div class="action-grid">
              <a href="#" class="action-button">
                <div class="action-button-icon">
                  <span class="material-icons-outlined">add</span>
                </div>
                <div class="action-button-text">Log Hours</div>
              </a>
              
              <a href="#" class="action-button">
                <div class="action-button-icon">
                  <span class="material-icons-outlined">report_problem</span>
                </div>
                <div class="action-button-text">Report Issue</div>
              </a>
              
              <a href="#" class="action-button">
                <div class="action-button-icon">
                  <span class="material-icons-outlined">support</span>
                </div>
                <div class="action-button-text">Request Support</div>
              </a>
              
              <a href="#" class="action-button">
                <div class="action-button-icon">
                  <span class="material-icons-outlined">calendar_today</span>
                </div>
                <div class="action-button-text">View Schedule</div>
              </a>
            </div>
          </div>
        </div>
        
        <div>
          <div class="stat-card">
            <div class="stat-header">
              <div class="stat-icon" style="background: rgba(241, 196, 15, 0.1); color: #f1c40f;">
                <span class="material-icons-outlined">info</span>
              </div>
            </div>
            <h3 class="section-title">Your Profile</h3>
            <div class="stat-title">Member since: <?php echo date('M j, Y', strtotime($registrationDate)); ?></div>
            <div class="stat-title">Role: <?php echo ucfirst($userRole); ?></div>
            <a href="#" class="stat-link">
              <span>Edit Profile</span>
              <span class="material-icons-outlined">arrow_forward</span>
            </a>
          </div>
        </div>
      </div>
    <?php else: ?>
      <!-- Login form for volunteers -->
      <div class="login-container">
        <h2 class="login-title">Volunteer Login</h2>
        
        <?php if (!empty($loginError)): ?>
          <div class="error-message">
            <span class="material-icons-outlined">error</span>
            <?php echo htmlspecialchars($loginError); ?>
          </div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
          </div>
          
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
          </div>
          
          <button type="submit" name="login" class="login-btn">Login as Volunteer</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; color: #7f8c8d;">
          Only volunteers can access this dashboard.
        </p>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>