<?php
session_start();

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../index.php");
    exit();
}

// Database connection
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

// Get student's enrolled courses with teacher information
$courses_sql = "SELECT c.*, u.first_name, u.last_name, e.enrollment_date,
                (SELECT COUNT(*) FROM attendance a WHERE a.student_id = ? AND a.course_id = c.id AND a.status = 'present') as present_count,
                (SELECT COUNT(*) FROM attendance a WHERE a.student_id = ? AND a.course_id = c.id AND a.status = 'absent') as absent_count,
                (SELECT COUNT(*) FROM attendance a WHERE a.student_id = ? AND a.course_id = c.id AND a.status = 'late') as late_count,
                (SELECT COUNT(*) FROM grades g WHERE g.student_id = ? AND g.course_id = c.id) as grade_count,
                (SELECT AVG(g.grade) FROM grades g WHERE g.student_id = ? AND g.course_id = c.id) as average_grade
                FROM courses c 
                JOIN enrollments e ON c.id = e.course_id 
                LEFT JOIN users u ON c.teacher_id = u.id 
                WHERE e.student_id = ? 
                ORDER BY c.course_name";
$courses_stmt = $conn->prepare($courses_sql);
$courses_stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$courses_stmt->execute();
$courses = $courses_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total attendance and grade statistics
$total_present = 0;
$total_absent = 0;
$total_late = 0;
$total_grades = 0;
$overall_average = 0;
$grade_sum = 0;
$grade_count = 0;

foreach ($courses as $course) {
    $total_present += $course['present_count'];
    $total_absent += $course['absent_count'];
    $total_late += $course['late_count'];
    $total_grades += $course['grade_count'];
    if ($course['average_grade']) {
        $grade_sum += $course['average_grade'];
        $grade_count++;
    }
}

$overall_average = $grade_count > 0 ? $grade_sum / $grade_count : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - My Courses</title>
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
                    <li><a href="../dashboard.php">Dashboard</a></li>
                    <li><a href="courses.php" class="active">My Courses</a></li>
                    <li><a href="attendance.php">My Attendance</a></li>
                    <li><a href="grades.php">My Grades</a></li>
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>My Courses</h1>
            </header>
            
            <!-- Overall Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Enrolled Courses</h3>
                    <p class="stat-number"><?php echo count($courses); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Attendance</h3>
                    <p class="stat-number"><?php echo $total_present + $total_absent + $total_late; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Overall Average</h3>
                    <p class="stat-number"><?php echo round($overall_average, 1); ?>%</p>
                </div>
                <div class="stat-card">
                    <h3>Total Assignments</h3>
                    <p class="stat-number"><?php echo $total_grades; ?></p>
                </div>
            </div>
            
            <!-- Courses List -->
            <?php if (!empty($courses)): ?>
                <div class="courses-grid">
                    <?php foreach ($courses as $course): ?>
                        <div class="course-card">
                            <div class="course-header">
                                <h3><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></h3>
                                <span class="course-teacher">
                                    <?php echo $course['first_name'] ? htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) : 'No Teacher Assigned'; ?>
                                </span>
                            </div>
                            
                            <div class="course-description">
                                <?php echo htmlspecialchars($course['description'] ?: 'No description available'); ?>
                            </div>
                            
                            <div class="course-stats">
                                <div class="stat-item">
                                    <span class="stat-label">Enrolled:</span>
                                    <span class="stat-value"><?php echo date('M j, Y', strtotime($course['enrollment_date'])); ?></span>
                                </div>
                                
                                <div class="stat-item">
                                    <span class="stat-label">Attendance:</span>
                                    <div class="attendance-stats">
                                        <span class="attendance-badge present">Present: <?php echo $course['present_count']; ?></span>
                                        <span class="attendance-badge absent">Absent: <?php echo $course['absent_count']; ?></span>
                                        <span class="attendance-badge late">Late: <?php echo $course['late_count']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <span class="stat-label">Assignments:</span>
                                    <span class="stat-value"><?php echo $course['grade_count']; ?></span>
                                </div>
                                
                                <div class="stat-item">
                                    <span class="stat-label">Average Grade:</span>
                                    <span class="stat-value">
                                        <?php if ($course['average_grade']): ?>
                                            <span class="grade-badge"><?php echo round($course['average_grade'], 1); ?>%</span>
                                        <?php else: ?>
                                            <span class="no-grade">No grades yet</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-error">You are not enrolled in any courses yet.</div>
            <?php endif; ?>
        </main>
    </div>
    
    <style>
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .course-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .course-header {
            margin-bottom: 15px;
        }
        
        .course-header h3 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .course-teacher {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .course-description {
            color: #555;
            margin-bottom: 20px;
            line-height: 1.5;
        }
        
        .course-stats {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .stat-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .stat-value {
            color: #555;
        }
        
        .attendance-stats {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .attendance-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8rem;
        }
        
        .attendance-badge.present {
            background-color: #d4edda;
            color: #155724;
        }
        
        .attendance-badge.absent {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .attendance-badge.late {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .grade-badge {
            background-color: #3498db;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .no-grade {
            color: #7f8c8d;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .courses-grid {
                grid-template-columns: 1fr;
            }
            
            .stat-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
        }
    </style>
</body>
</html> 