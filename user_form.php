<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$isEdit = $id > 0;
$user = null;
$error = '';
$success = '';

// If editing, get the user data
if ($isEdit) {
    $query = "SELECT id, username, email, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "User not found";
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validate username
    $usernameCheckQuery = "SELECT id FROM users WHERE username = ? AND id != ?";
    $stmt = $conn->prepare($usernameCheckQuery);
    $stmt->execute([$username, $id]);

    if ($stmt->rowCount() > 0) {
        $error = "Username already exists";
    } else {
        if ($isEdit) {
            // Update existing user
            if (!empty($password)) {
                // Update with new password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET username = ?, email = ?, role = ?, password = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$username, $email, $role, $hashedPassword, $id]);
            } else {
                // Update without changing password
                $query = "UPDATE users SET username = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$username, $email, $role, $id]);
            }

            if ($stmt->rowCount() > 0) {
                $success = "User updated successfully";
            } else {
                $error = "Error updating user";
            }
        } else {
            // Create new user
            if (empty($password)) {
                $error = "Password is required for new users";
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO users (username, email, role, password, created_at, updated_at) 
                          VALUES (?, ?, ?, ?, NOW(), NOW())";

                $stmt = $conn->prepare($query);
                if ($stmt->execute([$username, $email, $role, $hashedPassword])) {
                    $newId = $conn->lastInsertId();
                    header("Location: user_form.php?id=$newId&success=created");
                    exit;
                } else {
                    $error = "Error adding user";
            }
        }
    }
}
}
// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] === 'created') {
    $success = "User added successfully";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit User' : 'Add User'; ?> - School Management System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="page-content">
                <div class="page-header">
                    <h1><?php echo $isEdit ? 'Edit User' : 'Add New User'; ?></h1>
                    <div class="page-actions">
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Users
                        </a>
                    </div>
                </div>
                
                <?php if ($error) { ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php } ?>
                
                <?php if ($success) { ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php } ?>
                
                <div class="form-container">
                    <form method="post" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" value="<?php echo $user ? $user['username'] : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" value="<?php echo $user ? $user['email'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="role">Role *</label>
                                <select id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="admin" <?php echo $user && $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="staff" <?php echo $user && $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    <option value="teacher" <?php echo $user && $user['role'] === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="password"><?php echo $isEdit ? 'Password (leave blank to keep current)' : 'Password *'; ?></label>
                                <input type="password" id="password" name="password" <?php echo !$isEdit ? 'required' : ''; ?>>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $isEdit ? 'Update User' : 'Add User'; ?>
                            </button>
                            <a href="users.php" class="btn btn-text">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>