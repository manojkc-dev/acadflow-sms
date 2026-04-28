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

// Get student's grades with course information
$grades_sql = "SELECT g.*, c.course_code, c.course_name, u.first_name, u.last_name 
               FROM grades g 
               JOIN courses c ON g.course_id = c.id 
               JOIN users u ON g.assigned_by = u.id 
               WHERE g.student_id = ? 
               ORDER BY g.created_at DESC";
$grades_stmt = $conn->prepare($grades_sql);
$grades_stmt->bind_param("i", $user_id);
$grades_stmt->execute();
$grades = $grades_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_grades = count($grades);
$total_points = 0;
$max_points = 0;
$course_grades = [];

foreach ($grades as $grade) {
    $total_points += $grade['grade'];
    $max_points += $grade['max_grade'];
    
    $course_id = $grade['course_id'];
    if (!isset($course_grades[$course_id])) {
        $course_grades[$course_id] = [
            'course_name' => $grade['course_name'],
            'course_code' => $grade['course_code'],
            'grades' => [],
            'total_points' => 0,
            'max_points' => 0
        ];
    }
    
    $course_grades[$course_id]['grades'][] = $grade;
    $course_grades[$course_id]['total_points'] += $grade['grade'];
    $course_grades[$course_id]['max_points'] += $grade['max_grade'];
}

$overall_percentage = $max_points > 0 ? round(($total_points / $max_points) * 100, 2) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - My Grades</title>
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
                    <li><a href="courses.php">My Courses</a></li>
                    <li><a href="attendance.php">My Attendance</a></li>
                    <li><a href="grades.php" class="active">My Grades</a></li>
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>My Grades</h1>
            </header>
            
            <!-- Grade Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Assignments</h3>
                    <p class="stat-number"><?php echo $total_grades; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Overall Percentage</h3>
                    <p class="stat-number"><?php echo $overall_percentage; ?>%</p>
                </div>
                <div class="stat-card">
                    <h3>Total Points</h3>
                    <p class="stat-number"><?php echo $total_points; ?>/<?php echo $max_points; ?></p>
                </div>
            </div>
            
            <!-- Course-wise Grades -->
            <?php if (!empty($course_grades)): ?>
                <?php foreach ($course_grades as $course_id => $course_data): ?>
                    <?php 
                    $course_percentage = $course_data['max_points'] > 0 ? 
                        round(($course_data['total_points'] / $course_data['max_points']) * 100, 2) : 0;
                    ?>
                    <div class="table-container">
                        <div class="table-header">
                            <h2><?php echo htmlspecialchars($course_data['course_code'] . ' - ' . $course_data['course_name']); ?></h2>
                            <span class="course-percentage"><?php echo $course_percentage; ?>%</span>
                        </div>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>Assignment</th>
                                    <th>Grade</th>
                                    <th>Max Grade</th>
                                    <th>Percentage</th>
                                    <th>Assigned By</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($course_data['grades'] as $grade): ?>
                                    <?php 
                                    $grade_percentage = $grade['max_grade'] > 0 ? 
                                        round(($grade['grade'] / $grade['max_grade']) * 100, 2) : 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($grade['assignment_name']); ?></td>
                                        <td><?php echo $grade['grade']; ?></td>
                                        <td><?php echo $grade['max_grade']; ?></td>
                                        <td>
                                            <span class="grade-percentage <?php echo $grade_percentage >= 90 ? 'excellent' : ($grade_percentage >= 80 ? 'good' : ($grade_percentage >= 70 ? 'average' : 'poor')); ?>">
                                                <?php echo $grade_percentage; ?>%
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($grade['first_name'] . ' ' . $grade['last_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($grade['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert alert-error">No grades available yet.</div>
            <?php endif; ?>
            
            <!-- Recent Grades -->
            <?php if (!empty($grades)): ?>
                <div class="table-container">
                    <div class="table-header">
                        <h2>Recent Grades</h2>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Assignment</th>
                                <th>Grade</th>
                                <th>Percentage</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recent_grades = array_slice($grades, 0, 5); // Show only 5 most recent
                            foreach ($recent_grades as $grade): 
                                $grade_percentage = $grade['max_grade'] > 0 ? 
                                    round(($grade['grade'] / $grade['max_grade']) * 100, 2) : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($grade['course_code'] . ' - ' . $grade['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['assignment_name']); ?></td>
                                    <td><?php echo $grade['grade']; ?>/<?php echo $grade['max_grade']; ?></td>
                                    <td>
                                        <span class="grade-percentage <?php echo $grade_percentage >= 90 ? 'excellent' : ($grade_percentage >= 80 ? 'good' : ($grade_percentage >= 70 ? 'average' : 'poor')); ?>">
                                            <?php echo $grade_percentage; ?>%
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($grade['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <style>
        .course-percentage {
            background-color: #3498db;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
        }
        
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
    </style>
</body>
</html> 