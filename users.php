<?php
session_start();
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: login.php');
//     exit;
// }

require_once 'includes/db_connect.php';

// Pagination setup
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Get total number of users
$countQuery = "SELECT COUNT(*) as total FROM users";
$countStmt = $conn->query($countQuery);
$totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get user list
$query = "SELECT id, username, role, email, last_login FROM users ORDER BY username LIMIT $offset, $recordsPerPage";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - School Management System</title>
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
                    <h1>Users</h1>
                    <div class="page-actions">
                        <a href="user_form.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add User
                        </a>
                    </div>
                </div>
                
                <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted') { ?>
                    <div class="alert alert-success">
                        User deleted successfully
                    </div>
                <?php } ?>
                
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Email</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($result && $result->rowCount() > 0) {
                                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                    // Don't allow editing or deleting the current user
                                    $isCurrentUser = $row['id'] == $_SESSION['user_id'];
                            ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['username']; ?></td>
                                    <td><span class="role-badge role-<?php echo strtolower($row['role']); ?>"><?php echo ucfirst($row['role']); ?></span></td>
                                    <td><?php echo $row['email']; ?></td>
                                    <td><?php echo $row['last_login'] ? date('M d, Y H:i', strtotime($row['last_login'])) : 'Never'; ?></td>
                                    <td class="actions">
                                        <a href="user_form.php?id=<?php echo $row['id']; ?>" class="btn-icon" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (!$isCurrentUser) { ?>
                                        <button type="button" class="btn-icon delete-btn" data-id="<?php echo $row['id']; ?>" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php 
                                }
                            } else {
                            ?>
                                <tr>
                                    <td colspan="6" class="no-records">No users found</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalPages > 1) { ?>
                <div class="pagination">
                    <?php if ($page > 1) { ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="pagination-link">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php } ?>
                    
                    <div class="pagination-pages">
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        if ($startPage > 1) {
                            echo '<a href="?page=1" class="pagination-link">1</a>';
                            if ($startPage > 2) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++) {
                            $activeClass = $i === $page ? 'active' : '';
                            echo "<a href='?page=$i' class='pagination-link $activeClass'>$i</a>";
                        }
                        
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<span class="pagination-ellipsis">...</span>';
                            }
                            echo "<a href='?page=$totalPages' class='pagination-link'>$totalPages</a>";
                        }
                        ?>
                    </div>
                    
                    <?php if ($page < $totalPages) { ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="pagination-link">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php } ?>
                </div>
                <?php } ?>
            </div>
        </main>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Confirm Delete</h2>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form id="deleteForm" method="post" action="delete_user.php">
                    <input type="hidden" id="deleteUserId" name="id" value="">
                    <button type="button" class="btn btn-text close-modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('deleteModal');
            const deleteForm = document.getElementById('deleteForm');
            const deleteUserIdInput = document.getElementById('deleteUserId');
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const closeButtons = document.querySelectorAll('.close-modal');
            
            // Show modal when delete button is clicked
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    deleteUserIdInput.value = userId;
                    modal.classList.add('active');
                });
            });
            
            // Close modal when close button is clicked
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    modal.classList.remove('active');
                });
            });
            
            // Close modal when clicking outside of it
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>