<?php
// api/payment_notification.php
// Ce fichier reçoit les notifications des opérateurs
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../includes/PaymentAPI.php';

$database = new Database();
$db = $database->getConnection();
$payment = new PaymentAPI($db);

// Lire les données POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Si pas de JSON, essayer les données POST normales
    $input = $_POST;
}

// Log de la notification
$payment->log("Notification reçue", $input);

// Traiter selon le type de notification
if (isset($input['transaction_id'])) {
    $transaction_id = $input['transaction_id'];
    $status = $input['status'] ?? 'SUCCESS';
    
    if ($status == 'SUCCESS') {
        // Mettre à jour la transaction
        $payment->updateTransactionStatus($transaction_id, 'SUCCESS');
        
        // Récupérer l'ID du retrait
        $query = "SELECT withdrawal_id FROM transactions WHERE transaction_id = :tid";
        $stmt = $db->prepare($query);
        $stmt->execute([':tid' => $transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            // Marquer le retrait comme complété
            $update = "UPDATE withdrawals SET status = 'COMPLETED', processed_at = NOW() WHERE id = :id";
            $upd = $db->prepare($update);
            $upd->execute([':id' => $transaction['withdrawal_id']]);
        }
    } else {
        $payment->updateTransactionStatus($transaction_id, 'FAILED');
    }
}

echo json_encode(['success' => true]);
?>