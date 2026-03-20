<?php
// api/watch.php
header('Content-Type: application/json');
session_start();

require_once '../config/database.php';
require_once '../includes/User.php';
require_once '../includes/Video.php';
require_once '../includes/Auth.php';

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$video_id = $data['video_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$video_id) {
    echo json_encode(['success' => false, 'error' => 'ID de vidéo manquant']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$videoModel = new Video($db);
$userModel = new User($db);

// Vérifier si la vidéo existe
$video = $videoModel->getVideoById($video_id);
if (!$video) {
    echo json_encode(['success' => false, 'error' => 'Vidéo non trouvée']);
    exit();
}

// Vérifier si déjà regardée
if ($videoModel->hasUserWatched($user_id, $video_id)) {
    echo json_encode(['success' => false, 'error' => 'Déjà regardée']);
    exit();
}

// Commencer la transaction
$db->beginTransaction();

try {
    // Ajouter l'historique
    $videoModel->addWatchHistory($user_id, $video_id, $video['reward_amount']);
    
    // Mettre à jour le solde
    $userModel->updateBalance($user_id, $video['reward_amount']);
    
    // Récupérer le nouveau solde
    $user = $userModel->getUserById($user_id);
    
    $db->commit();
    
    // Mettre à jour la session
    $_SESSION['balance'] = $user['balance'];
    
    echo json_encode([
        'success' => true,
        'reward' => $video['reward_amount'],
        'new_balance' => $user['balance']
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Erreur lors du traitement']);
}
?>