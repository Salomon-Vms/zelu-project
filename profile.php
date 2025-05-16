<?php
// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once 'includes/db_connect.php';

try {
    // Fetch user data
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "Utilisateur introuvable.";
        exit();
    }
} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage();
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Utilisateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: #fff;
            font-family: 'Arial', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .profile-card {
            background: #fff;
            color: #333;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .profile-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }
        .profile-header {
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            padding: 20px;
            text-align: center;
        }
        .profile-header h2 {
            margin: 0;
            font-size: 1.8rem;
        }
        .profile-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #fff;
            margin-top: -60px;
        }
        .profile-body {
            padding: 20px;
            text-align: center;
        }
        .profile-body p {
            margin: 10px 0;
            font-size: 1rem;
        }
        .btn-custom {
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 1rem;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }
        .btn-custom:hover {
            transform: scale(1.05);
        }
        .btn-primary {
            background-color: #6a11cb;
            border: none;
        }
        .btn-primary:hover {
            background-color: #5a0fb7;
        }
        .btn-danger {
            background-color: #e63946;
            border: none;
        }
        .btn-danger:hover {
            background-color: #d62839;
        }
        .btn-home {
            background-color: #2a9d8f;
            border: none;
        }
        .btn-home:hover {
            background-color: #21867a;
        }
    </style>
</head>
<body>
    <div class="card profile-card" style="max-width: 500px;">
        <div class="profile-header">
            <h2>Profil de <?php echo htmlspecialchars($user['username']); ?></h2>
        </div>
        <div class="profile-body">
            <img src="<?php echo htmlspecialchars($user['profile_image'] ?? 'default-profile.png'); ?>" alt="Image de profil" class="profile-image">
            <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Date d'inscription :</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
            <p><strong>Bio :</strong> <?php echo htmlspecialchars($user['bio'] ?? 'Aucune bio disponible.'); ?></p>
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="edit_profile.php" class="btn btn-primary btn-custom">Modifier le profil</a>
                <a href="logout.php" class="btn btn-danger btn-custom">Se déconnecter</a>
            </div>
            <div class="mt-3">
                <a href="../index.php" class="btn btn-home btn-custom">Retour à l'accueil</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
