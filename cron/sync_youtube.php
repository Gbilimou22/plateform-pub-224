<?php
// cron/sync_youtube.php
require_once '../config/database.php';
require_once '../includes/YouTube.php';

$database = new Database();
$db = $database->getConnection();
$youtube = new YouTubeAPI($db);

// Récupérer toutes les chaînes à synchroniser
$channels = $db->query("SELECT * FROM youtube_channels WHERE auto_import = 1")->fetchAll(PDO::FETCH_ASSOC);

foreach ($channels as $channel) {
    echo "Synchronisation de " . $channel['channel_name'] . "...\n";
    $count = $youtube->syncChannel($channel['channel_id'], $channel['category_id'] ?? 1);
    echo "✅ $count nouvelles vidéos importées\n";
}

// Nettoyer les anciennes vidéos YouTube
$clean = $db->exec("DELETE FROM youtube_imported_videos WHERE status = 'pending' AND imported_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
echo "🧹 Nettoyage: $clean vidéos supprimées\n";
?>