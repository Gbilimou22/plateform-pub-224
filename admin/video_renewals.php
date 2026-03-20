<?php
// admin/video_renewals.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer l'historique des renouvellements
$query = "SELECT v1.*, v2.title as original_title, v2.id as original_id
          FROM videos v1
          LEFT JOIN videos v2 ON v1.original_video_id = v2.id
          WHERE v1.video_type = 'renewed'
          ORDER BY v1.created_at DESC
          LIMIT 50";
$renewals = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stats_query = "SELECT 
                    COUNT(*) as total_renewals,
                    COUNT(DISTINCT original_video_id) as unique_videos,
                    SUM(reward_amount * (SELECT COUNT(*) FROM watch_history WHERE video_id = videos.id)) as total_paid
                FROM videos 
                WHERE video_type = 'renewed'";
$stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Historique des renouvellements</title>
    <style>
        /* Mêmes styles que les autres pages admin */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline-item {
            padding: 15px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #667eea;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            background: #27ae60;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar identique -->
        
        <div class="main-content">
            <div class="header">
                <h1>🔄 Historique des renouvellements automatiques</h1>
                <a href="videos.php" class="btn">← Retour aux vidéos</a>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Renouvellements totaux</h3>
                    <div class="stat-value"><?= $stats['total_renewals'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Vidéos uniques</h3>
                    <div class="stat-value"><?= $stats['unique_videos'] ?></div>
                </div>
                <div class="stat-card">
                    <h3>Montant distribué</h3>
                    <div class="stat-value"><?= number_format($stats['total_paid']) ?> FCFA</div>
                </div>
            </div>
            
            <div class="timeline">
                <h2>Derniers renouvellements</h2>
                <?php foreach($renewals as $renewal): ?>
                <div class="timeline-item">
                    <div style="display: flex; justify-content: space-between;">
                        <div>
                            <strong><?= htmlspecialchars($renewal['title']) ?></strong><br>
                            <small>Original: <?= htmlspecialchars($renewal['original_title'] ?? 'N/A') ?></small>
                        </div>
                        <div>
                            <span class="badge"><?= date('d/m/Y H:i', strtotime($renewal['created_at'])) ?></span>
                        </div>
                    </div>
                    <div style="margin-top: 10px; display: flex; gap: 20px;">
                        <span>💰 <?= number_format($renewal['reward_amount']) ?> FCFA</span>
                        <span>⏱️ <?= $renewal['duration'] ?>s</span>
                        <span>📅 du <?= date('d/m', strtotime($renewal['start_date'])) ?> au <?= date('d/m', strtotime($renewal['end_date'])) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>