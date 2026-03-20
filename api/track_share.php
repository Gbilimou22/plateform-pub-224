<?php
// api/track_share.php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/Auth.php';

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$platform = $_POST['platform'] ?? 'whatsapp';

// Enregistrer le partage
$query = "INSERT INTO share_history (user_id, platform) VALUES (:user_id, :platform)";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->bindParam(":platform", $platform);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>