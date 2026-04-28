<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Database connection
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Admin-specific fields
    $admin_code = isset($_POST['admin_code']) ? trim($_POST['admin_code']) : '';
    $department = isset($_POST['department']) ? trim($_POST['department']) : '';
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif ($role === 'admin' && empty($admin_code)) {
        $error = 'Admin code is required for admin registration';
    } elseif ($role === 'admin' && $admin_code !== 'ADMIN2024') {
        $error = 'Invalid admin code';
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Email already exists';
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! You can now login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - Register</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-section {
            display: none;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
        
        .admin-section.show {
            display: block;
        }
        
        .admin-code-info {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="register-container">
            <div class="register-header">
                <h1>AcadFlow</h1>
                <p>Create New Account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="register-form" id="registerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="student">Student</option>
                        <option value="teacher">Teacher</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <!-- Admin Section -->
                <div class="admin-section" id="adminSection">
                    <h3>Admin Information</h3>
                    <div class="form-group">
                        <label for="admin_code">Admin Code</label>
                        <input type="password" id="admin_code" name="admin_code" placeholder="Enter admin code">
                        <div class="admin-code-info">Contact system administrator for admin code</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department (Optional)</label>
                        <input type="text" id="department" name="department" placeholder="e.g., IT, Administration">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
            
            <div class="register-footer">
                <p>Already have an account? <a href="index.php">Login here</a></p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/validation.js"></script>
    <script>
        // Show/hide admin section based on role selection
        document.getElementById('role').addEventListener('change', function() {
            const adminSection = document.getElementById('adminSection');
            const adminCodeInput = document.getElementById('admin_code');
            
            if (this.value === 'admin') {
                adminSection.classList.add('show');
                adminCodeInput.required = true;
            } else {
                adminSection.classList.remove('show');
                adminCodeInput.required = false;
            }
        });
    </script>
</body>
</html> 