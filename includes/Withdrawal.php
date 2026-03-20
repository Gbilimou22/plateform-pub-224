<?php
// includes/Withdrawal.php
class Withdrawal {
    private $conn;
    private $table = "withdrawals";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($user_id, $amount, $payment_method, $phone_number) {
        $query = "INSERT INTO " . $this->table . "
                (user_id, amount, payment_method, phone_number, status)
                VALUES (:user_id, :amount, :payment_method, :phone_number, 'PENDING')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":payment_method", $payment_method);
        $stmt->bindParam(":phone_number", $phone_number);
        return $stmt->execute();
    }

    public function getUserWithdrawals($user_id) {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE user_id = :user_id
                  ORDER BY requested_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>