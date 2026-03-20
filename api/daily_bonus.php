<?php
// api/daily_bonus.php
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

// Vérifier si déjà connecté aujourd'hui
$query = "SELECT last_login, login_streak FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$today = date('Y-m-d');
$last_login = date('Y-m-d', strtotime($user['last_login'] ?? '2000-01-01'));
$streak = $user['login_streak'] ?? 0;

if ($last_login == $today) {
    echo json_encode(['success' => false, 'message' => 'Bonus déjà réclamé aujourd\'hui']);
    exit();
}

// Calculer le streak
if ($last_login == date('Y-m-d', strtotime('-1 day'))) {
    $streak++;
} else {
    $streak = 1;
}

// Calculer le bonus (augmente avec le streak)
$bonus = 1000 * $streak; // 1000 FCFA par jour de streak
if ($streak >= 7) $bonus = 10000; // Bonus spécial pour 7 jours

// Mettre à jour
$updateQuery = "UPDATE users SET 
                balance = balance + :bonus,
                total_earned = total_earned + :bonus,
                last_login = NOW(),
                login_streak = :streak
                WHERE id = :user_id";
$updateStmt = $db->prepare($updateQuery);
$updateStmt->bindParam(":bonus", $bonus);
$updateStmt->bindParam(":streak", $streak);
$updateStmt->bindParam(":user_id", $user_id);

if ($updateStmt->execute()) {
    // Mettre à jour la session
    $_SESSION['balance'] += $bonus;
    
    echo json_encode([
        'success' => true,
        'bonus' => $bonus,
        'streak' => $streak,
        'message' => "🎉 Bonus de connexion: $bonus FCFA (Jour $streak)"
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors du crédit']);
}
?>