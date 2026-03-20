<?php
// api/process_payment.php
session_start();
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/Auth.php';
require_once '../includes/PaymentAPI.php';

if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$payment = new PaymentAPI($db);

$user_id = $_SESSION['user_id'];
$withdrawal_id = $_POST['withdrawal_id'] ?? 0;
$phone = $_POST['phone'] ?? '';
$operator = $_POST['operator'] ?? '';

if (!$withdrawal_id || !$phone || !$operator) {
    echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    exit();
}

// Récupérer les infos du retrait
$query = "SELECT * FROM withdrawals WHERE id = :id AND user_id = :user_id AND status = 'PENDING'";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $withdrawal_id, ':user_id' => $user_id]);
$withdrawal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$withdrawal) {
    echo json_encode(['success' => false, 'error' => 'Retrait non trouvé']);
    exit();
}

// Générer un identifiant unique
$reference = 'PUB_' . time() . '_' . $withdrawal_id . '_' . rand(1000, 9999);

// Enregistrer la transaction
$transaction_id = $payment->saveTransaction(
    $user_id,
    $withdrawal_id,
    $withdrawal['amount'],
    $phone,
    $operator,
    $reference
);

// Traiter le paiement selon l'opérateur
$result = null;
switch ($operator) {
    case 'ORANGE_MONEY':
        $result = $payment->sendOrangeMoney($phone, $withdrawal['amount'], $reference);
        break;
    case 'MOOV_MONEY':
        $result = $payment->sendMoovMoney($phone, $withdrawal['amount'], $reference);
        break;
    case 'WAVE':
        $result = $payment->sendWave($phone, $withdrawal['amount'], $reference);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Opérateur non supporté']);
        exit();
}

if ($result['success']) {
    // Mettre à jour le statut de la transaction
    $payment->updateTransactionStatus($reference, 'PROCESSING');
    
    echo json_encode([
        'success' => true,
        'transaction_id' => $result['transaction_id'],
        'message' => $result['message']
    ]);
} else {
    // Marquer comme échouée
    $payment->updateTransactionStatus($reference, 'FAILED', null, $result['message']);
    
    echo json_encode([
        'success' => false,
        'error' => $result['message']
    ]);
}
?>