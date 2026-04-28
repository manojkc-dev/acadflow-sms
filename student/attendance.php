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

// Get student's attendance records with course information
$attendance_sql = "SELECT a.*, c.course_code, c.course_name, u.first_name, u.last_name
                   FROM attendance a
                   JOIN courses c ON a.course_id = c.id
                   JOIN users u ON a.marked_by = u.id
                   WHERE a.student_id = ?
                   ORDER BY a.date DESC, c.course_name";
$attendance_stmt = $conn->prepare($attendance_sql);
$attendance_stmt->bind_param("i", $user_id);
$attendance_stmt->execute();
$attendance_records = $attendance_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate attendance statistics
$total_records = count($attendance_records);
$present_count = 0;
$absent_count = 0;
$late_count = 0;

foreach ($attendance_records as $record) {
    switch ($record['status']) {
        case 'present':
            $present_count++;
            break;
        case 'absent':
            $absent_count++;
            break;
        case 'late':
            $late_count++;
            break;
    }
}

$attendance_percentage = $total_records > 0 ? round(($present_count / $total_records) * 100, 1) : 0;

// Get attendance by course
$course_attendance_sql = "SELECT c.course_code, c.course_name,
                          COUNT(*) as total_records,
                          SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                          SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                          SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count
                          FROM attendance a
                          JOIN courses c ON a.course_id = c.id
                          WHERE a.student_id = ?
                          GROUP BY c.id
                          ORDER BY c.course_name";
$course_attendance_stmt = $conn->prepare($course_attendance_sql);
$course_attendance_stmt->bind_param("i", $user_id);
$course_attendance_stmt->execute();
$course_attendance = $course_attendance_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - My Attendance</title>
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
                    <li><a href="attendance.php" class="active">My Attendance</a></li>
                    <li><a href="grades.php">My Grades</a></li>
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>My Attendance</h1>
            </header>
            
            <!-- Attendance Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Records</h3>
                    <p class="stat-number"><?php echo $total_records; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Present</h3>
                    <p class="stat-number"><?php echo $present_count; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Absent</h3>
                    <p class="stat-number"><?php echo $absent_count; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Late</h3>
                    <p class="stat-number"><?php echo $late_count; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Attendance Rate</h3>
                    <p class="stat-number"><?php echo $attendance_percentage; ?>%</p>
                </div>
            </div>
            
            <!-- Attendance by Course -->
            <?php if (!empty($course_attendance)): ?>
                <div class="table-container">
                    <div class="table-header">
                        <h2>Attendance by Course</h2>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Total Records</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                                <th>Attendance Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($course_attendance as $course): ?>
                                <?php 
                                $course_rate = $course['total_records'] > 0 ? 
                                    round(($course['present_count'] / $course['total_records']) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?></td>
                                    <td><?php echo $course['total_records']; ?></td>
                                    <td><span class="attendance-badge present"><?php echo $course['present_count']; ?></span></td>
                                    <td><span class="attendance-badge absent"><?php echo $course['absent_count']; ?></span></td>
                                    <td><span class="attendance-badge late"><?php echo $course['late_count']; ?></span></td>
                                    <td>
                                        <span class="attendance-rate <?php echo $course_rate >= 90 ? 'excellent' : ($course_rate >= 80 ? 'good' : ($course_rate >= 70 ? 'average' : 'poor')); ?>">
                                            <?php echo $course_rate; ?>%
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Recent Attendance Records -->
            <?php if (!empty($attendance_records)): ?>
                <div class="table-container">
                    <div class="table-header">
                        <h2>Recent Attendance Records</h2>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Marked By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $recent_records = array_slice($attendance_records, 0, 10); // Show only 10 most recent
                            foreach ($recent_records as $record): 
                            ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($record['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($record['course_code'] . ' - ' . $record['course_name']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $record['status']; ?>">
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-error">No attendance records found.</div>
            <?php endif; ?>
        </main>
    </div>
    
    <style>
        .attendance-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
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
        
        .attendance-rate {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .attendance-rate.excellent {
            background-color: #d4edda;
            color: #155724;
        }
        
        .attendance-rate.good {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .attendance-rate.average {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .attendance-rate.poor {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-badge.status-present {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-badge.status-absent {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .status-badge.status-late {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</body>
</html> 