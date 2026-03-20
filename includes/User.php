<?php
// includes/User.php
class User {
    private $conn;
    private $table = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    // includes/User.php (modifié pour ajouter referred_by)

public function register($username, $email, $password, $phone, $referred_by = null) {
    $query = "INSERT INTO " . $this->table . "
            (username, email, password_hash, phone, referral_code, referred_by)
            VALUES (:username, :email, :password, :phone, :referral_code, :referred_by)";

    $stmt = $this->conn->prepare($query);

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $referral_code = "PUB" . strtoupper(substr(md5(uniqid()), 0, 8));

    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password", $password_hash);
    $stmt->bindParam(":phone", $phone);
    $stmt->bindParam(":referral_code", $referral_code);
    $stmt->bindParam(":referred_by", $referred_by);

    return $stmt->execute();
}

// Ajouter cette méthode pour créditer les commissions de parrainage
public function creditReferralCommission($new_user_id) {
    // Récupérer le parrain
    $query = "SELECT referred_by FROM users WHERE id = :id";
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":id", $new_user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && $user['referred_by']) {
        // Bonus de bienvenue pour le parrain (5000 FCFA)
        $bonus = 5000;
        $this->updateBalance($user['referred_by'], $bonus);
        
        // Envoyer une notification (à implémenter plus tard)
        return true;
    }
    return false;
}

    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $user['password_hash'])) {
                return $user;
            }
        }
        return false;
    }

    public function getUserById($id) {
        $query = "SELECT id, username, email, balance, total_earned, 
                         videos_watched, referral_code, created_at
                  FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateBalance($user_id, $amount) {
        $query = "UPDATE " . $this->table . "
                SET balance = balance + :amount,
                    total_earned = total_earned + :amount,
                    videos_watched = videos_watched + 1,
                    last_watch_date = NOW()
                WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":user_id", $user_id);
        return $stmt->execute();
    }
}
?>