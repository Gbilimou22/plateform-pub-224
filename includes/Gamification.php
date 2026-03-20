<?php
// includes/Gamification.php
class Gamification {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Calculer le niveau basé sur les vidéos regardées
    public function getUserLevel($videos_watched) {
        if ($videos_watched < 10) {
            return [
                'level' => 1,
                'title' => 'Débutant',
                'icon' => '🌱',
                'next_level' => 10,
                'progress' => ($videos_watched / 10) * 100
            ];
        } elseif ($videos_watched < 50) {
            return [
                'level' => 2,
                'title' => 'Regulier',
                'icon' => '🌟',
                'next_level' => 50,
                'progress' => (($videos_watched - 10) / 40) * 100
            ];
        } elseif ($videos_watched < 100) {
            return [
                'level' => 3,
                'title' => 'Expert',
                'icon' => '💎',
                'next_level' => 100,
                'progress' => (($videos_watched - 50) / 50) * 100
            ];
        } elseif ($videos_watched < 500) {
            return [
                'level' => 4,
                'title' => 'Pro',
                'icon' => '👑',
                'next_level' => 500,
                'progress' => (($videos_watched - 100) / 400) * 100
            ];
        } else {
            return [
                'level' => 5,
                'title' => 'Légende',
                'icon' => '🏆',
                'next_level' => null,
                'progress' => 100
            ];
        }
    }
    
    // Obtenir les badges de l'utilisateur
    public function getUserBadges($user_id) {
        $badges = [];
        
        // Récupérer les stats de l'utilisateur
        $query = "SELECT videos_watched, total_earned, created_at FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Badge première vidéo
        if ($user['videos_watched'] >= 1) {
            $badges[] = [
                'name' => 'Premier pas',
                'icon' => '🎬',
                'description' => 'Première vidéo regardée',
                'date' => $user['created_at']
            ];
        }
        
        // Badge 10 vidéos
        if ($user['videos_watched'] >= 10) {
            $badges[] = [
                'name' => 'Spectateur',
                'icon' => '👀',
                'description' => '10 vidéos regardées',
                'date' => null
            ];
        }
        
        // Badge 50 vidéos
        if ($user['videos_watched'] >= 50) {
            $badges[] = [
                'name' => 'Accro',
                'icon' => '🔥',
                'description' => '50 vidéos regardées',
                'date' => null
            ];
        }
        
        // Badge 100 vidéos
        if ($user['videos_watched'] >= 100) {
            $badges[] = [
                'name' => 'Cinéphile',
                'icon' => '🎥',
                'description' => '100 vidéos regardées',
                'date' => null
            ];
        }
        
        // Badge 1M FCFA
        if ($user['total_earned'] >= 1000000) {
            $badges[] = [
                'name' => 'Millionnaire',
                'icon' => '💵',
                'description' => '1 000 000 FCFA gagnés',
                'date' => null
            ];
        }
        
        return $badges;
    }
}
?>