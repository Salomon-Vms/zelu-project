<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connect.php';

// Handle form submission for filtering
$whereClause = "1=1";
$filterSection = isset($_GET['section']) ? $_GET['section'] : '';
$filterClass = isset($_GET['class']) ? $_GET['class'] : '';
$filterStatus = isset($_GET['payment_status']) ? $_GET['payment_status'] : '';

if (!empty($filterSection)) {
    $whereClause .= " AND se.id = " . intval($filterSection);
}

if (!empty($filterClass)) {
    $whereClause .= " AND c.id = " . intval($filterClass);
}

// Get the list of sections and classes for the filter
$sectionsQuery = "SELECT id, name FROM sections ORDER BY name";
$sectionsResult = $conn->query($sectionsQuery);

$classesQuery = "SELECT id, name FROM classes ORDER BY name";
$classesResult = $conn->query($classesQuery);

// Pagination setup
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Recuperer le nombre total des eleves
$countQuery = "SELECT COUNT(*) as total FROM students s
               LEFT JOIN classes c ON s.class_id = c.id
               LEFT JOIN sections se ON c.section_id = se.id
               WHERE $whereClause";
$countResult = $conn->query($countQuery)->fetch(PDO::FETCH_ASSOC);
$totalRecords = $countResult['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Get student list
$query = "SELECT s.id, s.first_name, s.last_name, s.gender, s.date_of_birth, 
                 s.annual_fee, s.contact_number, s.address, s.registration_date, 
                 c.name as class_name, se.name as section_name,
                 COALESCE(SUM(p.amount), 0) as paid_amount
          FROM students s
          LEFT JOIN classes c ON s.class_id = c.id
          LEFT JOIN sections se ON c.section_id = se.id
          LEFT JOIN fee_payments p ON s.id = p.student_id
          WHERE $whereClause
          GROUP BY s.id
          ORDER BY s.first_name, s.last_name
          LIMIT $offset, $recordsPerPage";
$result = $conn->query($query)->fetchAll(PDO::FETCH_ASSOC);


if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    require_once 'vendor/autoload.php'; // Inclure TCPDF si installé via Composer
    require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';
    // Modifier la requête pour inclure tous les résultats
    $exportQuery = "SELECT s.id, s.first_name, s.last_name, s.gender, s.date_of_birth, 
                           s.annual_fee, s.contact_number, s.address, s.registration_date, 
                           c.name as class_name, se.name as section_name,
                           COALESCE(SUM(p.amount), 0) as paid_amount
                    FROM students s
                    LEFT JOIN classes c ON s.class_id = c.id
                    LEFT JOIN sections se ON c.section_id = se.id
                    LEFT JOIN fee_payments p ON s.id = p.student_id
                    WHERE $whereClause
                    GROUP BY s.id
                    ORDER BY s.first_name, s.last_name";
    $exportResult = $conn->query($exportQuery);

    // Créer une instance de TCPDF
    $pdf = new \TCPDF();
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Complexe Scolaire Zelu');
    $pdf->SetTitle('Liste des étudiants');
    $pdf->SetHeaderData('', 0, 'Liste des élèves', 'Exporté le ' . date('d/m/Y'));
    $pdf->setHeaderFont(['helvetica', '', 10]);
    $pdf->setFooterFont(['helvetica', '', 8]);
    $pdf->SetMargins(15, 27, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 25);
    $pdf->AddPage();

    // Ajouter le contenu
    $html = '<h1>Liste des élèves</h1>';
    $html .= '<table border="1" cellpadding="5">
                <thead>
                    <tr>
                        <th colspan="7">Informations sur l\'étudiant</th>
                    </tr>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Section</th>
                        <th>Classe</th>
                        <th>Frais annuels</th>
                        <th>Montant payé</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($exportResult as $row) {
        $status = $row['paid_amount'] >= $row['annual_fee'] ? 'Payé' : 'En attente';
        $html .= '<tr>
                    <td>' . $row['id'] . '</td>
                    <td>' . $row['first_name'] . ' ' . $row['last_name'] . '</td>
                    <td>' . $row['section_name'] . '</td>
                    <td>' . $row['class_name'] . '</td>
                    <td>$' . number_format($row['annual_fee'], 2) . '</td>
                    <td>$' . number_format($row['paid_amount'], 2) . '</td>
                    <td>' . $status . '</td>
                  </tr>';
    }
    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Télécharger le fichier PDF
    $pdf->Output('students_export.pdf', 'D');
    exit;
}


