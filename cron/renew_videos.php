<?php
// cron/renew_videos.php
// Ce script doit être exécuté toutes les heures via cron

require_once '../config/database.php';

class VideoRenewal {
    private $db;
    private $log_file = '../logs/video_renewal.log';
    
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
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->log_file, "[$timestamp] $message\n", FILE_APPEND);
    }
    
    public function renewVideos() {
        $this->log("=== DÉBUT DU RENOUVELLEMENT AUTOMATIQUE ===");
        
        // 1. Désactiver les vidéos expirées
        $this->deactivateExpiredVideos();
        
        // 2. Renouveler les vidéos automatiques
        $this->renewAutoVideos();
        
        // 3. Activer les nouvelles vidéos programmées
        $this->activateScheduledVideos();
        
        // 4. Nettoyer les anciennes vidéos temporaires
        $this->cleanTemporaryVideos();
        
        $this->log("=== FIN DU RENOUVELLEMENT ===\n");
    }
    
    private function deactivateExpiredVideos() {
        $query = "UPDATE videos 
                  SET is_active = 0 
                  WHERE end_date < CURDATE() 
                  AND is_active = 1";
        $count = $this->db->exec($query);
        $this->log("✅ Vidéos expirées désactivées : $count");
    }
    
    private function renewAutoVideos() {
        // Trouver les vidéos à renouveler
        $query = "SELECT * FROM videos 
                  WHERE auto_renew = 1 
                  AND is_active = 1
                  AND (last_renewed IS NULL 
                       OR last_renewed < DATE_SUB(NOW(), INTERVAL renew_interval HOUR))
                  AND (max_renewals = 0 OR renewals_count < max_renewals)";
        
        $stmt = $this->db->query($query);
        $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($videos as $video) {
            $this->renewSingleVideo($video);
        }
        
        $this->log("✅ Vidéos renouvelées : " . count($videos));
    }
    
    private function renewSingleVideo($video) {
        try {
            $this->db->beginTransaction();
            
            // Créer une nouvelle entrée pour la vidéo renouvelée
            $new_start = date('Y-m-d');
            $new_end = date('Y-m-d', strtotime("+{$video['renew_interval']} hours"));
            
            $insert = "INSERT INTO videos 
                      (title, description, video_url, thumbnail_url, reward_amount, 
                       duration, category_id, start_date, end_date, auto_renew, 
                       renew_interval, original_video_id, video_type)
                      VALUES 
                      (:title, :description, :video_url, :thumbnail_url, :reward_amount,
                       :duration, :category_id, :start_date, :end_date, :auto_renew,
                       :renew_interval, :original_id, 'renewed')";
            
            $stmt = $this->db->prepare($insert);
            $stmt->execute([
                ':title' => $video['title'] . " (Session " . ($video['renewals_count'] + 1) . ")",
                ':description' => $video['description'],
                ':video_url' => $video['video_url'],
                ':thumbnail_url' => $video['thumbnail_url'],
                ':reward_amount' => $video['reward_amount'],
                ':duration' => $video['duration'],
                ':category_id' => $video['category_id'],
                ':start_date' => $new_start,
                ':end_date' => $new_end,
                ':auto_renew' => $video['auto_renew'],
                ':renew_interval' => $video['renew_interval'],
                ':original_id' => $video['id']
            ]);
            
            // Mettre à jour le compteur de la vidéo originale
            $update = "UPDATE videos 
                       SET renewals_count = renewals_count + 1,
                           last_renewed = NOW()
                       WHERE id = :id";
            $upd_stmt = $this->db->prepare($update);
            $upd_stmt->execute([':id' => $video['id']]);
            
            $this->db->commit();
            $this->log("   → Vidéo #{$video['id']} renouvelée avec succès");
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->log("   ❌ Erreur pour la vidéo #{$video['id']} : " . $e->getMessage());
        }
    }
    
    private function activateScheduledVideos() {
        $query = "UPDATE videos 
                  SET is_active = 1 
                  WHERE start_date <= CURDATE() 
                  AND end_date >= CURDATE()
                  AND is_active = 0";
        $count = $this->db->exec($query);
        $this->log("✅ Vidéos programmées activées : $count");
    }
    
    private function cleanTemporaryVideos() {
        // Supprimer les vidéos temporaires de plus de 7 jours
        $query = "DELETE FROM videos 
                  WHERE video_type = 'temporary' 
                  AND created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $count = $this->db->exec($query);
        $this->log("✅ Vidéos temporaires nettoyées : $count");
    }
}

// Exécution
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $renewal = new VideoRenewal($db);
    $renewal->renewVideos();
    
    echo "✅ Renouvellement terminé avec succès !";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
    error_log("Erreur renouvellement vidéos : " . $e->getMessage());
}
?>