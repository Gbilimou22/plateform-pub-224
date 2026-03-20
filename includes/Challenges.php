<?php
// includes/Challenges.php (version améliorée)
class Challenges {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function getDailyChallenges($user_id) {
        $today = date('Y-m-d');
        
        // Supprimer les anciens défis
        $cleanQuery = "DELETE FROM daily_challenges 
                      WHERE user_id = :user_id 
                      AND challenge_date < :today";
        $cleanStmt = $this->conn->prepare($cleanQuery);
        $cleanStmt->bindParam(":user_id", $user_id);
        $cleanStmt->bindParam(":today", $today);
        $cleanStmt->execute();
        
        // Récupérer les défis du jour
        $query = "SELECT * FROM daily_challenges 
                  WHERE user_id = :user_id 
                  AND challenge_date = :today";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":today", $today);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Récupérer les stats du jour pour la progression
        $watchQuery = "SELECT COUNT(*) as watched, COALESCE(SUM(reward_earned), 0) as earned 
                      FROM watch_history 
                      WHERE user_id = :user_id 
                      AND DATE(watched_at) = :today";
        $watchStmt = $this->conn->prepare($watchQuery);
        $watchStmt->bindParam(":user_id", $user_id);
        $watchStmt->bindParam(":today", $today);
        $watchStmt->execute();
        $stats = $watchStmt->fetch(PDO::FETCH_ASSOC);
        
        $shareQuery = "SELECT COUNT(*) as shared FROM share_history 
                      WHERE user_id = :user_id 
                      AND DATE(shared_at) = :today";
        $shareStmt = $this->conn->prepare($shareQuery);
        $shareStmt->bindParam(":user_id", $user_id);
        $shareStmt->bindParam(":today", $today);
        $shareStmt->execute();
        $shareStats = $shareStmt->fetch(PDO::FETCH_ASSOC);
        
        // Créer les défis du jour avec progression initiale
        $challenges = [
            [
                'title' => 'Regarder 3 vidéos',
                'description' => 'Regardez 3 vidéos aujourd\'hui',
                'target' => 3,
                'progress' => $stats['watched'],
                'reward' => 5000,
                'icon' => '🎬'
            ],
            [
                'title' => 'Partager sur WhatsApp',
                'description' => 'Partagez votre lien de parrainage',
                'target' => 1,
                'progress' => $shareStats['shared'],
                'reward' => 3000,
                'icon' => '📱'
            ],
            [
                'title' => 'Atteindre 200 000 FCFA',
                'description' => 'Gagnez 200 000 FCFA aujourd\'hui',
                'target' => 200000,
                'progress' => $stats['earned'],
                'reward' => 10000,
                'icon' => '💰'
            ]
        ];
        
        // Insérer les défis
        foreach ($challenges as $challenge) {
            $completed = ($challenge['progress'] >= $challenge['target']) ? 1 : 0;
            
            $insert = "INSERT INTO daily_challenges 
                      (user_id, challenge_title, challenge_description, target, progress, reward, icon, challenge_date, completed)
                      VALUES (:user_id, :title, :description, :target, :progress, :reward, :icon, :today, :completed)";
            $stmt = $this->conn->prepare($insert);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":title", $challenge['title']);
            $stmt->bindParam(":description", $challenge['description']);
            $stmt->bindParam(":target", $challenge['target']);
            $stmt->bindParam(":progress", $challenge['progress']);
            $stmt->bindParam(":reward", $challenge['reward']);
            $stmt->bindParam(":icon", $challenge['icon']);
            $stmt->bindParam(":today", $today);
            $stmt->bindParam(":completed", $completed);
            $stmt->execute();
        }
        
        return $challenges;
    }
}
?>