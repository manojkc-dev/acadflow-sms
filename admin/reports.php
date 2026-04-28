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

// Attendance statistics
$attendance_stats_sql = "SELECT 
    COUNT(*) as total_attendance_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
FROM attendance";
$attendance_result = $conn->query($attendance_stats_sql);
$attendance_stats = $attendance_result->fetch_assoc();

// Grade statistics
$grade_stats_sql = "SELECT 
    COUNT(*) as total_grades,
    AVG(grade) as average_grade,
    MAX(grade) as highest_grade,
    MIN(grade) as lowest_grade
FROM grades";
$grade_result = $conn->query($grade_stats_sql);
$grade_stats = $grade_result->fetch_assoc();

// Recent activities
$recent_users_sql = "SELECT first_name, last_name, email, role, created_at 
                     FROM users 
                     ORDER BY created_at DESC 
                     LIMIT 5";
$recent_users_result = $conn->query($recent_users_sql);
$recent_users = $recent_users_result->fetch_all(MYSQLI_ASSOC);

$recent_grades_sql = "SELECT g.grade, g.assignment_name, u.first_name, u.last_name, c.course_name, g.created_at
                      FROM grades g
                      JOIN users u ON g.student_id = u.id
                      JOIN courses c ON g.course_id = c.id
                      ORDER BY g.created_at DESC
                      LIMIT 5";
$recent_grades_result = $conn->query($recent_grades_sql);
$recent_grades = $recent_grades_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - System Reports</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>AcadFlow</h2>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="../dashboard.php">Main Dashboard</a></li>
                    <li><a href="index.php">Admin Dashboard</a></li>
                    <li><a href="users.php">Manage Users</a></li>
                    <li><a href="courses.php">Manage Courses</a></li>
                    <li><a href="enrollments.php">Manage Enrollments</a></li>
                    <li><a href="reports.php" class="active">System Reports</a></li>
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>System Reports</h1>
            </header>
            
            <!-- System Overview Statistics -->
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
                    <h3>Attendance Records</h3>
                    <p class="stat-number"><?php echo $attendance_stats['total_attendance_records']; ?></p>
                    <div class="stat-breakdown">
                        <span>Present: <?php echo $attendance_stats['present_count']; ?></span>
                        <span>Absent: <?php echo $attendance_stats['absent_count']; ?></span>
                        <span>Late: <?php echo $attendance_stats['late_count']; ?></span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Grades</h3>
                    <p class="stat-number"><?php echo $grade_stats['total_grades']; ?></p>
                </div>
                
                <div class="stat-card">
                    <h3>Average Grade</h3>
                    <p class="stat-number"><?php echo $grade_stats['average_grade'] ? round($grade_stats['average_grade'], 1) : 'N/A'; ?></p>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="reports-grid">
                <!-- Recent Users -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Recent User Registrations</h2>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Recent Grades -->
                <div class="table-container">
                    <div class="table-header">
                        <h2>Recent Grade Assignments</h2>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Assignment</th>
                                <th>Grade</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_grades as $grade): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['assignment_name']); ?></td>
                                    <td><?php echo $grade['grade']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($grade['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .stat-breakdown {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        
        .stat-breakdown span {
            display: block;
            margin: 2px 0;
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .role-badge.role-admin {
            background-color: #e74c3c;
            color: white;
        }
        
        .role-badge.role-teacher {
            background-color: #3498db;
            color: white;
        }
        
        .role-badge.role-student {
            background-color: #2ecc71;
            color: white;
        }
        
        @media (max-width: 768px) {
            .reports-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html> 