<?php
// admin/videos.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Traitement des actions
$message = '';
$error = '';

// Ajouter une vidéo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $video_url = $_POST['video_url'];
        $thumbnail_url = $_POST['thumbnail_url'];
        $reward = $_POST['reward'];
        $duration = $_POST['duration'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        
        $query = "INSERT INTO videos (title, description, video_url, thumbnail_url, reward_amount, duration, start_date, end_date) 
                  VALUES (:title, :description, :video_url, :thumbnail_url, :reward, :duration, :start_date, :end_date)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":video_url", $video_url);
        $stmt->bindParam(":thumbnail_url", $thumbnail_url);
        $stmt->bindParam(":reward", $reward);
        $stmt->bindParam(":duration", $duration);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        
        if ($stmt->execute()) {
            $message = "Vidéo ajoutée avec succès !";
        } else {
            $error = "Erreur lors de l'ajout";
        }
    }
    
    // Supprimer une vidéo
    if ($_POST['action'] === 'delete' && isset($_POST['video_id'])) {
        $video_id = $_POST['video_id'];
        $query = "DELETE FROM videos WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $video_id);
        if ($stmt->execute()) {
            $message = "Vidéo supprimée";
        }
    }
}

// Récupérer toutes les vidéos
$query = "SELECT * FROM videos ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestion Vidéos - Admin</title>
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            display: inline-block;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-success { background: #27ae60; color: white; }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .video-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .video-thumb {
            height: 150px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2em;
        }
        
        .video-info { padding: 15px; }
        .video-info h3 { margin-bottom: 5px; color: #333; }
        .video-info p { color: #666; font-size: 0.9em; margin-bottom: 10px; }
        .video-meta { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .reward { background: #27ae60; color: white; padding: 3px 8px; border-radius: 3px; font-size: 0.8em; }
        .date { color: #999; font-size: 0.8em; }
        .actions { display: flex; gap: 5px; margin-top: 10px; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
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
                <li><a href="videos.php" class="active"><span class="icon">🎬</span> Vidéos</a></li>
                <li><a href="withdrawals.php"><span class="icon">💸</span> Retraits</a></li>
                <li><a href="stats.php"><span class="icon">📈</span> Statistiques</a></li>
                <li><a href="settings.php"><span class="icon">⚙️</span> Paramètres</a></li>
                <li><a href="video_renewals.php"><span class="icon">🔄</span> Renouvellements</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>🎬 Gestion des Vidéos</h1>
                <button class="btn btn-primary" onclick="openModal()">+ Ajouter une vidéo</button>
            </div>

            <?php if($message): ?>
                <div class="message success"><?= $message ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="message error"><?= $error ?></div>
            <?php endif; ?>

            <div class="video-grid">
                <?php foreach($videos as $video): ?>
                <div class="video-card">
                    <div class="video-thumb">🎬</div>
                    <div class="video-info">
                        <h3><?= htmlspecialchars($video['title']) ?></h3>
                        <p><?= htmlspecialchars(substr($video['description'], 0, 100)) ?>...</p>
                        <div class="video-meta">
                            <span class="reward">💰 <?= number_format($video['reward_amount']) ?> FCFA</span>
                            <span class="date"><?= date('d/m/Y', strtotime($video['created_at'])) ?></span>
                        </div>
                        <div class="actions">
                            <a href="video_edit.php?id=<?= $video['id'] ?>" class="btn btn-primary">Modifier</a>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="video_id" value="<?= $video['id'] ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Supprimer cette vidéo ?')">Supprimer</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <!-- Section Ajout rapide -->
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
    <h2 style="color: white;">⚡ Ajout rapide de vidéos</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
        <!-- Boutons prédéfinis -->
        <button class="quick-add" onclick="quickAdd('tech')">
            📱 + 5 vidéos Technologie
        </button>
        <button class="quick-add" onclick="quickAdd('fashion')">
            👗 + 5 vidéos Mode
        </button>
        <button class="quick-add" onclick="quickAdd('food')">
            🍔 + 5 vidéos Alimentation
        </button>
        <button class="quick-add" onclick="quickAdd('sport')">
            ⚽ + 5 vidéos Sport
        </button>
    </div>
    
    <div style="margin-top: 20px;">
        <textarea id="bulkUrls" placeholder="Collez plusieurs URLs de vidéos (une par ligne)" style="width: 100%; padding: 10px; border-radius: 5px;" rows="3"></textarea>
        <button onclick="addBulkVideos()" style="margin-top: 10px; padding: 10px 20px; background: white; color: #667eea; border: none; border-radius: 5px; cursor: pointer;">
            📥 Ajouter en lot
        </button>
    </div>
</div>

<style>
.quick-add {
    padding: 15px;
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 8px;
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}
.quick-add:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
}
</style>

<script>
function quickAdd(category) {
    const categories = {
        tech: [
            {title: "Nouveau Smartphone", url: "https://example.com/tech1.mp4"},
            {title: "Ordinateur Portable", url: "https://example.com/tech2.mp4"},
            {title: "Casque Audio", url: "https://example.com/tech3.mp4"},
            {title: "Montre Connectée", url: "https://example.com/tech4.mp4"},
            {title: "Tablette", url: "https://example.com/tech5.mp4"}
        ],
        fashion: [
            {title: "Collection Été", url: "https://example.com/fashion1.mp4"},
            // ... etc
        ]
    };
    
    let selected = categories[category];
    let formData = new FormData();
    formData.append('bulk_add', JSON.stringify(selected));
    
    fetch('ajax_add_videos.php', {
        method: 'POST',
        body: formData
    }).then(response => response.json())
      .then(data => {
          alert(data.message);
          location.reload();
      });
}

function addBulkVideos() {
    let urls = document.getElementById('bulkUrls').value.split('\n');
    // Traitement des URLs
}
</script>
    <!-- Modal Ajout Vidéo -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 20px;">Ajouter une nouvelle vidéo</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Titre</label>
                    <input type="text" name="title" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>URL de la vidéo</label>
                    <input type="url" name="video_url" required>
                </div>
                
                <div class="form-group">
                    <label>URL de la miniature (optionnel)</label>
                    <input type="url" name="thumbnail_url">
                </div>
                
                <div class="form-group">
                    <label>Récompense (FCFA)</label>
                    <input type="number" name="reward" value="100000" required>
                </div>
                
                <div class="form-group">
                    <label>Durée (secondes)</label>
                    <input type="number" name="duration" value="60" required>
                </div>
                
                <div class="form-group">
                    <label>Date de début</label>
                    <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Date de fin</label>
                    <input type="date" name="end_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Ajouter</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">Annuler</button>
                </div>
                <!-- Dans le formulaire d'ajout de vidéo, ajoute cette section -->
<div style="background: #f7f7f7; padding: 20px; border-radius: 10px; margin: 20px 0;">
    <h3 style="margin-bottom: 15px;">🔄 Configuration du renouvellement automatique</h3>
    
    <div class="form-group">
        <label>
            <input type="checkbox" name="auto_renew" value="1" 
                   onchange="toggleRenewOptions(this.checked)">
            Activer le renouvellement automatique toutes les 24h
        </label>
        <small>La vidéo sera automatiquement remplacée par une nouvelle toutes les 24h</small>
    </div>
    
    <div id="renewOptions" style="display: none; margin-top: 15px;">
        <div class="form-group">
            <label>Intervalle de renouvellement (heures)</label>
            <select name="renew_interval">
                <option value="24">Toutes les 24 heures</option>
                <option value="48">Toutes les 48 heures</option>
                <option value="72">Toutes les 72 heures</option>
                <option value="168">Toutes les semaines</option>
            </select>
        </div>
        
        <div class="form-group">
            <label>Nombre maximum de renouvellements</label>
            <input type="number" name="max_renewals" value="0" min="0">
            <small>0 = illimité</small>
        </div>
        
        <div class="form-group">
            <label>Type de vidéo</label>
            <select name="video_type">
                <option value="normal">Normale (reste en ligne)</option>
                <option value="renewable">Renouvelable (crée des copies)</option>
                <option value="temporary">Temporaire (supprimée après 7 jours)</option>
            </select>
        </div>
    </div>
</div>

<script>
function toggleRenewOptions(checked) {
    document.getElementById('renewOptions').style.display = checked ? 'block' : 'none';
}
</script>
            </form>
        </div>
    </div>

    <script>
    function openModal() {
        document.getElementById('addModal').style.display = 'block';
    }
    
    function closeModal() {
        document.getElementById('addModal').style.display = 'none';
    }
    
    // Fermer si on clique en dehors
    window.onclick = function(event) {
        if (event.target == document.getElementById('addModal')) {
            closeModal();
        }
    }
    </script>
</body>
</html>