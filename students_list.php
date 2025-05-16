<?php
// filepath: c:\xampp\htdocs\zelu-project\students_list.php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

require_once 'includes/db_connect.php';
$whereClause = "1=1";
// Récupérer l'ID de la classe depuis l'URL
$classId = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if ($classId <= 0) {
    echo "<p>Classe non spécifiée.</p>";
    exit;
}

// Récupérer les infos de la classe et de la section
$classInfo = $conn->query(
    "SELECT c.name as class_name, se.name as section_name
     FROM classes c
     LEFT JOIN sections se ON c.section_id = se.id
     WHERE c.id = $classId"
)->fetch(PDO::FETCH_ASSOC);

if (!$classInfo) {
    echo "<p>Classe introuvable.</p>";
    exit;
}

// Récupérer les élèves de la classe
$students = $conn->query(
    "SELECT id, first_name, last_name, gender, date_of_birth, registration_date
     FROM students
     WHERE class_id = $classId
     ORDER BY first_name, last_name"
)->fetchAll(PDO::FETCH_ASSOC);

$students = $conn->query(
    "SELECT id, first_name, last_name, gender, date_of_birth, registration_date
     FROM students
     WHERE class_id = $classId
     ORDER BY first_name, last_name"
)->fetchAll(PDO::FETCH_ASSOC);

// Export CSV si demandé
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=eleves_classe_' . $classId . '.csv');
    $output = fopen('php://output', 'w');
    // En-têtes
    fputcsv($output, ['Prénom', 'Nom', 'Sexe', 'Date de naissance', "Date d'inscription"]);
    // Données
    foreach ($students as $student) {
        fputcsv($output, [
            $student['first_name'],
            $student['last_name'],
            $student['gender'],
            $student['date_of_birth'],
            $student['registration_date']
        ]);
    }
    fclose($output);
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    require_once 'vendor/autoload.php'; // Inclure TCPDF si installé via Composer
    require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';
    // Modifier la requête pour inclure tous les résultats
    $exportQuery = "SELECT s.first_name, s.last_name, s.gender
                    FROM students s
                    WHERE s.class_id = $classId
                    ORDER BY s.first_name, s.last_name";
    $exportResult = $conn->query($exportQuery)->fetchAll(PDO::FETCH_ASSOC);

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
                        <th>#</th>
                        <th>Prénom</th>
                        <th>Nom</th>
                        <th>Sexe</th>
                    </tr>
                </thead>
                <tbody>';
    foreach ($exportResult as $idx => $row) {
        $html .= '<tr>
                    <td>' . ($idx + 1) . '</td>
                    <td>' . htmlspecialchars($row['first_name']) . '</td>
                    <td>' . htmlspecialchars($row['last_name']) . '</td>
                    <td>' . htmlspecialchars($row['gender']) . '</td>
                  </tr>';
    }
    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    // Télécharger le fichier PDF
    $pdf->Output('students_export.pdf', 'D');
    exit;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Liste des élèves - <?php echo htmlspecialchars($classInfo['class_name']); ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content mt-3">
            <?php include 'includes/header.php'; ?>
            
            <h2>
                Liste des élèves de la classe 
                <?php echo htmlspecialchars($classInfo['section_name'] . ' - ' . $classInfo['class_name']); ?>
            </h2>
            <a href="reports.php" class="btn btn-secondary" style="margin-bottom:15px;">&#8592; Retour</a>
            <?php if (count($students) > 0): ?>
            <div class="filter-container">
                <div class="export-actions">
                    <a href="students_list.php?class_id=<?php echo $classId; ?>&export=csv" class="btn btn-outline" style="margin-bottom:15px;">
                        <i class="fa fa-download"></i> Exporter en CSV
                    </a>
                 </div>
                 <div class="export-actions">
                    <a href="students_list.php?class_id=<?php echo $classId; ?>&export=pdf" class="btn btn-outline" style="margin-bottom:15px;">
                        <i class="fa fa-file-pdf"></i> Exporter en PDF
                    </a>
                 </div>
            </div>
            <div class="data-table-container">
                <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Prénom</th>
                        <th>Nom</th>
                        <th>Sexe</th>
                        <th>Date de naissance</th>
                        <th>Date d'inscription</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $idx => $student): ?>
                    <tr>
                        <td><?php echo $idx + 1; ?></td>
                        <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['gender']); ?></td>
                        <td><?php echo htmlspecialchars($student['date_of_birth']); ?></td>
                        <td><?php echo htmlspecialchars($student['registration_date']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <p>Aucun élève trouvé pour cette classe.</p>
            <?php endif; ?>
            </div>
            
        </main>
    </div>
</body>
</html>