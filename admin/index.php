<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Database connection
require_once '../config/database.php';

// Get system statistics
$user_stats_sql = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admins,
    SUM(CASE WHEN role = 'teacher' THEN 1 ELSE 0 END) as total_teachers,
    SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as total_students
FROM users";
$user_result = $conn->query($user_stats_sql);
$user_stats = $user_result->fetch_assoc();

// Course statistics
$course_stats_sql = "SELECT 
    COUNT(*) as total_courses,
    COUNT(CASE WHEN teacher_id IS NOT NULL THEN 1 END) as assigned_courses,
    COUNT(CASE WHEN teacher_id IS NULL THEN 1 END) as unassigned_courses
FROM courses";
$course_result = $conn->query($course_stats_sql);
$course_stats = $course_result->fetch_assoc();

// Enrollment statistics
$enrollment_stats_sql = "SELECT COUNT(*) as total_enrollments FROM enrollments";
$enrollment_result = $conn->query($enrollment_stats_sql);
$enrollment_stats = $enrollment_result->fetch_assoc();

// Recent activities
$recent_users_sql = "SELECT first_name, last_name, email, role, created_at 
                     FROM users 
                     ORDER BY created_at DESC 
                     LIMIT 5";
$recent_users_result = $conn->query($recent_users_sql);
$recent_users = $recent_users_result->fetch_all(MYSQLI_ASSOC);

// Get courses with teacher info
$courses_sql = "SELECT c.*, u.first_name, u.last_name, 
                (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) as student_count
                FROM courses c
                LEFT JOIN users u ON c.teacher_id = u.id
                ORDER BY c.course_name";
$courses_result = $conn->query($courses_sql);
$courses = $courses_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - Admin Dashboard</title>
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
                    <li><a href="index.php" class="active">Admin Dashboard</a></li>
                    <li><a href="users.php">Manage Users</a></li>
                    <li><a href="courses.php">Manage Courses</a></li>
                    <li><a href="enrollments.php">Manage Enrollments</a></li>
                    <li><a href="reports.php">System Reports</a></li>
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>!</p>
            </header>
            
            <!-- Quick Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <p class="stat-number"><?php echo $user_stats['total_users']; ?></p>
                    <div class="stat-breakdown">
                        <span>Admins: <?php echo $user_stats['total_admins']; ?></span>
                        <span>Teachers: <?php echo $user_stats['total_teachers']; ?></span>
                        <span>Students: <?php echo $user_stats['total_students']; ?></span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Courses</h3>
                    <p class="stat-number"><?php echo $course_stats['total_courses']; ?></p>
                    <div class="stat-breakdown">
                        <span>Assigned: <?php echo $course_stats['assigned_courses']; ?></span>
                        <span>Unassigned: <?php echo $course_stats['unassigned_courses']; ?></span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Enrollments</h3>
                    <p class="stat-number"><?php echo $enrollment_stats['total_enrollments']; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>System Status</h3>
                    <p class="stat-number">Active</p>
                </div>
            </div>
            
            <!-- Admin Actions -->
            <div class="admin-actions">
                <h2>Quick Actions</h2>
                <div class="action-grid">
                    <a href="users.php?action=add" class="action-card">
                        <h3>Add New User</h3>
                        <p>Create new admin, teacher, or student accounts</p>
                    </a>
                    
                    <a href="courses.php?action=add" class="action-card">
                        <h3>Add New Course</h3>
                        <p>Create new course offerings</p>
                    </a>
                    
                    <a href="enrollments.php" class="action-card">
                        <h3>Manage Enrollments</h3>
                        <p>Enroll students in courses</p>
                    </a>
                    
                    <a href="reports.php" class="action-card">
                        <h3>View Reports</h3>
                        <p>System analytics and statistics</p>
                    </a>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="recent-activities">
                <h2>Recent User Registrations</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="users.php?edit=<?php echo $user['id']; ?>" class="btn btn-secondary">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Course Overview -->
            <div class="course-overview">
                <h2>Course Overview</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Teacher</th>
                                <th>Students</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                    <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                    <td>
                                        <?php if ($course['first_name']): ?>
                                            <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?>
                                        <?php else: ?>
                                            <span class="no-teacher">No Teacher Assigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $course['student_count']; ?></td>
                                    <td>
                                        <a href="courses.php?edit=<?php echo $course['id']; ?>" class="btn btn-secondary">Edit</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .admin-actions {
            margin: 30px 0;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .action-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-left: 4px solid #3498db;
        }
        
        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .action-card h3 {
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .action-card p {
            color: #7f8c8d;
            margin: 0;
        }
        
        .recent-activities, .course-overview {
            margin: 30px 0;
        }
        
        .no-teacher {
            color: #e74c3c;
            font-style: italic;
        }
        
        .stat-breakdown {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        
        .stat-breakdown span {
            display: block;
            margin: 2px 0;
        }
    </style>
</body>
</html> 