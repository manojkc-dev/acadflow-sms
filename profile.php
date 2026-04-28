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
$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = 'Please fill in all required fields';
    } else {
        // Check if email is already taken by another user
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Email is already taken by another user';
        } else {
            // Handle profile photo upload
            $profile_photo = null;
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['profile_photo']['type'], $allowed_types)) {
                    $error = 'Please upload a valid image file (JPEG, PNG, or GIF)';
                } elseif ($_FILES['profile_photo']['size'] > $max_size) {
                    $error = 'Image file size must be less than 5MB';
                } else {
                    // Create uploads directory if it doesn't exist
                    $upload_dir = 'uploads/profile_photos/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                    $filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                    $filepath = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $filepath)) {
                        $profile_photo = $filepath;
                    } else {
                        $error = 'Error uploading profile photo';
                    }
                }
            }
            
            if (empty($error)) {
                // If password change is requested
                if (!empty($current_password)) {
                    // Verify current password
                    $verify_sql = "SELECT password FROM users WHERE id = ?";
                    $verify_stmt = $conn->prepare($verify_sql);
                    $verify_stmt->bind_param("i", $user_id);
                    $verify_stmt->execute();
                    $verify_result = $verify_stmt->get_result();
                    $user_data = $verify_result->fetch_assoc();
                    
                    if (!password_verify($current_password, $user_data['password'])) {
                        $error = 'Current password is incorrect';
                    } elseif (empty($new_password)) {
                        $error = 'New password is required';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'New passwords do not match';
                    } elseif (strlen($new_password) < 6) {
                        $error = 'New password must be at least 6 characters long';
                    } else {
                        // Update with new password and photo
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        if ($profile_photo) {
                            $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, password = ?, profile_photo = ? WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("sssssi", $first_name, $last_name, $email, $hashed_password, $profile_photo, $user_id);
                        } else {
                            $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, password = ? WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $hashed_password, $user_id);
                        }
                    }
                } else {
                    // Update without password change
                    if ($profile_photo) {
                        $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, profile_photo = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssssi", $first_name, $last_name, $email, $profile_photo, $user_id);
                    } else {
                        $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sssi", $first_name, $last_name, $email, $user_id);
                    }
                }
                
                if (empty($error) && $stmt->execute()) {
                    $message = 'Profile updated successfully';
                    // Update session data
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['last_name'] = $last_name;
                    $_SESSION['email'] = $email;
                } else {
                    $error = 'Error updating profile';
                }
            }
        }
    }
}

// Get current user data
$sql = "SELECT id, first_name, last_name, email, role, profile_photo, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - Profile</title>
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
                    <li><a href="dashboard.php">Dashboard</a></li>
                    
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
                    
                    <li><a href="profile.php" class="active">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <h1>My Profile</h1>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Profile Information -->
            <div class="form-container">
                <h2>Profile Information</h2>
                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- Profile Photo Section -->
                    <div class="profile-photo-section">
                        <div class="current-photo">
                            <?php if ($user['profile_photo'] && file_exists($user['profile_photo'])): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Profile Photo" class="profile-photo">
                            <?php else: ?>
                                <div class="profile-photo-placeholder">
                                    <span><?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="photo-upload">
                            <label for="profile_photo">Update Profile Photo</label>
                            <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                            <small>Max size: 5MB. Supported formats: JPEG, PNG, GIF</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <input type="text" id="role" value="<?php echo ucfirst($user['role']); ?>" readonly style="background-color: #f8f9fa;">
                    </div>
                    
                    <div class="form-group">
                        <label for="created_at">Member Since</label>
                        <input type="text" id="created_at" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" readonly style="background-color: #f8f9fa;">
                    </div>
                    
                    <hr style="margin: 30px 0; border: none; border-top: 1px solid #e0e0e0;">
                    
                    <h3>Change Password (Optional)</h3>
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <div class="form-row">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
            
            <!-- Account Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Account Type</h3>
                    <p class="stat-number"><?php echo ucfirst($user['role']); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Member Since</h3>
                    <p class="stat-number"><?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Account Status</h3>
                    <p class="stat-number">Active</p>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .profile-photo-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
        }
        
        .current-photo {
            flex-shrink: 0;
        }
        
        .profile-photo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #3498db;
        }
        
        .profile-photo-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background-color: #3498db;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            border: 3px solid #3498db;
        }
        
        .photo-upload {
            flex: 1;
        }
        
        .photo-upload label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .photo-upload input[type="file"] {
            margin-bottom: 5px;
        }
        
        .photo-upload small {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .profile-photo-section {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
    
    <script src="assets/js/validation.js"></script>
</body>
</html> 