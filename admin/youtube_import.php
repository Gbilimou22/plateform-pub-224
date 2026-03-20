<?php
// admin/youtube_import.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
require_once '../includes/YouTube.php';

$database = new Database();
$db = $database->getConnection();
$youtube = new YouTubeAPI($db);

$message = '';
$error = '';
$search_results = [];

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search'])) {
        $keyword = $_POST['keyword'];
        $search_results = $youtube->searchVideos($keyword, 20);
    }
    
    if (isset($_POST['import'])) {
        $video_id = $_POST['video_id'];
        $category = $_POST['category_id'];
        $result = $youtube->importVideo($video_id, $category);
        
        if ($result['success']) {
            $message = "✅ Vidéo importée avec succès !";
        } else {
            $error = "❌ " . $result['message'];
        }
    }
    
    if (isset($_POST['import_keywords'])) {
        $keywords = explode("\n", trim($_POST['keywords']));
        $keywords = array_filter(array_map('trim', $keywords));
        $category = $_POST['category_id'];
        
        $results = $youtube->importByKeywords($keywords, 5, $category);
        
        $success_count = 0;
        foreach ($results as $r) {
            if ($r['result']['success']) $success_count++;
        }
        
        $message = "✅ $success_count vidéos importées sur " . count($results);
    }
}

// Récupérer les catégories
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les vidéos importées récemment
$imported = $db->query("SELECT * FROM youtube_imported_videos ORDER BY imported_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Import YouTube - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { display: flex; min-height: 100vh; }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .sidebar-header { text-align: center; padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-menu { list-style: none; margin-top: 30px; }
        .sidebar-menu li { margin-bottom: 10px; }
        .sidebar-menu a {
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            display: block;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.1); }
        .sidebar-menu .icon { margin-right: 10px; }
        
        .main-content { flex: 1; padding: 20px; }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .youtube-header {
            background: linear-gradient(135deg, #FF0000 0%, #CC0000 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .search-section, .bulk-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-danger { background: #FF0000; color: white; }
        .btn-success { background: #27ae60; color: white; }
        
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .video-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .video-thumb {
            height: 150px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .video-duration {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
        }
        
        .video-info {
            padding: 15px;
        }
        .video-info h3 {
            font-size: 1em;
            margin-bottom: 5px;
            color: #333;
        }
        .video-info p {
            color: #666;
            font-size: 0.8em;
            margin-bottom: 10px;
        }
        .video-channel {
            color: #999;
            font-size: 0.75em;
        }
        
        .import-form {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .import-form select {
            flex: 1;
            padding: 5px;
        }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tab {
            padding: 10px 20px;
            background: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>💰 PubWatch Pro</h2>
                <p>Administration</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php"><span class="icon">📊</span> Dashboard</a></li>
                <li><a href="users.php"><span class="icon">👥</span> Utilisateurs</a></li>
                <li><a href="videos.php"><span class="icon">🎬</span> Vidéos</a></li>
                <li><a href="youtube_import.php" class="active"><span class="icon">📺</span> YouTube</a></li>
                <li><a href="withdrawals.php"><span class="icon">💸</span> Retraits</a></li>
                <li><a href="stats.php"><span class="icon">📈</span> Statistiques</a></li>
                <li><a href="settings.php"><span class="icon">⚙️</span> Paramètres</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>📺 Importation YouTube automatique</h1>
            </div>

            <div class="youtube-header">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/7/75/YouTube_social_white_squircle.svg/1200px-YouTube_social_white_squircle.svg.png" 
                     style="width: 60px; margin-bottom: 15px;">
                <h2>Intégration YouTube</h2>
                <p>Importez automatiquement des vidéos depuis YouTube</p>
            </div>

            <?php if($message): ?>
                <div class="message success"><?= $message ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="message error"><?= $error ?></div>
            <?php endif; ?>

            <!-- Tabs -->
            <div class="tabs">
                <div class="tab active" onclick="showTab('search')">🔍 Rechercher</div>
                <div class="tab" onclick="showTab('bulk')">📦 Import en masse</div>
                <div class="tab" onclick="showTab('channel')">📺 Chaînes</div>
                <div class="tab" onclick="showTab('history')">📋 Historique</div>
            </div>

            <!-- Tab Recherche -->
            <div id="search" class="tab-content active">
                <div class="search-section">
                    <h2 style="margin-bottom: 20px;">Rechercher des vidéos</h2>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Mot-clé</label>
                            <input type="text" name="keyword" placeholder="Ex: pub, technologie, sport..." required>
                        </div>
                        <button type="submit" name="search" class="btn btn-primary">Rechercher</button>
                    </form>
                </div>

                <?php if(!empty($search_results)): ?>
                <div class="video-grid">
                    <?php foreach($search_results as $video): ?>
                    <div class="video-card">
                        <div class="video-thumb" style="background-image: url('<?= $video['thumbnail'] ?>')">
                            <span class="video-duration"><?= gmdate("i:s", $video['duration']) ?></span>
                        </div>
                        <div class="video-info">
                            <h3><?= htmlspecialchars(substr($video['title'], 0, 50)) ?>...</h3>
                            <p class="video-channel"><?= htmlspecialchars($video['channel']) ?></p>
                            <p><?= number_format($video['views']) ?> vues</p>
                            
                            <form method="POST" class="import-form">
                                <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                                <select name="category_id" required>
                                    <?php foreach($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= $cat['icon'] ?> <?= $cat['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="import" class="btn btn-success">Importer</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tab Import en masse -->
            <div id="bulk" class="tab-content">
                <div class="bulk-section">
                    <h2 style="margin-bottom: 20px;">Import par mots-clés</h2>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Mots-clés (un par ligne)</label>
                            <textarea name="keywords" rows="5" placeholder="technologie&#10;mode&#10;sport&#10;cuisine" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Catégorie par défaut</label>
                            <select name="category_id">
                                <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= $cat['icon'] ?> <?= $cat['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" name="import_keywords" class="btn btn-primary">
                            📥 Importer 5 vidéos par mot-clé
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tab Chaînes -->
            <div id="channel" class="tab-content">
                <div class="bulk-section">
                    <h2 style="margin-bottom: 20px;">Ajouter une chaîne YouTube</h2>
                    
                    <form method="POST" action="youtube_channel.php">
                        <div class="form-group">
                            <label>URL ou ID de la chaîne</label>
                            <input type="text" name="channel_url" placeholder="https://youtube.com/c/..." required>
                        </div>
                        
                        <div class="form-group">
                            <label>Catégorie</label>
                            <select name="category_id">
                                <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= $cat['icon'] ?> <?= $cat['name'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="auto_sync" value="1" checked>
                                Synchronisation automatique quotidienne
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">➕ Ajouter la chaîne</button>
                    </form>
                </div>
            </div>

            <!-- Tab Historique -->
            <div id="history" class="tab-content">
                <div class="bulk-section">
                    <h2 style="margin-bottom: 20px;">Dernières vidéos importées</h2>
                    
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Vidéo</th>
                                <th>Chaîne</th>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($imported as $vid): ?>
                            <tr>
                                <td><?= htmlspecialchars(substr($vid['title'], 0, 50)) ?></td>
                                <td><?= $vid['channel_id'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($vid['imported_at'])) ?></td>
                                <td>
                                    <span style="color: <?= $vid['status'] == 'imported' ? '#27ae60' : '#f39c12' ?>">
                                        <?= $vid['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab').forEach(el => el.classList.remove('active'));
        
        document.getElementById(tabId).classList.add('active');
        event.target.classList.add('active');
    }
    </script>
</body>
</html>