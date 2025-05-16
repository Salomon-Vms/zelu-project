<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-school"></i>
            <span>CS ZELU</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li>
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li>
                <a href="students.php", class="has-submenu <?php echo in_array(basename($_SERVER['PHP_SELF']), ['students.php', 'student_form.php']) ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
                <ul class="submenu">
            
            <li>
                <a href="payments.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'payments.php' || basename($_SERVER['PHP_SELF']) === 'payment_form.php' ? 'active' : ''; ?>">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>Payments</span>
                </a>
            </li>
            
            <li>
                <a href="sections.php" class="disabled <?php echo basename($_SERVER['PHP_SELF']) === 'sections.php' || basename($_SERVER['PHP_SELF']) === 'section_form.php' ? 'active' : ''; ?>">
                    <i class="fas fa-sitemap"></i>
                    <span>Sections</span>
                </a>
            </li>
            
            <li>
                <a href="classes.php" class="disabled <?php echo basename($_SERVER['PHP_SELF']) === 'classes.php' || basename($_SERVER['PHP_SELF']) === 'class_form.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard"></i>
                    <span>Classes</span>
                </a>
            </li>
            
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
            <li>
                <a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'users.php' || basename($_SERVER['PHP_SELF']) === 'user_form.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Users</span>
                </a>
            </li>
            <?php } ?>
            
            <li>
                <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="profile.php">
            <i class="fas fa-user-circle"></i>
            <span>Profile</span>
        </a>
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>