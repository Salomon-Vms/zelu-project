<?php
session_start();
require_once 'includes/db_connect.php';

$error = '';
$success = '';
$payments = [];

// Fetch payments from the database
$query = "SELECT p.*, s.first_name, s.last_name FROM fee_payments p JOIN students s ON p.student_id = s.id ORDER BY p.payment_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission for adding a new payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = intval($_POST['student_id']);
    $amount = floatval($_POST['amount']);
    $paymentDate = $_POST['payment_date'];
    $paymentMethod = $_POST['payment_method'];
    $referenceNumber = $_POST['reference_number'];
    $notes = $_POST['notes'];

    // Insert new payment into the database
    $query = "INSERT INTO fee_payments (student_id, amount, payment_date, payment_method, reference_number, notes, created_at, updated_at) 
              VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($query);
    if ($stmt->execute([$studentId, $amount, $paymentDate, $paymentMethod, $referenceNumber, $notes])) {
        $success = "Payment added successfully";
        // Refresh payments list
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = "Error adding payment: " . implode(", ", $stmt->errorInfo());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - School Management System</title>
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
                <h1>Payments</h1>
                <div class="page-actions">
                <a href="payment_form.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Payment
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
            
            <div class="table-container">
                <table class="table table-striped table-bordered" style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background-color: #f8f9fa; border-bottom: 2px solid #dee2e6;">
                    <tr>
                    <th style="padding: 10px; border: 1px solid #dee2e6;">Student Name</th>
                    <th style="padding: 10px; border: 1px solid #dee2e6;">Amount</th>
                    <th style="padding: 10px; border: 1px solid #dee2e6;">Payment Date</th>
                    <th style="padding: 10px; border: 1px solid #dee2e6;">Payment Method</th>
                    <th style="padding: 10px; border: 1px solid #dee2e6;">Reference Number</th>
                    <th style="padding: 10px; border: 1px solid #dee2e6;">Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment) { ?>
                    <tr style="border-bottom: 1px solid #dee2e6;">
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><?php echo $payment['first_name'] . ' ' . $payment['last_name']; ?></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">$<?php echo number_format($payment['amount'], 2); ?></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><?php echo date('Y-m-d', strtotime($payment['payment_date'])); ?></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><?php echo $payment['payment_method']; ?></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><?php echo $payment['reference_number']; ?></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><?php echo $payment['notes']; ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
                </table>
            </div>
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>