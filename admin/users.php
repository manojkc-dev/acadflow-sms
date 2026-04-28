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

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    
    // Don't allow admin to delete themselves
    if ($delete_id == $_SESSION['user_id']) {
        $error = 'You cannot delete your own account';
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $delete_id);
        
        if ($stmt->execute()) {
            $message = 'User deleted successfully';
        } else {
            $error = 'Error deleting user';
        }
    }
}

// Handle user creation/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($role)) {
        $error = 'Please fill in all required fields';
    } else {
        if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
            // Update existing user
            $user_id = $_POST['user_id'];
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $first_name, $last_name, $email, $role, $hashed_password, $user_id);
            } else {
                $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $first_name, $last_name, $email, $role, $user_id);
            }
        } else {
            // Create new user
            if (empty($password)) {
                $error = 'Password is required for new users';
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $role);
            }
        }
        
        if (empty($error) && $stmt->execute()) {
            $message = isset($_POST['user_id']) ? 'User updated successfully' : 'User created successfully';
        } else {
            $error = 'Error saving user';
        }
    }
}

// Get user for editing
$edit_user = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $sql = "SELECT id, first_name, last_name, email, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_user = $result->fetch_assoc();
}

// Get all users
$sql = "SELECT id, first_name, last_name, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcadFlow - Manage Users</title>
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
                    <li><a href="users.php" class="active">Manage Users</a></li>
                    <li><a href="courses.php">Manage Courses</a></li>
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
                <h1>Manage Users</h1>
                <button class="btn btn-primary" onclick="showAddForm()">Add New User</button>
            </header>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <!-- Add/Edit User Form -->
            <div class="form-container" id="userForm" style="display: <?php echo $edit_user ? 'block' : 'none'; ?>;">
                <h2><?php echo $edit_user ? 'Edit User' : 'Add New User'; ?></h2>
                <form method="POST" action="">
                    <?php if ($edit_user): ?>
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo $edit_user ? htmlspecialchars($edit_user['first_name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo $edit_user ? htmlspecialchars($edit_user['last_name']) : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin" <?php echo ($edit_user && $edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="teacher" <?php echo ($edit_user && $edit_user['role'] == 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                            <option value="student" <?php echo ($edit_user && $edit_user['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password <?php echo $edit_user ? '(leave blank to keep current)' : ''; ?></label>
                        <input type="password" id="password" name="password" <?php echo $edit_user ? '' : 'required'; ?>>
                    </div>
                    
                    <div class="form-row">
                        <button type="submit" class="btn btn-primary"><?php echo $edit_user ? 'Update User' : 'Add User'; ?></button>
                        <button type="button" class="btn btn-secondary" onclick="hideForm()">Cancel</button>
                    </div>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="table-container">
                <div class="table-header">
                    <h2>All Users</h2>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $user['id']; ?>" class="btn btn-secondary">Edit</a>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-secondary" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        function showAddForm() {
            document.getElementById('userForm').style.display = 'block';
            document.getElementById('userForm').scrollIntoView({ behavior: 'smooth' });
        }
        
        function hideForm() {
            document.getElementById('userForm').style.display = 'none';
            // Reset form
            document.querySelector('#userForm form').reset();
            // Remove edit mode
            window.location.href = 'users.php';
        }
    </script>
</body>
</html> 