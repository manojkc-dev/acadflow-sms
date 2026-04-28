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

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = $_POST['course_id'];
    $date = $_POST['date'];
    $attendance_data = $_POST['attendance'];
    
    if (empty($course_id) || empty($date)) {
        $error = 'Please select course and date';
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Delete existing attendance for this course and date
            $delete_sql = "DELETE FROM attendance WHERE course_id = ? AND date = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("is", $course_id, $date);
            $delete_stmt->execute();
            
            // Insert new attendance records
            $insert_sql = "INSERT INTO attendance (student_id, course_id, date, status, marked_by) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            
            foreach ($attendance_data as $student_id => $status) {
                $insert_stmt->bind_param("iissi", $student_id, $course_id, $date, $status, $user_id);
                $insert_stmt->execute();
            }
            
            $conn->commit();
            $message = 'Attendance marked successfully';
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error marking attendance';
        }
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
$selected_date = date('Y-m-d');

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

// Get existing attendance for selected course and date
$existing_attendance = [];
if ($selected_course && isset($_GET['date'])) {
    $selected_date = $_GET['date'];
    $attendance_sql = "SELECT student_id, status FROM attendance WHERE course_id = ? AND date = ?";
    $attendance_stmt = $conn->prepare($attendance_sql);
    $attendance_stmt->bind_param("is", $selected_course, $selected_date);
    $attendance_stmt->execute();
    $result = $attendance_stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $existing_attendance[$row['student_id']] = $row['status'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - Attendance</title>
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
                    <li><a href="attendance.php" class="active">Attendance</a></li>
                    <li><a href="grades.php">Grades</a></li>
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>Mark Attendance</h1>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Course and Date Selection -->
            <div class="form-container">
                <h2>Select Course and Date</h2>
                <form method="GET" action="">
                    <div class="form-row">
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
                        
                        <div class="form-group">
                            <label for="date">Date</label>
                            <input type="date" id="date" name="date" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Attendance Form -->
            <?php if ($selected_course && !empty($students)): ?>
                <div class="form-container">
                    <h2>Mark Attendance for <?php echo date('F j, Y', strtotime($selected_date)); ?></h2>
                    <form method="POST" action="">
                        <input type="hidden" name="course_id" value="<?php echo $selected_course; ?>">
                        <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td>
                                            <select name="attendance[<?php echo $student['id']; ?>]" required>
                                                <option value="">Select</option>
                                                <option value="present" <?php echo (isset($existing_attendance[$student['id']]) && $existing_attendance[$student['id']] == 'present') ? 'selected' : ''; ?>>Present</option>
                                                <option value="absent" <?php echo (isset($existing_attendance[$student['id']]) && $existing_attendance[$student['id']] == 'absent') ? 'selected' : ''; ?>>Absent</option>
                                                <option value="late" <?php echo (isset($existing_attendance[$student['id']]) && $existing_attendance[$student['id']] == 'late') ? 'selected' : ''; ?>>Late</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <div class="form-row" style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary">Save Attendance</button>
                        </div>
                    </form>
                </div>
            <?php elseif ($selected_course && empty($students)): ?>
                <div class="alert alert-error">No students enrolled in this course.</div>
            <?php endif; ?>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="actions-grid">
                    <button class="action-card" onclick="markAllPresent()">
                        <h3>Mark All Present</h3>
                        <p>Quickly mark all students as present</p>
                    </button>
                    <button class="action-card" onclick="markAllAbsent()">
                        <h3>Mark All Absent</h3>
                        <p>Quickly mark all students as absent</p>
                    </button>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        function markAllPresent() {
            const selects = document.querySelectorAll('select[name^="attendance["]');
            selects.forEach(select => {
                select.value = 'present';
            });
        }
        
        function markAllAbsent() {
            const selects = document.querySelectorAll('select[name^="attendance["]');
            selects.forEach(select => {
                select.value = 'absent';
            });
        }
    </script>
</body>
</html> 