<?php
session_start();

// Check if user is logged in and is teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

// Database connection
require_once '../config/database.php';

$user_id = $_SESSION['user_id'];

// Get teacher's courses
$courses_sql = "SELECT id, course_code, course_name FROM courses WHERE teacher_id = ? ORDER BY course_name";
$courses_stmt = $conn->prepare($courses_sql);
$courses_stmt->bind_param("i", $user_id);
$courses_stmt->execute();
$courses = $courses_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get students for selected course
$students = [];
$selected_course = null;

if (isset($_GET['course_id']) && is_numeric($_GET['course_id'])) {
    $selected_course = $_GET['course_id'];
    $students_sql = "SELECT u.id, u.first_name, u.last_name, u.email, e.enrollment_date,
                     (SELECT COUNT(*) FROM attendance a WHERE a.student_id = u.id AND a.course_id = ? AND a.status = 'present') as present_count,
                     (SELECT COUNT(*) FROM attendance a WHERE a.student_id = u.id AND a.course_id = ? AND a.status = 'absent') as absent_count,
                     (SELECT COUNT(*) FROM attendance a WHERE a.student_id = u.id AND a.course_id = ? AND a.status = 'late') as late_count,
                     (SELECT COUNT(*) FROM grades g WHERE g.student_id = u.id AND g.course_id = ?) as grade_count,
                     (SELECT AVG(g.grade) FROM grades g WHERE g.student_id = u.id AND g.course_id = ?) as average_grade
                     FROM users u 
                     JOIN enrollments e ON u.id = e.student_id 
                     WHERE e.course_id = ? AND u.role = 'student' 
                     ORDER BY u.last_name, u.first_name";
    $students_stmt = $conn->prepare($students_sql);
    $students_stmt->bind_param("iiiiii", $selected_course, $selected_course, $selected_course, $selected_course, $selected_course, $selected_course);
    $students_stmt->execute();
    $students = $students_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - My Students</title>
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
                    <li><a href="students.php" class="active">My Students</a></li>
                    <li><a href="attendance.php">Attendance</a></li>
                    <li><a href="grades.php">Grades</a></li>
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>My Students</h1>
            </header>
            
            <!-- Course Selection -->
            <div class="form-container">
                <h2>Select Course</h2>
                <form method="GET" action="">
                    <div class="form-group">
                        <label for="course_id">Course</label>
                        <select id="course_id" name="course_id" required onchange="this.form.submit()">
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo ($selected_course == $course['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <!-- Students Table -->
            <?php if ($selected_course && !empty($students)): ?>
                <div class="table-container">
                    <div class="table-header">
                        <h2>Enrolled Students</h2>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Enrollment Date</th>
                                <th>Attendance</th>
                                <th>Grades</th>
                                <th>Average Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($student['enrollment_date'])); ?></td>
                                    <td>
                                        <span class="attendance-badge present">Present: <?php echo $student['present_count']; ?></span>
                                        <span class="attendance-badge absent">Absent: <?php echo $student['absent_count']; ?></span>
                                        <span class="attendance-badge late">Late: <?php echo $student['late_count']; ?></span>
                                    </td>
                                    <td><?php echo $student['grade_count']; ?> assignments</td>
                                    <td>
                                        <?php if ($student['average_grade']): ?>
                                            <span class="grade-badge"><?php echo round($student['average_grade'], 1); ?>%</span>
                                        <?php else: ?>
                                            <span class="no-grade">No grades yet</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($selected_course && empty($students)): ?>
                <div class="alert alert-error">No students enrolled in this course.</div>
            <?php endif; ?>
        </main>
    </div>
    
    <style>
        .attendance-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.8rem;
            margin: 1px;
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
    </style>
</body>
</html> 