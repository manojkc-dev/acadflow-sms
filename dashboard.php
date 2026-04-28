<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Database connection
require_once 'config/database.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];

// Get dashboard statistics based on role
$stats = [];

if ($role == 'admin') {
    // Admin statistics
    $sql = "SELECT 
        (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
        (SELECT COUNT(*) FROM users WHERE role = 'teacher') as total_teachers,
        (SELECT COUNT(*) FROM courses) as total_courses,
        (SELECT COUNT(*) FROM users) as total_users";
    $result = $conn->query($sql);
    $stats = $result->fetch_assoc();
} elseif ($role == 'teacher') {
    // Teacher statistics
    $sql = "SELECT 
        (SELECT COUNT(*) FROM courses WHERE teacher_id = ?) as my_courses,
        (SELECT COUNT(*) FROM enrollments e JOIN courses c ON e.course_id = c.id WHERE c.teacher_id = ?) as total_students,
        (SELECT COUNT(*) FROM attendance a JOIN courses c ON a.course_id = c.id WHERE c.teacher_id = ? AND a.date = CURDATE()) as today_attendance";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
} else {
    // Student statistics
    $sql = "SELECT 
        (SELECT COUNT(*) FROM enrollments WHERE student_id = ?) as enrolled_courses,
        (SELECT COUNT(*) FROM attendance WHERE student_id = ? AND date = CURDATE()) as today_attendance,
        (SELECT COUNT(*) FROM grades WHERE student_id = ?) as total_grades";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $user_id, $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li><a href="admin/index.php">Admin Dashboard</a></li>
                        <li><a href="admin/users.php">Manage Users</a></li>
                        <li><a href="admin/courses.php">Manage Courses</a></li>
                        <li><a href="admin/enrollments.php">Manage Enrollments</a></li>
                        <li><a href="admin/reports.php">Reports</a></li>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role'] == 'teacher'): ?>
                        <li><a href="teacher/students.php">My Students</a></li>
                        <li><a href="teacher/attendance.php">Attendance</a></li>
                        <li><a href="teacher/grades.php">Grades</a></li>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role'] == 'student'): ?>
                        <li><a href="student/courses.php">My Courses</a></li>
                        <li><a href="student/attendance.php">My Attendance</a></li>
                        <li><a href="student/grades.php">My Grades</a></li>
                    <?php endif; ?>
                    
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>Welcome, <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>!</h1>
                <div class="user-info">
                    <span><?php echo ucfirst($role); ?></span>
                </div>
            </header>
            
            <div class="dashboard-content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <?php if ($role == 'admin'): ?>
                        <div class="stat-card">
                            <h3>Total Students</h3>
                            <p class="stat-number"><?php echo $stats['total_students']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Total Teachers</h3>
                            <p class="stat-number"><?php echo $stats['total_teachers']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Total Courses</h3>
                            <p class="stat-number"><?php echo $stats['total_courses']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Total Users</h3>
                            <p class="stat-number"><?php echo $stats['total_users']; ?></p>
                        </div>
                    <?php elseif ($role == 'teacher'): ?>
                        <div class="stat-card">
                            <h3>My Courses</h3>
                            <p class="stat-number"><?php echo $stats['my_courses']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Total Students</h3>
                            <p class="stat-number"><?php echo $stats['total_students']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Today's Attendance</h3>
                            <p class="stat-number"><?php echo $stats['today_attendance']; ?></p>
                        </div>
                    <?php else: ?>
                        <div class="stat-card">
                            <h3>Enrolled Courses</h3>
                            <p class="stat-number"><?php echo $stats['enrolled_courses']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Today's Attendance</h3>
                            <p class="stat-number"><?php echo $stats['today_attendance']; ?></p>
                        </div>
                        <div class="stat-card">
                            <h3>Total Grades</h3>
                            <p class="stat-number"><?php echo $stats['total_grades']; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2>Quick Actions</h2>
                    <div class="actions-grid">
                        <?php if ($role == 'admin'): ?>
                            <a href="admin/users.php" class="action-card">
                                <h3>Manage Users</h3>
                                <p>Add, edit, or remove users</p>
                            </a>
                            <a href="admin/courses.php" class="action-card">
                                <h3>Manage Courses</h3>
                                <p>Create and manage courses</p>
                            </a>
                            <a href="admin/reports.php" class="action-card">
                                <h3>View Reports</h3>
                                <p>System analytics and reports</p>
                            </a>
                        <?php elseif ($role == 'teacher'): ?>
                            <a href="teacher/attendance.php" class="action-card">
                                <h3>Mark Attendance</h3>
                                <p>Record student attendance</p>
                            </a>
                            <a href="teacher/grades.php" class="action-card">
                                <h3>Assign Grades</h3>
                                <p>Grade student assignments</p>
                            </a>
                            <a href="teacher/students.php" class="action-card">
                                <h3>View Students</h3>
                                <p>See enrolled students</p>
                            </a>
                        <?php else: ?>
                            <a href="student/courses.php" class="action-card">
                                <h3>My Courses</h3>
                                <p>View enrolled courses</p>
                            </a>
                            <a href="student/grades.php" class="action-card">
                                <h3>My Grades</h3>
                                <p>Check your grades</p>
                            </a>
                            <a href="student/attendance.php" class="action-card">
                                <h3>My Attendance</h3>
                                <p>View attendance records</p>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Role-specific sections -->
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <div class="dashboard-section">
                        <h2>Administration</h2>
                        <div class="feature-grid">
                            <a href="admin/index.php" class="feature-card">
                                <h3>Admin Dashboard</h3>
                                <p>Complete admin control panel and system overview</p>
                            </a>
                            <a href="admin/users.php" class="feature-card">
                                <h3>Manage Users</h3>
                                <p>Add, edit, and manage system users</p>
                            </a>
                            <a href="admin/courses.php" class="feature-card">
                                <h3>Manage Courses</h3>
                                <p>Create and manage course offerings</p>
                            </a>
                            <a href="admin/enrollments.php" class="feature-card">
                                <h3>Manage Enrollments</h3>
                                <p>Enroll students in courses and manage registrations</p>
                            </a>
                            <a href="admin/reports.php" class="feature-card">
                                <h3>System Reports</h3>
                                <p>View system statistics and analytics</p>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] == 'teacher'): ?>
                    <div class="dashboard-section">
                        <h2>Teaching</h2>
                        <div class="feature-grid">
                            <a href="teacher/students.php" class="feature-card">
                                <h3>My Students</h3>
                                <p>View enrolled students and their progress</p>
                            </a>
                            <a href="teacher/attendance.php" class="feature-card">
                                <h3>Attendance</h3>
                                <p>Mark and manage student attendance</p>
                            </a>
                            <a href="teacher/grades.php" class="feature-card">
                                <h3>Grades</h3>
                                <p>Assign and manage student grades</p>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($_SESSION['role'] == 'student'): ?>
                    <div class="dashboard-section">
                        <h2>My Academic</h2>
                        <div class="feature-grid">
                            <a href="student/courses.php" class="feature-card">
                                <h3>My Courses</h3>
                                <p>View enrolled courses and details</p>
                            </a>
                            <a href="student/attendance.php" class="feature-card">
                                <h3>My Attendance</h3>
                                <p>Check your attendance records</p>
                            </a>
                            <a href="student/grades.php" class="feature-card">
                                <h3>My Grades</h3>
                                <p>View your grades and performance</p>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="assets/js/dashboard.js"></script>
</body>
</html> 