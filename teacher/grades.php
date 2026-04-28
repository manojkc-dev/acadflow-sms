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
$message = '';
$error = '';

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];
    $assignment_name = trim($_POST['assignment_name']);
    $grade = $_POST['grade'];
    $max_grade = $_POST['max_grade'];
    $comments = trim($_POST['comments']);
    
    if (empty($student_id) || empty($course_id) || empty($assignment_name) || empty($grade) || empty($max_grade)) {
        $error = 'Please fill in all required fields';
    } elseif ($grade > $max_grade) {
        $error = 'Grade cannot be higher than maximum grade';
    } elseif ($grade < 0) {
        $error = 'Grade cannot be negative';
    } else {
        $sql = "INSERT INTO grades (student_id, course_id, assignment_name, grade, max_grade, comments, assigned_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisddss", $student_id, $course_id, $assignment_name, $grade, $max_grade, $comments, $user_id);
        
        if ($stmt->execute()) {
            $message = 'Grade assigned successfully';
        } else {
            $error = 'Error assigning grade';
        }
    }
}

// Handle grade deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // Verify the grade belongs to this teacher's course
    $verify_sql = "SELECT g.id FROM grades g 
                   JOIN courses c ON g.course_id = c.id 
                   WHERE g.id = ? AND c.teacher_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $delete_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows > 0) {
        $delete_sql = "DELETE FROM grades WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $delete_id);
        
        if ($delete_stmt->execute()) {
            $message = 'Grade deleted successfully';
        } else {
            $error = 'Error deleting grade';
        }
    } else {
        $error = 'Unauthorized to delete this grade';
    }
}

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
    $students_sql = "SELECT u.id, u.first_name, u.last_name, u.email 
                     FROM users u 
                     JOIN enrollments e ON u.id = e.student_id 
                     WHERE e.course_id = ? AND u.role = 'student' 
                     ORDER BY u.last_name, u.first_name";
    $students_stmt = $conn->prepare($students_sql);
    $students_stmt->bind_param("i", $selected_course);
    $students_stmt->execute();
    $students = $students_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Get existing grades for selected course
$grades = [];
if ($selected_course) {
    $grades_sql = "SELECT g.*, u.first_name, u.last_name, u.email 
                   FROM grades g 
                   JOIN users u ON g.student_id = u.id 
                   WHERE g.course_id = ? 
                   ORDER BY g.created_at DESC";
    $grades_stmt = $conn->prepare($grades_sql);
    $grades_stmt->bind_param("i", $selected_course);
    $grades_stmt->execute();
    $grades = $grades_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - Manage Grades</title>
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
                    <li><a href="students.php">My Students</a></li>
                    <li><a href="attendance.php">Attendance</a></li>
                    <li><a href="grades.php" class="active">Grades</a></li>
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>Manage Grades</h1>
                <button class="btn btn-primary" onclick="showAddForm()">Assign New Grade</button>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
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
            
            <!-- Add Grade Form -->
            <?php if ($selected_course && !empty($students)): ?>
                <div class="form-container" id="gradeForm" style="display: none;">
                    <h2>Assign New Grade</h2>
                    <form method="POST" action="">
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
                        
                        <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
                        
                        <div class="form-group">
                            <label for="assignment_name">Assignment Name</label>
                            <input type="text" id="assignment_name" name="assignment_name" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="grade">Grade</label>
                                <input type="number" id="grade" name="grade" step="0.01" min="0" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="max_grade">Maximum Grade</label>
                                <input type="number" id="max_grade" name="max_grade" step="0.01" min="0" value="100" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="comments">Comments (Optional)</label>
                            <textarea id="comments" name="comments" rows="3"></textarea>
                        </div>
                        
                        <div class="form-row">
                            <button type="submit" class="btn btn-primary">Assign Grade</button>
                            <button type="button" class="btn btn-secondary" onclick="hideForm()">Cancel</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
            
            <!-- Grades Table -->
            <?php if ($selected_course): ?>
                <div class="table-container">
                    <div class="table-header">
                        <h2>Grades for Selected Course</h2>
                    </div>
                    
                    <?php if (!empty($grades)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Assignment</th>
                                    <th>Grade</th>
                                    <th>Percentage</th>
                                    <th>Comments</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $grade): ?>
                                    <?php 
                                    $percentage = $grade['max_grade'] > 0 ? 
                                        round(($grade['grade'] / $grade['max_grade']) * 100, 2) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['assignment_name']); ?></td>
                                        <td><?php echo $grade['grade']; ?>/<?php echo $grade['max_grade']; ?></td>
                                        <td>
                                            <span class="grade-percentage <?php echo $percentage >= 90 ? 'excellent' : ($percentage >= 80 ? 'good' : ($percentage >= 70 ? 'average' : 'poor')); ?>">
                                                <?php echo $percentage; ?>%
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($grade['comments'] ?: '-'); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($grade['created_at'])); ?></td>
                                        <td>
                                            <a href="?course_id=<?php echo $selected_course; ?>&delete=<?php echo $grade['id']; ?>" 
                                               class="btn btn-secondary" 
                                               onclick="return confirm('Are you sure you want to delete this grade?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-error">No grades assigned for this course yet.</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <style>
        .grade-percentage {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .grade-percentage.excellent {
            background-color: #d4edda;
            color: #155724;
        }
        
        .grade-percentage.good {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .grade-percentage.average {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .grade-percentage.poor {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            font-family: inherit;
            resize: vertical;
        }
        
        textarea:focus {
            outline: none;
            border-color: #3498db;
        }
    </style>
    
    <script>
        function showAddForm() {
            document.getElementById('gradeForm').style.display = 'block';
            document.getElementById('gradeForm').scrollIntoView({ behavior: 'smooth' });
        }
        
        function hideForm() {
            document.getElementById('gradeForm').style.display = 'none';
            document.querySelector('#gradeForm form').reset();
        }
    </script>
</body>
</html> 