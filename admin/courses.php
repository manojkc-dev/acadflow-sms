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

// Handle course deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    $sql = "DELETE FROM courses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $message = 'Course deleted successfully';
    } else {
        $error = 'Error deleting course';
    }
}

// Handle course creation/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $description = trim($_POST['description']);
    $teacher_id = $_POST['teacher_id'];
    
    if (empty($course_code) || empty($course_name)) {
        $error = 'Please fill in all required fields';
    } else {
        if (isset($_POST['course_id']) && !empty($_POST['course_id'])) {
            // Update existing course
            $course_id = $_POST['course_id'];
            $sql = "UPDATE courses SET course_code = ?, course_name = ?, description = ?, teacher_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssii", $course_code, $course_name, $description, $teacher_id, $course_id);
        } else {
            // Create new course
            $sql = "INSERT INTO courses (course_code, course_name, description, teacher_id) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $course_code, $course_name, $description, $teacher_id);
        }
        
        if ($stmt->execute()) {
            $message = isset($_POST['course_id']) ? 'Course updated successfully' : 'Course created successfully';
        } else {
            $error = 'Error saving course';
        }
    }
}

// Get course for editing
$edit_course = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $sql = "SELECT id, course_code, course_name, description, teacher_id FROM courses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_course = $result->fetch_assoc();
}

// Get all teachers for dropdown
$teachers_sql = "SELECT id, first_name, last_name FROM users WHERE role = 'teacher' ORDER BY last_name, first_name";
$teachers_result = $conn->query($teachers_sql);
$teachers = $teachers_result->fetch_all(MYSQLI_ASSOC);

// Get all courses with teacher information
$sql = "SELECT c.*, u.first_name, u.last_name 
        FROM courses c 
        LEFT JOIN users u ON c.teacher_id = u.id 
        ORDER BY c.course_name";
$result = $conn->query($sql);
$courses = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - Manage Courses</title>
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
                    <li><a href="courses.php" class="active">Manage Courses</a></li>
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
                <h1>Manage Courses</h1>
                <button class="btn btn-primary" onclick="showAddForm()">Add New Course</button>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Add/Edit Course Form -->
            <div class="form-container" id="courseForm" style="display: <?php echo $edit_course ? 'block' : 'none'; ?>;">
                <h2><?php echo $edit_course ? 'Edit Course' : 'Add New Course'; ?></h2>
                <form method="POST" action="">
                    <?php if ($edit_course): ?>
                        <input type="hidden" name="course_id" value="<?php echo $edit_course['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="course_code">Course Code</label>
                            <input type="text" id="course_code" name="course_code" value="<?php echo $edit_course ? htmlspecialchars($edit_course['course_code']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="course_name">Course Name</label>
                            <input type="text" id="course_name" name="course_name" value="<?php echo $edit_course ? htmlspecialchars($edit_course['course_name']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="teacher_id">Assigned Teacher</label>
                        <select id="teacher_id" name="teacher_id">
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>" <?php echo ($edit_course && $edit_course['teacher_id'] == $teacher['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"><?php echo $edit_course ? htmlspecialchars($edit_course['description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_course ? 'Update Course' : 'Add Course'; ?></button>
                        <button type="button" class="btn btn-secondary" onclick="hideForm()">Cancel</button>
                    </div>
                </form>
            </div>
            
            <!-- Courses Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>All Courses</h2>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Description</th>
                            <th>Teacher</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['description'] ?: '-'); ?></td>
                                <td><?php echo $course['first_name'] ? htmlspecialchars($course['first_name'] . ' ' . $course['last_name']) : 'Not Assigned'; ?></td>
                                <td>
                                    <a href="?edit=<?php echo $course['id']; ?>" class="btn btn-secondary">Edit</a>
                                    <a href="?delete=<?php echo $course['id']; ?>" class="btn btn-secondary" onclick="return confirm('Are you sure you want to delete this course?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <style>
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
            document.getElementById('courseForm').style.display = 'block';
            document.getElementById('courseForm').scrollIntoView({ behavior: 'smooth' });
        }
        
        function hideForm() {
            document.getElementById('courseForm').style.display = 'none';
            // Reset form
            document.querySelector('#courseForm form').reset();
            // Remove edit mode
            window.location.href = 'courses.php';
        }
    </script>
</body>
</html> 