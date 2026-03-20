<?php
// includes/YouTube.php
require_once 'config/youtube.php';

class YouTubeAPI {
    private $api_key;
    private $api_url;
    private $db;
    
    public function __construct($db) {
        $this->api_key = YOUTUBE_API_KEY;
        $this->api_url = YOUTUBE_API_URL;
        $this->db = $db;
    }
    
    // Rechercher des vidéos par mot-clé
    public function searchVideos($keyword, $max_results = 10) {
        $url = $this->api_url . "search?part=snippet&q=" . urlencode($keyword) . 
               "&type=video&maxResults=" . $max_results . 
               "&key=" . $this->api_key;
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        $videos = [];
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $video_id = $item['id']['videoId'];
                $video_details = $this->getVideoDetails($video_id);
                
                $videos[] = [
                    'id' => $video_id,
                    'title' => $item['snippet']['title'],
                    'description' => $item['snippet']['description'],
                    'thumbnail' => $item['snippet']['thumbnails']['high']['url'],
                    'channel' => $item['snippet']['channelTitle'],
                    'channel_id' => $item['snippet']['channelId'],
                    'published' => $item['snippet']['publishedAt'],
                    'duration' => $video_details['duration'] ?? 60,
                    'views' => $video_details['views'] ?? 0
                ];
            }
        }
        
        return $videos;
    }
    
    // Obtenir les détails d'une vidéo
    public function getVideoDetails($video_id) {
        $url = $this->api_url . "videos?part=contentDetails,statistics&id=" . $video_id . 
               "&key=" . $this->api_key;
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if (isset($data['items'][0])) {
            $duration = $this->convertDuration($data['items'][0]['contentDetails']['duration']);
            $views = $data['items'][0]['statistics']['viewCount'] ?? 0;
            
            return [
                'duration' => $duration,
                'views' => $views
            ];
        }
        
        return null;
    }
    
    // Convertir la durée ISO 8601 en secondes
    private function convertDuration($iso_duration) {
        $interval = new DateInterval($iso_duration);
        return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
    }
    
    // Importer une vidéo YouTube dans la base
    public function importVideo($youtube_video_id, $category_id = 1, $reward = 100000) {
        // Vérifier si déjà importée
        $check = $this->db->prepare("SELECT id FROM youtube_imported_videos WHERE youtube_video_id = :vid");
        $check->execute([':vid' => $youtube_video_id]);
        
        if ($check->rowCount() > 0) {
            return ['success' => false, 'message' => 'Vidéo déjà importée'];
        }
        
        // Récupérer les infos
        $details = $this->getVideoDetails($youtube_video_id);
        $search_url = $this->api_url . "videos?part=snippet&id=" . $youtube_video_id . "&key=" . $this->api_key;
        $search_response = file_get_contents($search_url);
        $search_data = json_decode($search_response, true);
        
        if (!isset($search_data['items'][0])) {
            return ['success' => false, 'message' => 'Vidéo non trouvée'];
        }
        
        $snippet = $search_data['items'][0]['snippet'];
        
        // Commencer la transaction
        $this->db->beginTransaction();
        
        try {
            // Insérer dans youtube_imported_videos
            $insert_import = "INSERT INTO youtube_imported_videos 
                             (youtube_video_id, title, description, thumbnail_url, duration, 
                              channel_id, published_at, status)
                             VALUES 
                             (:yid, :title, :desc, :thumb, :duration, :channel, :published, 'pending')";
            
            $stmt = $this->db->prepare($insert_import);
            $stmt->execute([
                ':yid' => $youtube_video_id,
                ':title' => $snippet['title'],
                ':desc' => $snippet['description'],
                ':thumb' => $snippet['thumbnails']['high']['url'],
                ':duration' => $details['duration'],
                ':channel' => $snippet['channelId'],
                ':published' => date('Y-m-d H:i:s', strtotime($snippet['publishedAt']))
            ]);
            
            // Créer la vidéo dans la table videos
            $insert_video = "INSERT INTO videos 
                            (title, description, video_url, thumbnail_url, reward_amount, 
                             duration, category_id, start_date, end_date, youtube_video_id)
                            VALUES 
                            (:title, :desc, :url, :thumb, :reward, :duration, :cat, 
                             CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), :yid)";
            
            $video_url = "https://www.youtube.com/embed/" . $youtube_video_id;
            
            $stmt2 = $this->db->prepare($insert_video);
            $stmt2->execute([
                ':title' => $snippet['title'],
                ':desc' => substr($snippet['description'], 0, 200),
                ':url' => $video_url,
                ':thumb' => $snippet['thumbnails']['high']['url'],
                ':reward' => $reward,
                ':duration' => $details['duration'],
                ':cat' => $category_id,
                ':yid' => $youtube_video_id
            ]);
            
            $video_id = $this->db->lastInsertId();
            
            // Mettre à jour le statut
            $update = "UPDATE youtube_imported_videos 
                       SET status = 'imported', video_id = :vid 
                       WHERE youtube_video_id = :yid";
            $upd = $this->db->prepare($update);
            $upd->execute([':vid' => $video_id, ':yid' => $youtube_video_id]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'video_id' => $video_id,
                'message' => 'Vidéo importée avec succès'
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Importer plusieurs vidéos par mots-clés
    public function importByKeywords($keywords, $max_per_keyword = 5, $category_id = 1) {
        $results = [];
        
        foreach ($keywords as $keyword) {
            $videos = $this->searchVideos($keyword, $max_per_keyword);
            
            foreach ($videos as $video) {
                $result = $this->importVideo($video['id'], $category_id);
                $results[] = [
                    'keyword' => $keyword,
                    'video' => $video['title'],
                    'result' => $result
                ];
            }
        }
        
        return $results;
    }
    
    // Synchroniser une chaîne YouTube
    public function syncChannel($channel_id, $category_id = 1) {
        // Récupérer les dernières vidéos de la chaîne
        $url = $this->api_url . "search?part=snippet&channelId=" . $channel_id . 
               "&type=video&maxResults=50&order=date&key=" . $this->api_key;
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        $imported = 0;
        
        if (isset($data['items'])) {
            foreach ($data['items'] as $item) {
                $video_id = $item['id']['videoId'];
                $result = $this->importVideo($video_id, $category_id);
                if ($result['success']) {
                    $imported++;
                }
            }
        }
        
        // Mettre à jour la date de synchro
        $update = "UPDATE youtube_channels SET last_sync = NOW() WHERE channel_id = :cid";
        $upd = $this->db->prepare($update);
        $upd->execute([':cid' => $channel_id]);
        
        return $imported;
    }
}
?>