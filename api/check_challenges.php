<?php
// api/check_challenges.php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/User.php';
require_once '../includes/Auth.php';

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Récupérer tous les défis du jour
$query = "SELECT * FROM daily_challenges 
          WHERE user_id = :user_id 
          AND challenge_date = :today 
          AND completed = FALSE";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->bindParam(":today", $today);
$stmt->execute();
$challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rewards_claimed = [];

foreach ($challenges as $challenge) {
    $should_complete = false;
    
    // Vérifier chaque type de défi
    if (strpos($challenge['challenge_title'], 'Regarder') !== false) {
        // Défi de vidéos : vérifier le nombre de vidéos regardées aujourd'hui
        $watchQuery = "SELECT COUNT(*) as count FROM watch_history 
                      WHERE user_id = :user_id 
                      AND DATE(watched_at) = :today";
        $watchStmt = $db->prepare($watchQuery);
        $watchStmt->bindParam(":user_id", $user_id);
        $watchStmt->bindParam(":today", $today);
        $watchStmt->execute();
        $watched = $watchStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($watched['count'] >= $challenge['target']) {
            $should_complete = true;
        }
    }
    elseif (strpos($challenge['challenge_title'], 'Partager') !== false) {
        // Défi de partage : vérifier si partagé aujourd'hui
        $shareQuery = "SELECT COUNT(*) as count FROM share_history 
                      WHERE user_id = :user_id 
                      AND DATE(shared_at) = :today";
        $shareStmt = $db->prepare($shareQuery);
        $shareStmt->bindParam(":user_id", $user_id);
        $shareStmt->bindParam(":today", $today);
        $shareStmt->execute();
        $shared = $shareStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($shared['count'] >= $challenge['target']) {
            $should_complete = true;
        }
    }
    elseif (strpos($challenge['challenge_title'], 'Atteindre') !== false) {
        // Défi de gains : vérifier les gains du jour
        $earningQuery = "SELECT COALESCE(SUM(reward_earned), 0) as total FROM watch_history 
                        WHERE user_id = :user_id 
                        AND DATE(watched_at) = :today";
        $earningStmt = $db->prepare($earningQuery);
        $earningStmt->bindParam(":user_id", $user_id);
        $earningStmt->bindParam(":today", $today);
        $earningStmt->execute();
        $earned = $earningStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($earned['total'] >= $challenge['target']) {
            $should_complete = true;
        }
    }
    
    // Si le défi est complété, créditer la récompense
    if ($should_complete) {
        // Marquer comme complété
        $completeQuery = "UPDATE daily_challenges 
                         SET completed = TRUE, 
                             progress = target,
                             completed_at = NOW()
                         WHERE id = :challenge_id";
        $completeStmt = $db->prepare($completeQuery);
        $completeStmt->bindParam(":challenge_id", $challenge['id']);
        $completeStmt->execute();
        
        // Créditer la récompense
        $userModel->updateBalance($user_id, $challenge['reward']);
        
        // Mettre à jour la session
        $_SESSION['balance'] += $challenge['reward'];
        
        $rewards_claimed[] = [
            'title' => $challenge['challenge_title'],
            'reward' => $challenge['reward']
        ];
    }
}

echo json_encode([
    'success' => true,
    'rewards_claimed' => $rewards_claimed,
    'new_balance' => $_SESSION['balance']
]);
?>