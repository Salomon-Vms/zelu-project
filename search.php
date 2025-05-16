<?php
// filepath: c:\xampp\htdocs\zelu-project\search.php
session_start();
require_once 'includes/db_connect.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$students = [];
if ($q !== '') {
    // Recherche sur prénom ou nom (ajuste selon ta structure)
    $stmt = $conn->prepare("SELECT id, first_name, last_name, gender, date_of_birth FROM students WHERE first_name LIKE :q OR last_name LIKE :q ORDER BY first_name, last_name");
    $stmt->execute(['q' => "%$q%"]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats de la recherche</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container">
    <h2>Résultats de la recherche pour "<?php echo htmlspecialchars($q); ?>"</h2>
    <?php if ($q === ''): ?>
        <p>Veuillez saisir un terme de recherche.</p>
    <?php elseif (count($students) === 0): ?>
        <p>Aucun élève trouvé.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Prénom</th>
                    <th>Nom</th>
                    <th>Sexe</th>
                    <th>Date de naissance</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($students as $student): ?>
                <tr>
                    <td><?php echo htmlspecialchars($student['first_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($student['gender']); ?></td>
                    <td><?php echo htmlspecialchars($student['date_of_birth']); ?></td>
                    <td>
                        <a href="student_view.php?id=<?php echo $student['id']; ?>" class="btn btn-mini">Voir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>