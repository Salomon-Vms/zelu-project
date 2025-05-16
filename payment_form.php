<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$isEdit = $id > 0;
$payment = null;
$student = null;
$error = '';
$success = '';

// If editing, get the payment data
if ($isEdit) {
    $query = "SELECT p.*, s.first_name, s.last_name, s.annual_fee 
              FROM fee_payments p
              JOIN students s ON p.student_id = s.id
              WHERE p.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $payment = $result;
        $studentId = $payment['student_id'];
    } else {
        $error = "Payment not found";
    }
} else if ($studentId > 0) {
    // Get student information for new payment
    $query = "SELECT * FROM students WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$studentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $student = $result;
        
        // Get total paid amount
        $query = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM fee_payments WHERE student_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$studentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalPaid = $row['total_paid'];
        
        // Calculate remaining balance
        $student['remaining_balance'] = max(0, $student['annual_fee'] - $totalPaid);
    } else {
        $error = "Student not found";
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = intval($_POST['student_id']);
    $amount = floatval($_POST['amount']);
    $paymentDate = $_POST['payment_date'];
    $paymentMethod = $_POST['payment_method'];
    $referenceNumber = $_POST['reference_number'];
    $notes = $_POST['notes'];
    
    if ($isEdit) {
        // Update existing payment
        $query = "UPDATE fee_payments SET 
                  amount = ?, 
                  payment_date = ?, 
                  payment_method = ?, 
                  reference_number = ?, 
                  notes = ?, 
                  updated_at = NOW() 
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        if ($stmt->execute([$amount, $paymentDate, $paymentMethod, $referenceNumber, $notes, $id])) {
            $success = "Payment updated successfully";
            
            // Reload payment data
            $query = "SELECT p.*, s.first_name, s.last_name, s.annual_fee 
                    FROM fee_payments p
                    JOIN students s ON p.student_id = s.id
                    WHERE p.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$id]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Error updating payment: " . implode(", ", $stmt->errorInfo());
        }
    } else {
        // Create new payment
        $query = "INSERT INTO fee_payments (student_id, amount, payment_date, payment_method, reference_number, notes, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($query);
        if ($stmt->execute([$studentId, $amount, $paymentDate, $paymentMethod, $referenceNumber, $notes])) {
            $newId = $conn->lastInsertId();
            $success = "Payment added successfully";
            
            // Redirect to the student page
            header("Location: student_form.php?id=$studentId&payment_success=1");
            exit;
        } else {
            $error = "Error adding payment: " . implode(", ", $stmt->errorInfo());
        }
    }
}

// Get all students for dropdown (if needed)
$studentsQuery = "SELECT id, first_name, last_name FROM students ORDER BY first_name, last_name";
$studentsResult = $conn->query($studentsQuery);
$students = $studentsResult->fetchAll(PDO::FETCH_ASSOC);
$students = [];
while ($row = $studentsResult->fetch(PDO::FETCH_ASSOC)) {
    $students[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit Payment' : 'Add Payment'; ?> - School Management System</title>
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
                    <h1><?php echo $isEdit ? 'Edit Payment' : 'Add New Payment'; ?></h1>
                    <div class="page-actions">
                        <?php if ($studentId) { ?>
                            <a href="student_form.php?id=<?php echo $studentId; ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Student
                            </a>
                        <?php } else { ?>
                            <a href="payments.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Payments
                            </a>
                        <?php } ?>
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
                
                <?php if ($student || $payment) { ?>
                <div class="student-info-card">
                    <div class="student-details">
                        <h3>Student Information</h3>
                        <div class="student-name">
                            <?php 
                            if ($payment) {
                                echo $payment['first_name'] . ' ' . $payment['last_name'];
                            } elseif ($student) {
                                echo $student['first_name'] . ' ' . $student['last_name'];
                            }
                            ?>
                        </div>
                        <div class="fee-details">
                            <div class="fee-item">
                                <span class="label">Annual Fee:</span>
                                <span class="value">$<?php echo number_format($payment ? $payment['annual_fee'] : $student['annual_fee'], 2); ?></span>
                            </div>
                            <?php if ($student && isset($student['remaining_balance'])) { ?>
                            <div class="fee-item">
                                <span class="label">Remaining Balance:</span>
                                <span class="value">$<?php echo number_format($student['remaining_balance'], 2); ?></span>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <?php } ?>
                
                <div class="form-container">
                    <form method="post" action="">
                        <?php if (!$studentId) { ?>
                        <div class="form-group">
                            <label for="student_id">Student *</label>
                            <select id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student) { ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo $student['first_name'] . ' ' . $student['last_name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <?php } else { ?>
                            <input type="hidden" name="student_id" value="<?php echo $studentId; ?>">
                        <?php } ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="amount">Amount *</label>
                                <input type="number" step="0.01" id="amount" name="amount" value="<?php echo $payment ? $payment['amount'] : ($student && isset($student['remaining_balance']) ? $student['remaining_balance'] : ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_date">Payment Date *</label>
                                <input type="date" id="payment_date" name="payment_date" value="<?php echo $payment ? $payment['payment_date'] : date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="payment_method">Payment Method *</label>
                                <select id="payment_method" name="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="Cash" <?php echo $payment && $payment['payment_method'] === 'Cash' ? 'selected' : ''; ?>>Cash</option>
                                    <option value="Check" <?php echo $payment && $payment['payment_method'] === 'Check' ? 'selected' : ''; ?>>Check</option>
                                    <option value="Bank Transfer" <?php echo $payment && $payment['payment_method'] === 'Bank Transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                    <option value="Credit Card" <?php echo $payment && $payment['payment_method'] === 'Credit Card' ? 'selected' : ''; ?>>Credit Card</option>
                                    <option value="Other" <?php echo $payment && $payment['payment_method'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="reference_number">Reference Number</label>
                                <input type="text" id="reference_number" name="reference_number" value="<?php echo $payment ? $payment['reference_number'] : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes</label>
                            <textarea id="notes" name="notes" rows="2"><?php echo $payment ? $payment['notes'] : ''; ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $isEdit ? 'Update Payment' : 'Add Payment'; ?>
                            </button>
                            <?php if ($studentId) { ?>
                                <a href="student_form.php?id=<?php echo $studentId; ?>" class="btn btn-text">Cancel</a>
                            <?php } else { ?>
                                <a href="payments.php" class="btn btn-text">Cancel</a>
                            <?php } ?>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>