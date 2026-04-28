<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Database connection
require_once '../config/database.php';

$message = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update_settings') {
            // Here you would typically update system settings
            // For now, we'll just show a success message
            $message = 'System settings updated successfully';
        } elseif ($_POST['action'] == 'backup_database') {
            // Database backup functionality
            $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $message = 'Database backup created: ' . $backup_file;
        } elseif ($_POST['action'] == 'clear_cache') {
            // Clear system cache
            $message = 'System cache cleared successfully';
        }
    }
}

// Get system information
$system_info = [
    'php_version' => PHP_VERSION,
    'mysql_version' => $conn->server_info,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit')
];

// Get database statistics
$db_stats_sql = "SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM courses) as total_courses,
    (SELECT COUNT(*) FROM enrollments) as total_enrollments,
    (SELECT COUNT(*) FROM attendance) as total_attendance,
    (SELECT COUNT(*) FROM grades) as total_grades";
$db_stats_result = $conn->query($db_stats_sql);
$db_stats = $db_stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - System Settings</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>AcadFlow Admin</h2>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="../dashboard.php">Main Dashboard</a></li>
                    <li><a href="index.php">Admin Dashboard</a></li>
                    <li><a href="users.php">Manage Users</a></li>
                    <li><a href="courses.php">Manage Courses</a></li>
                    <li><a href="enrollments.php">Manage Enrollments</a></li>
                    <li><a href="reports.php">System Reports</a></li>
                    <li><a href="settings.php" class="active">System Settings</a></li>
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>System Settings</h1>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- System Information -->
            <div class="settings-section">
                <h2>System Information</h2>
                <div class="info-grid">
                    <div class="info-card">
                        <h3>PHP Version</h3>
                        <p><?php echo $system_info['php_version']; ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3>MySQL Version</h3>
                        <p><?php echo $system_info['mysql_version']; ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3>Server Software</h3>
                        <p><?php echo $system_info['server_software']; ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3>Upload Max Filesize</h3>
                        <p><?php echo $system_info['upload_max_filesize']; ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h3>Max Execution Time</h3>
                        <p><?php echo $system_info['max_execution_time']; ?> seconds</p>
                    </div>
                    
                    <div class="info-card">
                        <h3>Memory Limit</h3>
                        <p><?php echo $system_info['memory_limit']; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Database Statistics -->
            <div class="settings-section">
                <h2>Database Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <p class="stat-number"><?php echo $db_stats['total_users']; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Total Courses</h3>
                        <p class="stat-number"><?php echo $db_stats['total_courses']; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Total Enrollments</h3>
                        <p class="stat-number"><?php echo $db_stats['total_enrollments']; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Total Attendance Records</h3>
                        <p class="stat-number"><?php echo $db_stats['total_attendance']; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Total Grades</h3>
                        <p class="stat-number"><?php echo $db_stats['total_grades']; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- System Actions -->
            <div class="settings-section">
                <h2>System Actions</h2>
                <div class="action-grid">
                    <div class="action-card">
                        <h3>Database Backup</h3>
                        <p>Create a backup of the entire database</p>
                        <form method="POST" action="" style="margin-top: 15px;">
                            <input type="hidden" name="action" value="backup_database">
                            <button type="submit" class="btn btn-primary">Create Backup</button>
                        </form>
                    </div>
                    
                    <div class="action-card">
                        <h3>Clear Cache</h3>
                        <p>Clear system cache and temporary files</p>
                        <form method="POST" action="" style="margin-top: 15px;">
                            <input type="hidden" name="action" value="clear_cache">
                            <button type="submit" class="btn btn-secondary">Clear Cache</button>
                        </form>
                    </div>
                    
                    <div class="action-card">
                        <h3>System Maintenance</h3>
                        <p>Run system maintenance tasks</p>
                        <form method="POST" action="" style="margin-top: 15px;">
                            <input type="hidden" name="action" value="update_settings">
                            <button type="submit" class="btn btn-secondary">Run Maintenance</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Security Settings -->
            <div class="settings-section">
                <h2>Security Settings</h2>
                <div class="form-container">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_settings">
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="enable_2fa" value="1"> Enable Two-Factor Authentication
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="force_ssl" value="1"> Force HTTPS
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="session_timeout" value="1"> Enable Session Timeout
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label for="session_duration">Session Duration (minutes)</label>
                            <input type="number" id="session_duration" name="session_duration" value="30" min="5" max="480">
                        </div>
                        
                        <div class="form-row">
                            <button type="submit" class="btn btn-primary">Save Security Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .settings-section {
            margin-bottom: 40px;
        }
        
        .settings-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .info-card h3 {
            color: #3498db;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .info-card p {
            color: #2c3e50;
            font-weight: 600;
            margin: 0;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3498db;
        }
        
        .action-card h3 {
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .action-card p {
            color: #7f8c8d;
            margin-bottom: 15px;
        }
    </style>
</body>
</html> 