<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff')) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connect.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$isEdit = $id > 0;
$student = null;
$error = '';
$success = '';

// Get all classes
$classesQuery = "SELECT c.id, c.name, s.name as section_name 
                FROM classes c
                JOIN sections s ON c.section_id = s.id
                ORDER BY s.name, c.name";
$classesStmt = $conn->prepare($classesQuery);
$classesStmt->execute();
$classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

// If editing, get the student data
if ($isEdit) {
    $query = "SELECT * FROM students WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        $error = "Student not found";
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $gender = $_POST['gender'];
    $dateOfBirth = $_POST['date_of_birth'];
    $classId = intval($_POST['class_id']);
    $annualFee = floatval($_POST['annual_fee']);
    $contactNumber = $_POST['contact_number'];
    $address = $_POST['address'];
    
    if ($isEdit) {
        // Update existing student
        $query = "UPDATE students SET 
                  first_name = :first_name, 
                  last_name = :last_name, 
                  gender = :gender, 
                  date_of_birth = :date_of_birth, 
                  class_id = :class_id, 
                  annual_fee = :annual_fee, 
                  contact_number = :contact_number, 
                  address = :address, 
                  updated_at = NOW() 
                  WHERE id = :id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':date_of_birth', $dateOfBirth);
        $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
        $stmt->bindParam(':annual_fee', $annualFee);
        $stmt->bindParam(':contact_number', $contactNumber);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $success = "Student updated successfully";
            
            // Reload student data
            $query = "SELECT * FROM students WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "Error updating student: " . $conn->errorInfo()[2];
        }
    } else {
        // Create new student
        $query = "INSERT INTO students (first_name, last_name, gender, date_of_birth, class_id, annual_fee, contact_number, address, registration_date, created_at, updated_at) 
                  VALUES (:first_name, :last_name, :gender, :date_of_birth, :class_id, :annual_fee, :contact_number, :address, NOW(), NOW(), NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':date_of_birth', $dateOfBirth);
        $stmt->bindParam(':class_id', $classId, PDO::PARAM_INT);
        $stmt->bindParam(':annual_fee', $annualFee);
        $stmt->bindParam(':contact_number', $contactNumber);
        $stmt->bindParam(':address', $address);
        
        if ($stmt->execute()) {
            $newId = $conn->lastInsertId();
            $success = "Student added successfully";
            
            // Redirect to the edit page
            header("Location: student_form.php?id=$newId&success=created");
            exit;
        } else {
            $error = "Error adding student: " . $conn->errorInfo()[2];
        }
    }
}

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] === 'created') {
    $success = "Student added successfully";
}

// Calculate payment summary
$paymentQuery = "SELECT SUM(amount) as total_paid FROM fee_payments WHERE student_id = :id";
$paymentStmt = $conn->prepare($paymentQuery);
$paymentStmt->bindParam(':id', $id, PDO::PARAM_INT);
$paymentStmt->execute();
$paymentSummary = $paymentStmt->fetch(PDO::FETCH_ASSOC);
$totalPaid = $paymentSummary['total_paid'] ?? 0;
$remainingBalance = $student ? max(0, $student['annual_fee'] - $totalPaid) : 0;

// Fetch payment records
$paymentsQuery = "SELECT * FROM fee_payments WHERE student_id = :id ORDER BY payment_date DESC";
$paymentsStmt = $conn->prepare($paymentsQuery);
$paymentsStmt->bindParam(':id', $id, PDO::PARAM_INT);
$paymentsStmt->execute();
$payments = $paymentsStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit Student' : 'Add Student'; ?> - School Management System</title>
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
                    <h1><?php echo $isEdit ? 'Edit Student' : 'Add New Student'; ?></h1>
                    <div class="page-actions">
                        <a href="students.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Students
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
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo $student ? $student['first_name'] : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo $student ? $student['last_name'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="gender">Gender *</label>
                                <select id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo $student && $student['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $student && $student['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth *</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $student ? $student['date_of_birth'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="class_id">Class *</label>
                                <select id="class_id" name="class_id" required>
                                    <option value="">Select Class</option>
                                    <?php foreach ($classes as $class) { ?>
                                        <option value="<?php echo $class['id']; ?>" 
                                                <?php echo $student && $student['class_id'] == $class['id'] ? 'selected' : ''; ?>>
                                            <?php echo $class['section_name'] . ' - ' . $class['name']; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="annual_fee">Annual Fee *</label>
                                <input type="number" step="0.01" id="annual_fee" name="annual_fee" value="<?php echo $student ? $student['annual_fee'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="contact_number">Contact Number</label>
                                <input type="text" id="contact_number" name="contact_number" value="<?php echo $student ? $student['contact_number'] : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="2"><?php echo $student ? $student['address'] : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <?php echo $isEdit ? 'Update Student' : 'Add Student'; ?>
                            </button>
                            <a href="students.php" class="btn btn-text">Cancel</a>
                        </div>
                    </form>
                </div>
                
                <?php if ($isEdit) { ?>
                <div class="section-divider"></div>
                
                <div class="related-section">
                    <h2>Payment History</h2>
                    
                    <div class="action-bar">
                        <a href="payment_form.php?student_id=<?php echo $id; ?>" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Add Payment
                        </a>
                    </div>
                    
    <div class="payment-summary">
        <div class="summary-card">
            <span class="label">Annual Fee:</span>
            <span class="value">$<?php echo number_format($student['annual_fee'], 2); ?></span>
        </div>
        <div class="summary-card">
            <span class="label">Total Paid:</span>
            <span class="value">$<?php echo number_format($totalPaid, 2); ?></span>
        </div>
        <div class="summary-card">
            <span class="label">Remaining Balance:</span>
            <span class="value">$<?php echo number_format($remainingBalance, 2); ?></span>
        </div>
        <div class="summary-card">
            <span class="label">Status:</span>
            <span class="value status <?php echo $remainingBalance > 0 ? 'status-warning' : 'status-success'; ?>">
                <?php echo $remainingBalance > 0 ? 'Pending' : 'Paid'; ?>
            </span>
        </div>
    </div>

<div class="data-table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Amount</th>
                <th>Payment Date</th>
                <th>Payment Method</th>
                <th>Reference</th>
                <th>Notes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (count($payments) > 0) {
                foreach ($payments as $payment) {
            ?>
                <tr>
                    <td><?php echo $payment['id']; ?></td>
                    <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                    <td><?php echo $payment['payment_method']; ?></td>
                    <td><?php echo $payment['reference_number']; ?></td>
                    <td><?php echo $payment['notes']; ?></td>
                    <td class="actions">
                        <a href="payment_form.php?id=<?php echo $payment['id']; ?>" class="btn-icon" title="Edit Payment">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
            <?php 
                }
            } else {
            ?>
                <tr>
                    <td colspan="7" class="no-records">No payment records found</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php } ?>
            </div>
        </div>
    </main>
    