<?php
// includes/Video.php
class Video {
    private $conn;
    private $table = "videos";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAvailableVideos($user_id) {
        $query = "SELECT v.*, 
                         CASE WHEN wh.id IS NOT NULL THEN 1 ELSE 0 END as already_watched
                  FROM " . $this->table . " v
                  LEFT JOIN watch_history wh ON v.id = wh.video_id AND wh.user_id = :user_id
                  WHERE v.is_active = 1 
                    AND v.start_date <= CURDATE() 
                    AND v.end_date >= CURDATE()
                  ORDER BY v.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVideoById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function hasUserWatched($user_id, $video_id) {
        $query = "SELECT id FROM watch_history 
                  WHERE user_id = :user_id AND video_id = :video_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":video_id", $video_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function addWatchHistory($user_id, $video_id, $reward) {
        $query = "INSERT INTO watch_history (user_id, video_id, reward_earned)
                  VALUES (:user_id, :video_id, :reward)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":video_id", $video_id);
        $stmt->bindParam(":reward", $reward);
        return $stmt->execute();
    }

    public function getRecentWatches($user_id, $limit = 5) {
        $query = "SELECT wh.*, v.title 
                  FROM watch_history wh
                  JOIN videos v ON wh.video_id = v.id
                  WHERE wh.user_id = :user_id
                  ORDER BY wh.watched_at DESC
                  LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyEarnings($user_id) {
        $query = "SELECT COALESCE(SUM(reward_earned), 0) as total 
                  FROM watch_history 
                  WHERE user_id = :user_id 
                    AND MONTH(watched_at) = MONTH(CURRENT_DATE())
                    AND YEAR(watched_at) = YEAR(CURRENT_DATE())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
?>