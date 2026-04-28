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

// Handle enrollment actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $student_id = $_POST['student_id'];
            $course_id = $_POST['course_id'];
            
            if (empty($student_id) || empty($course_id)) {
                $error = 'Please select both student and course';
            } else {
                // Check if enrollment already exists
                $check_sql = "SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("ii", $student_id, $course_id);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error = 'Student is already enrolled in this course';
                } else {
                    $sql = "INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $student_id, $course_id);
                    
                    if ($stmt->execute()) {
                        $message = 'Student enrolled successfully';
                    } else {
                        $error = 'Error enrolling student';
                    }
                }
            }
        } elseif ($_POST['action'] == 'delete') {
            $enrollment_id = $_POST['enrollment_id'];
            
            $sql = "DELETE FROM enrollments WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $enrollment_id);
            
            if ($stmt->execute()) {
                $message = 'Enrollment removed successfully';
            } else {
                $error = 'Error removing enrollment';
            }
        }
    }
}

// Get all enrollments with student and course details
$enrollments_sql = "SELECT e.*, 
                    u.first_name, u.last_name, u.email,
                    c.course_code, c.course_name,
                    t.first_name as teacher_first, t.last_name as teacher_last
                    FROM enrollments e
                    JOIN users u ON e.student_id = u.id
                    JOIN courses c ON e.course_id = c.id
                    LEFT JOIN users t ON c.teacher_id = t.id
                    ORDER BY e.enrollment_date DESC";
$enrollments_result = $conn->query($enrollments_sql);
$enrollments = $enrollments_result->fetch_all(MYSQLI_ASSOC);

// Get students for dropdown
$students_sql = "SELECT id, first_name, last_name, email FROM users WHERE role = 'student' ORDER BY last_name, first_name";
$students_result = $conn->query($students_sql);
$students = $students_result->fetch_all(MYSQLI_ASSOC);

// Get courses for dropdown
$courses_sql = "SELECT id, course_code, course_name FROM courses ORDER BY course_name";
$courses_result = $conn->query($courses_sql);
$courses = $courses_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - Manage Enrollments</title>
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
                    <li><a href="enrollments.php" class="active">Manage Enrollments</a></li>
                    <li><a href="reports.php">System Reports</a></li>
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>Manage Enrollments</h1>
                <button class="btn btn-primary" onclick="showAddForm()">Add New Enrollment</button>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Add Enrollment Form -->
            <div class="form-container" id="enrollmentForm" style="display: none;">
                <h2>Add New Enrollment</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="student_id">Student</label>
                            <select id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="course_id">Course</label>
                            <select id="course_id" name="course_id" required>
                                <option value="">Select Course</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>">
                                        <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <button type="submit" class="btn btn-primary">Enroll Student</button>
                        <button type="button" class="btn btn-secondary" onclick="hideForm()">Cancel</button>
                    </div>
                </form>
            </div>
            
            <!-- Enrollments Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>All Enrollments</h2>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Teacher</th>
                            <th>Enrollment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td>
                                    <div class="student-info">
                                        <strong><?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?></strong>
                                        <br>
                                        <small><?php echo htmlspecialchars($enrollment['email']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="course-info">
                                        <strong><?php echo htmlspecialchars($enrollment['course_code'] . ' - ' . $enrollment['course_name']); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($enrollment['teacher_first']): ?>
                                        <?php echo htmlspecialchars($enrollment['teacher_first'] . ' ' . $enrollment['teacher_last']); ?>
                                    <?php else: ?>
                                        <span class="no-teacher">No Teacher Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['id']; ?>">
                                        <button type="submit" class="btn btn-secondary" onclick="return confirm('Are you sure you want to remove this enrollment?')">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <style>
        .student-info, .course-info {
            line-height: 1.4;
        }
        
        .no-teacher {
            color: #e74c3c;
            font-style: italic;
        }
    </style>
    
    <script>
        function showAddForm() {
            document.getElementById('enrollmentForm').style.display = 'block';
            document.getElementById('enrollmentForm').scrollIntoView({ behavior: 'smooth' });
        }
        
        function hideForm() {
            document.getElementById('enrollmentForm').style.display = 'none';
            document.querySelector('#enrollmentForm form').reset();
        }
    </script>
</body>
</html> 