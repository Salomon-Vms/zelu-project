<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header('Location: users.php');
    exit;
}

require_once 'includes/db_connect.php';

$id = intval($_POST['id']);

// Don't allow deleting the current user
if ($id === $_SESSION['user_id']) {
    header('Location: users.php?error=self_delete');
    exit;
}

// Delete the user
$query = "DELETE FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bindParam(1, $id, PDO::PARAM_INT);

if ($stmt->execute()) {
    header('Location: users.php?success=deleted');
} else {
    header('Location: users.php?error=delete_failed');
}
?>