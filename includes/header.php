<?php
// Start the session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="header">
    <div class="header-left">
        <h2 class="page-title">
            <?php
            $pageName = basename($_SERVER['PHP_SELF'], '.php');
            switch ($pageName) {
                case 'index':
                    echo 'Dashboard';
                    break;
                case 'students':
                    echo 'Students';
                    break;
                case 'student_form':
                    echo isset($_GET['id']) ? 'Edit Student' : 'Add Student';
                    break;
                case 'payments':
                    echo 'Payments';
                    break;
                case 'payment_form':
                    echo isset($_GET['id']) ? 'Edit Payment' : 'Add Payment';
                    break;
                case 'users':
                    echo 'Users';
                    break;
                case 'user_form':
                    echo isset($_GET['id']) ? 'Edit User' : 'Add User';
                    break;
                case 'sections':
                    echo 'Sections';
                    break;
                case 'classes':
                    echo 'Classes';
                    break;
                case 'reports':
                    echo 'Reports';
                    break;
                case 'profile':
                    echo 'Profile';
                    break;
                default:
                    echo 'School Management System';
            }
            ?>
        </h2>
    </div>
    
    <div class="header-right">
        <div class="header-search">
            <form action="search.php" method="get">
                <div class="search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" placeholder="Search...">
                </div>
            </form>
        </div>
        
        <div class="header-actions">
            <div class="dropdown">
                <button class="dropdown-toggle user-btn">
                    <div class="user-avatar">
                        <i class="fas fa-user text-primary"></i>
                    </div>
                    <span class="user-name">
                        <?php 
                        if (isset($_SESSION['username']) && !empty($_SESSION['username'])) {
                            echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
                        } else {
                            echo 'Guest';
                        }
                        ?>
                    </span>
                </button>
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
                    </a>
                    <a href="settings.php" class="dropdown-item disabled">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>