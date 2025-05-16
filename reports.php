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

$filterYear = isset($_GET['year']) ? $_GET['year'] : '';
if (!empty($filterYear)) {
    $whereClause .= " AND YEAR(s.registration_date) = " . intval($filterYear);
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
    <title>Reports - School Management System</title>
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
                    <h1>Reports</h1>
                </div>
       
            <div class="class-students-summary">
    <h2>Liste des élèves par classe et section</h2>
    <div class="accordion" id="accordion-classes">
        <?php
        // Récupérer toutes les classes avec leur section pour l'accordéon
        $classes = $conn->query(
            "SELECT c.id, c.name as class_name, se.name as section_name
            FROM classes c
            LEFT JOIN sections se ON c.section_id = se.id
            ORDER BY se.name, c.name"
        )->fetchAll(PDO::FETCH_ASSOC);

        $i = 0;
        foreach ($classes as $class) {
            $students = $conn->query("SELECT s.id, s.first_name, s.last_name 
                                    FROM students s 
                                    WHERE s.class_id = " . intval($class['id']) . " 
                                    ORDER BY s.first_name, s.last_name")->fetchAll(PDO::FETCH_ASSOC);
            if (count($students) > 0) {
                $collapseId = "collapse" . $i;
                echo "<div class='accordion-item'>";
                echo "<button class='accordion-header' type='button' data-toggle='collapse' data-target='#$collapseId' aria-expanded='false'>";
                echo "{$class['section_name']} - {$class['class_name']}";
                echo "</button>";
                echo "<div id='$collapseId' class='accordion-collapse'>";
                echo "<ul>";
                foreach ($students as $student) {
                    echo "<li>{$student['first_name']} {$student['last_name']}</li>";
                }
                echo "</ul>";
                // Bouton pour voir la liste complète sur une autre page
                echo "<a href='students_list.php?class_id={$class['id']}' class='btn btn-primary' style='margin-top:10px;display:inline-block;'>Voir la liste des élèves</a>";
                echo "</div>";
                echo "</div>";
                $i++;
            }
    
        }
?>
    </div>
</div>
        </main>
        
    </div>

    <script src="assets/js/main.js"></script>
        <script>
    document.querySelectorAll('.accordion-header').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const collapse = btn.nextElementSibling;
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            // Fermer tous les autres
            document.querySelectorAll('.accordion-header').forEach(function(otherBtn) {
                otherBtn.setAttribute('aria-expanded', 'false');
                if (otherBtn.nextElementSibling) otherBtn.nextElementSibling.style.display = 'none';
            });
            // Ouvrir/cacher celui-ci
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            collapse.style.display = expanded ? 'none' : 'block';
        });
    });
    </script>
</body>
</html>