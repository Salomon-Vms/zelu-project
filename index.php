<?php
// Redirect to login page if not already logged in
session_start();
require_once 'includes/db_connect.php'; // Assurez-vous que db_connect.php initialise une connexion PDO

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="dashboard">
                <h1>Dashboard</h1>
                
                <div class="dashboard-stats">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Students</h3>
                            <?php
                            $query = "SELECT COUNT(*) as total FROM students";
                            $stmt = $conn->query($query);
                            $data = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo '<p class="stat-number">' . $data['total'] . '</p>';
                            ?>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Fees Collected</h3>
                            <?php
                                $query = "SELECT SUM(amount) as total FROM fee_payments";
                                $stmt = $conn->query($query);
                                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                                $total = $data['total'] ? $data['total'] : 0;
                                echo '<p class="stat-number">$' . number_format($total, 2) . '</p>';
                            ?>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Pending Payments</h3>
                            <?php
                            $query = "SELECT COUNT(DISTINCT student_id) as total FROM students s 
                                      LEFT JOIN fee_payments p ON s.id = p.student_id 
                                      WHERE p.amount < s.annual_fee OR p.id IS NULL";
                            $stmt = $conn->query($query);
                            $data = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo '<p class="stat-number">' . $data['total'] . '</p>';
                            ?>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-school"></i>
                        </div>
                        <div class="stat-details">
                            <h3>Total Sections</h3>
                            <?php
                            $query = "SELECT COUNT(*) as total FROM sections";
                            $stmt = $conn->query($query);
                            $data = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo '<p class="stat-number">' . $data['total'] . '</p>';
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-recent">
                    <div class="dashboard-card recent-students">
                        <div class="card-header">
                            <h2>Recent Students</h2>
                            <a href="students.php" class="view-all">View All</a>
                        </div>
                        <div class="card-content">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Section</th>
                                        <th>Class</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT s.id, s.first_name, s.last_name, s.annual_fee, 
                                              c.name as class_name, se.name as section_name,
                                              COALESCE(SUM(p.amount), 0) as paid_amount
                                              FROM students s
                                              LEFT JOIN classes c ON s.class_id = c.id
                                              LEFT JOIN sections se ON c.section_id = se.id
                                              LEFT JOIN fee_payments p ON s.id = p.student_id
                                              GROUP BY s.id
                                              ORDER BY s.id DESC LIMIT 5";
                                    $stmt = $conn->query($query);
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        $status = $row['paid_amount'] >= $row['annual_fee'] ? 'Paid' : 'Pending';
                                        $statusClass = $status === 'Paid' ? 'status-success' : 'status-warning';
                                        echo '<tr>
                                            <td>' . $row['first_name'] . ' ' . $row['last_name'] . '</td>
                                            <td>' . $row['section_name'] . '</td>
                                            <td>' . $row['class_name'] . '</td>
                                            <td><span class="status ' . $statusClass . '">' . $status . '</span></td>
                                        </tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="dashboard-card recent-payments">
                        <div class="card-header">
                            <h2>Recent Payments</h2>
                            <a href="payments.php" class="view-all">View All</a>
                        </div>
                        <div class="card-content">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT p.amount, p.payment_date, s.first_name, s.last_name
                                              FROM fee_payments p
                                              JOIN students s ON p.student_id = s.id
                                              ORDER BY p.payment_date DESC LIMIT 5";
                                    $stmt = $conn->query($query);
                                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<tr>
                                            <td>' . $row['first_name'] . ' ' . $row['last_name'] . '</td>
                                            <td>$' . number_format($row['amount'], 2) . '</td>
                                            <td>' . date('M d, Y', strtotime($row['payment_date'])) . '</td>
                                        </tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
