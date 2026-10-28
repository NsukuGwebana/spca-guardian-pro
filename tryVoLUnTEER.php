<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Dashboard</title>
    <style>
        :root {
            --primary: #3498db;
            --secondary: #2ecc71;
            --accent: #e74c3c;
            --light: #f8f9fa;
            --dark: #2c3e50;
            --gray: #6c757d;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--dark);
        }
        
        .dashboard-header {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
            position: relative;
        }
        
        .dashboard-header h1 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 32px;
        }
        
        .welcome-message {
            font-size: 18px;
            color: var(--gray);
            margin-bottom: 15px;
        }
        
        .user-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .info-badge {
            background: var(--light);
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--accent);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 16px;
            color: var(--gray);
        }
        
        .actions-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }
        
        .action-icon {
            font-size: 36px;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .action-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .action-desc {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 15px;
        }
        
        .action-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background: #2980b9;
        }
        
        .recent-activities {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            margin-top: 30px;
        }
        
        .section-title {
            font-size: 22px;
            margin-bottom: 20px;
            color: var(--dark);
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light);
        }
        
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid var(--light);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            font-size: 20px;
            margin-right: 15px;
            color: var(--primary);
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .activity-time {
            font-size: 14px;
            color: var(--gray);
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .actions-container {
                grid-template-columns: 1fr;
            }
            
            .user-info {
                flex-direction: column;
                align-items: center;
            }
        }
        
        .no-data {
            text-align: center;
            padding: 30px;
            color: var(--gray);
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1>Volunteer Dashboard</h1>
        <p class="welcome-message">Welcome back, valued volunteer! Thank you for your dedication.</p>
        
        <div class="user-info">
            <div class="info-badge">
                <span>👤</span> ID: <?= htmlspecialchars($_SESSION['idnumber'] ?? 'N/A') ?>
            </div>
            <div class="info-badge">
                <span>📧</span> Email: <?= htmlspecialchars($volunteerEmail) ?>
            </div>
            <div class="info-badge">
                <span>🔄</span> Last Login: <?= date('Y-m-d H:i') ?>
            </div>
        </div>
        
        <button class="logout-btn" onclick="logout()">Logout</button>
    </div>
    
    <div class="stats-container">
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-value"><?= $completedTasksCount ?></div>
            <div class="stat-label">Activities Completed</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">⏱️</div>
            <div class="stat-value"><?= $volunteerHours ?></div>
            <div class="stat-label">Hours Volunteered</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value"><?= round($volunteerHours / max($completedTasksCount, 1), 1) ?></div>
            <div class="stat-label">Avg. Hours Per Activity</div>
        </div>
    </div>
    
    <div class="actions-container">
        <div class="action-card" onclick="location.href='log_activity.php'">
            <div class="action-icon">➕</div>
            <div class="action-title">Log New Activity</div>
            <div class="action-desc">Record your volunteer hours and activities</div>
            <button class="action-btn">Log Activity</button>
        </div>
        
        <div class="action-card" onclick="location.href='my_activities.php'">
            <div class="action-icon">📋</div>
            <div class="action-title">View My Activities</div>
            <div class="action-desc">See your volunteering history and details</div>
            <button class="action-btn">View History</button>
        </div>
        
        <div class="action-card" onclick="location.href='profile.php'">
            <div class="action-icon">👤</div>
            <div class="action-title">Update Profile</div>
            <div class="action-desc">Manage your personal information</div>
            <button class="action-btn">Edit Profile</button>
        </div>
    </div>
    
    <div class="recent-activities">
        <h2 class="section-title">Recent Activities</h2>
        
        <?php if ($completedTasksCount > 0): ?>
            <ul class="activity-list">
                <li class="activity-item">
                    <div class="activity-icon">✅</div>
                    <div class="activity-content">
                        <div class="activity-title">Community Cleanup</div>
                        <div class="activity-time">Completed: 3 hours ago</div>
                    </div>
                </li>
                <li class="activity-item">
                    <div class="activity-icon">✅</div>
                    <div class="activity-content">
                        <div class="activity-title">Food Drive Assistance</div>
                        <div class="activity-time">Completed: 2 days ago</div>
                    </div>
                </li>
                <li class="activity-item">
                    <div class="activity-icon">✅</div>
                    <div class="activity-content">
                        <div class="activity-title">Tutoring Session</div>
                        <div class="activity-time">Completed: 1 week ago</div>
                    </div>
                </li>
            </ul>
        <?php else: ?>
            <div class="no-data">
                <p>You haven't logged any activities yet.</p>
                <p>Get started by logging your first volunteer activity!</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
        
        // Simple animations
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('fade-in');
            });
        });
    </script>
</body>
</html>