<?php
// api/notifications.php
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

// Récupérer les dernières notifications
$query = "SELECT * FROM notifications 
          WHERE user_id = :user_id 
          AND is_read = FALSE 
          ORDER BY created_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'count' => count($notifications)
]);
?>