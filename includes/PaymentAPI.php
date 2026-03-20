<?php
// includes/PaymentAPI.php
require_once 'config/payment_api.php';

class PaymentAPI {
    private $db;
    private $log_file = '../logs/payment.log';
    
    public function __construct($db) {
        $this->db = $db;
        $this->initLog();
    }
    
    private function initLog() {
        $log_dir = dirname($this->log_file);
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
    }
    
    private function log($message, $data = null) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message";
        if ($data) {
            $log_entry .= "\n" . print_r($data, true);
        }
        file_put_contents($this->log_file, $log_entry . "\n", FILE_APPEND);
    }
    
    private function logApiCall($endpoint, $request, $response, $code) {
        $query = "INSERT INTO api_logs (endpoint, request_method, request_data, response_data, response_code, ip_address) 
                  VALUES (:endpoint, 'POST', :request, :response, :code, :ip)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':endpoint' => $endpoint,
            ':request' => json_encode($request),
            ':response' => json_encode($response),
            ':code' => $code,
            ':ip' => $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    // Envoyer de l'argent via Orange Money
    public function sendOrangeMoney($phone, $amount, $reference) {
        $this->log("Orange Money payment initiated", [
            'phone' => $phone,
            'amount' => $amount,
            'reference' => $reference
        ]);
        
        if (PAYMENT_TEST_MODE) {
            return $this->testPayment($phone, $amount, 'ORANGE');
        }
        
        try {
            // 1. Obtenir le token d'accès
            $token = $this->getOrangeMoneyToken();
            
            if (!$token) {
                throw new Exception("Impossible d'obtenir le token Orange Money");
            }
            
            // 2. Préparer la transaction
            $payment_data = [
                'merchant_key' => ORANGE_MONEY_MERCHANT_KEY,
                'currency' => 'XOF',
                'order_id' => $reference,
                'amount' => $amount,
                'return_url' => 'https://tonsite.com/payment_callback.php',
                'cancel_url' => 'https://tonsite.com/payment_cancel.php',
                'notif_url' => 'https://tonsite.com/api/payment_notification.php',
                'lang' => 'fr'
            ];
            
            // 3. Envoyer la requête
            $ch = curl_init(ORANGE_MONEY_API_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            $this->logApiCall('orange_money_payment', $payment_data, $result, $http_code);
            
            if ($http_code == 200 && isset($result['pay_token'])) {
                return [
                    'success' => true,
                    'transaction_id' => $result['pay_token'],
                    'payment_url' => $result['payment_url'],
                    'message' => 'Transaction initiée avec succès'
                ];
            } else {
                throw new Exception($result['description'] ?? 'Erreur Orange Money');
            }
            
        } catch (Exception $e) {
            $this->log("Orange Money error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Envoyer de l'argent via Moov Money (MTN)
    public function sendMoovMoney($phone, $amount, $reference) {
        $this->log("Moov Money payment initiated", [
            'phone' => $phone,
            'amount' => $amount,
            'reference' => $reference
        ]);
        
        if (PAYMENT_TEST_MODE) {
            return $this->testPayment($phone, $amount, 'MOOV');
        }
        
        try {
            // 1. Obtenir le token MTN
            $token = $this->getMtnToken();
            
            // 2. Préparer la transaction
            $payment_data = [
                'amount' => $amount,
                'currency' => 'XOF',
                'externalId' => $reference,
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $phone
                ],
                'payerMessage' => 'Paiement PubWatch Pro',
                'payeeNote' => 'Retrait de gains'
            ];
            
            // 3. Envoyer la requête
            $ch = curl_init(MOOV_MONEY_API_URL . 'requesttopay');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'X-Reference-Id: ' . $reference,
                'Ocp-Apim-Subscription-Key: ' . MOOV_MONEY_API_KEY
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $this->logApiCall('moov_money_payment', $payment_data, ['http_code' => $http_code], $http_code);
            
            if ($http_code == 202) {
                // Transaction acceptée, vérifier le statut plus tard
                return [
                    'success' => true,
                    'transaction_id' => $reference,
                    'message' => 'Transaction en cours de traitement'
                ];
            } else {
                throw new Exception('Erreur Moov Money: Code ' . $http_code);
            }
            
        } catch (Exception $e) {
            $this->log("Moov Money error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Envoyer de l'argent via Wave
    public function sendWave($phone, $amount, $reference) {
        $this->log("Wave payment initiated", [
            'phone' => $phone,
            'amount' => $amount,
            'reference' => $reference
        ]);
        
        if (PAYMENT_TEST_MODE) {
            return $this->testPayment($phone, $amount, 'WAVE');
        }
        
        try {
            $payment_data = [
                'amount' => $amount,
                'currency' => 'XOF',
                'recipient' => $phone,
                'reference' => $reference,
                'description' => 'Retrait PubWatch Pro'
            ];
            
            $ch = curl_init(WAVE_API_URL . 'transfers');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . WAVE_API_TOKEN,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            $this->logApiCall('wave_payment', $payment_data, $result, $http_code);
            
            if ($http_code == 200 || $http_code == 201) {
                return [
                    'success' => true,
                    'transaction_id' => $result['id'] ?? $reference,
                    'message' => 'Transaction Wave réussie'
                ];
            } else {
                throw new Exception($result['message'] ?? 'Erreur Wave');
            }
            
        } catch (Exception $e) {
            $this->log("Wave error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    // Obtenir le token Orange Money
    private function getOrangeMoneyToken() {
        if (PAYMENT_TEST_MODE) {
            return 'test_token_' . time();
        }
        
        $ch = curl_init('https://api.orange.com/oauth/v2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode(ORANGE_MONEY_CLIENT_ID . ':' . ORANGE_MONEY_CLIENT_SECRET),
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    // Obtenir le token MTN
    private function getMtnToken() {
        if (PAYMENT_TEST_MODE) {
            return 'test_token_' . time();
        }
        
        // Logique d'obtention du token MTN
        // À implémenter selon la documentation MTN
        return null;
    }
    
    // Mode test pour développement
    private function testPayment($phone, $amount, $operator) {
        $this->log("TEST MODE: Payment simulated", [
            'operator' => $operator,
            'phone' => $phone,
            'amount' => $amount
        ]);
        
        return [
            'success' => true,
            'transaction_id' => 'TEST_' . time() . '_' . rand(1000, 9999),
            'message' => "[TEST] Transaction simulée avec succès pour $operator"
        ];
    }
    
    // Vérifier le statut d'une transaction
    public function checkTransactionStatus($transaction_id, $operator) {
        $query = "SELECT * FROM transactions WHERE transaction_id = :tid";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':tid' => $transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            return ['success' => false, 'message' => 'Transaction non trouvée'];
        }
        
        if (PAYMENT_TEST_MODE) {
            return [
                'success' => true,
                'status' => 'SUCCESS',
                'message' => 'Transaction réussie (mode test)'
            ];
        }
        
        // Logique de vérification selon l'opérateur
        // À implémenter avec les API correspondantes
        
        return $transaction;
    }
    
    // Enregistrer une transaction
    public function saveTransaction($user_id, $withdrawal_id, $amount, $phone, $operator, $transaction_id) {
        $query = "INSERT INTO transactions 
                  (user_id, withdrawal_id, amount, phone_number, operator, transaction_id, status)
                  VALUES 
                  (:user_id, :withdrawal_id, :amount, :phone, :operator, :tid, 'PENDING')";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':withdrawal_id' => $withdrawal_id,
            ':amount' => $amount,
            ':phone' => $phone,
            ':operator' => $operator,
            ':tid' => $transaction_id
        ]);
        
        return $this->db->lastInsertId();
    }
    
    // Mettre à jour le statut d'une transaction
    public function updateTransactionStatus($transaction_id, $status, $response_code = null, $response_message = null) {
        $query = "UPDATE transactions 
                  SET status = :status, 
                      response_code = :code, 
                      response_message = :message,
                      processed_at = NOW()
                  WHERE transaction_id = :tid";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':status' => $status,
            ':code' => $response_code,
            ':message' => $response_message,
            ':tid' => $transaction_id
        ]);
    }
}
?>