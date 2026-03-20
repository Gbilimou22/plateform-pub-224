<?php
// includes/Admin.php
class Admin {
    private $conn;
    private $table = "admins";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE username = :username OR email = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            if(password_verify($password, $admin['password_hash'])) {
                // Mettre à jour dernière connexion
                $update = "UPDATE admins SET last_login = NOW() WHERE id = :id";
                $updateStmt = $this->conn->prepare($update);
                $updateStmt->bindParam(":id", $admin['id']);
                $updateStmt->execute();
                
                return $admin;
            }
        }
        return false;
    }

    public function getDashboardStats() {
        $stats = [];
        
        // Nombre d'utilisateurs
        $query = "SELECT COUNT(*) as total FROM users";
        $stmt = $this->conn->query($query);
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Nouveaux utilisateurs aujourd'hui
        $query = "SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->conn->query($query);
        $stats['new_users_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Utilisateurs actifs aujourd'hui
        $query = "SELECT COUNT(DISTINCT user_id) as total FROM watch_history WHERE DATE(watched_at) = CURDATE()";
        $stmt = $this->conn->query($query);
        $stats['active_users_today'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total des gains
        $query = "SELECT COALESCE(SUM(reward_earned), 0) as total FROM watch_history";
        $stmt = $this->conn->query($query);
        $stats['total_earnings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Gains aujourd'hui
        $query = "SELECT COALESCE(SUM(reward_earned), 0) as total FROM watch_history WHERE DATE(watched_at) = CURDATE()";
        $stmt = $this->conn->query($query);
        $stats['today_earnings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total des vidéos regardées
        $query = "SELECT COUNT(*) as total FROM watch_history";
        $stmt = $this->conn->query($query);
        $stats['total_watches'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Vidéos regardées aujourd'hui
        $query = "SELECT COUNT(*) as total FROM watch_history WHERE DATE(watched_at) = CURDATE()";
        $stmt = $this->conn->query($query);
        $stats['today_watches'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Retraits en attente
        $query = "SELECT COUNT(*) as total, COALESCE(SUM(amount), 0) as total_amount FROM withdrawals WHERE status = 'PENDING'";
        $stmt = $this->conn->query($query);
        $pending = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['pending_withdrawals'] = $pending['total'];
        $stats['pending_amount'] = $pending['total_amount'];
        
        // Total payé
        $query = "SELECT COALESCE(SUM(amount), 0) as total FROM withdrawals WHERE status = 'COMPLETED'";
        $stmt = $this->conn->query($query);
        $stats['total_paid'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    }

    public function getRecentUsers($limit = 10) {
        $query = "SELECT id, username, email, balance, total_earned, videos_watched, created_at 
                  FROM users 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentWithdrawals($limit = 10) {
        $query = "SELECT w.*, u.username 
                  FROM withdrawals w
                  JOIN users u ON w.user_id = u.id
                  ORDER BY w.requested_at DESC 
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>