// Export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Modify the query to get all results for export
    $exportQuery = "SELECT s.id, s.first_name, s.last_name, s.gender, s.date_of_birth, 
                           s.annual_fee, s.contact_number, s.address, s.registration_date, 
                           c.name as class_name, se.name as section_name,
                           COALESCE(SUM(p.amount), 0) as paid_amount
                    FROM students s
                    LEFT JOIN classes c ON s.class_id = c.id
                    LEFT JOIN sections se ON c.section_id = se.id
                    LEFT JOIN fee_payments p ON s.id = p.student_id
                    WHERE $whereClause
                    GROUP BY s.id
                    ORDER BY s.first_name, s.last_name";
    $exportResult = $conn->query($exportQuery);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_export.csv"');
    
    // Create a file handle for PHP output
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['ID', 'First Name', 'Last Name', 'Gender', 'Date of Birth', 'Section', 'Class', 'Annual Fee', 'Paid Amount', 'Payment Status', 'Contact Number', 'Address', 'Registration Date']);
    
    // Add data rows
    foreach ($exportResult as $row) {
        $status = $row['paid_amount'] >= $row['annual_fee'] ? 'Paid' : 'Pending';
        fputcsv($output, [
            $row['id'],
            $row['first_name'],
            $row['last_name'],
            $row['gender'],
            $row['date_of_birth'],
            $row['section_name'],
            $row['class_name'],
            $row['annual_fee'],
            $row['paid_amount'],
            $status,
            $row['contact_number'],
            $row['address'],
            $row['registration_date']
        ]);
    }
    
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - School Management System</title>
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
                    <h1>Students</h1>
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff') { ?>
                    <div class="page-actions">
                        <a href="student_form.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Student
                        </a>
                    </div>
                    <?php } ?>
                </div>
                
                <div class="filter-container">
                    <form method="get" class="filter-form">
                        <div class="form-group">
                            <label for="section">Section</label>
                            <select id="section" name="section">
                                <option value="">All Sections</option>
                                <?php foreach ($sectionsResult as $section) { ?>
                                    <option value="<?php echo $section['id']; ?>" <?php echo $filterSection == $section['id'] ? 'selected' : ''; ?>>
                                        <?php echo $section['name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="class">Class</label>
                            <select id="class" name="class">
                                <option value="">All Classes</option>
                                <?php foreach ($classesResult as $class) { ?>
                                    <option value="<?php echo $class['id']; ?>" <?php echo $filterClass == $class['id'] ? 'selected' : ''; ?>>
                                        <?php echo $class['name']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="payment_status">Payment Status</label>
                            <select id="payment_status" name="payment_status">
                                <option value="">All Status</option>
                                <option value="paid" <?php echo $filterStatus === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-secondary">Apply Filters</button>
                            <a href="students.php" class="btn btn-text">Reset</a>
                        </div>
                    </form>
                        <div class="export-actions">
                            <a href="students.php?export=pdf<?php echo !empty($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : ''; ?>" class="btn btn-outline">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </a>
                        </div>
                    
                    <div class="export-actions">
                        <a href="students.php?export=csv<?php echo !empty($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : ''; ?>" class="btn btn-outline">
                            <i class="fas fa-download"></i> Export CSV
                        </a>
                    </div>
                </div>
                
                <div class="data-table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Section</th>
                                <th>Class</th>
                                <th>Annual Fee</th>
                                <th>Paid Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if (count($result) > 0) {
                                foreach ($result as $row) {
                                    $status = $row['paid_amount'] >= $row['annual_fee'] ? 'Paid' : 'Pending';
                                    $statusClass = $status === 'Paid' ? 'status-success' : 'status-warning';
                            ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
                                    <td><?php echo $row['section_name']; ?></td>
                                    <td><?php echo $row['class_name']; ?></td>
                                    <td>$<?php echo number_format($row['annual_fee'], 2); ?></td>
                                    <td>$<?php echo number_format($row['paid_amount'], 2); ?></td>
                                    <td><span class="status <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                    <td class="actions">
                                        <a href="student_view.php?id=<?php echo $row['id']; ?>" class="btn-icon text-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'staff') { ?>
                                        <a href="student_form.php?id=<?php echo $row['id']; ?>" class="btn-icon text-success" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="payment_form.php?student_id=<?php echo $row['id']; ?>" class="btn-icon text-info" title="Add Payment">
                                            <i class="fas fa-money-bill"></i>
                                        </a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php 
                                }
                            } else {
                            ?>
                                <tr>
                                    <td colspan="8" class="no-records">No students found</td>
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

    <script src="assets/js/main.js"></script>
</body>
</html>