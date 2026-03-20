<?php
// videos.php
session_start();
require_once 'includes/Auth.php';
require_once 'config/database.php';
require_once 'includes/Video.php';
require_once 'includes/User.php';

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

$videoModel = new Video($db);
$userModel = new User($db);

$user = $userModel->getUserById($_SESSION['user_id']);
$videos = $videoModel->getAvailableVideos($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Vidéos - PubWatch Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .navbar { background: white; padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .nav-logo { font-size: 1.5em; font-weight: bold; color: #667eea; text-decoration: none; }
        .nav-menu { display: flex; list-style: none; gap: 20px; align-items: center; }
        .nav-link { color: #333; text-decoration: none; }
        .nav-link:hover { color: #667eea; }
        .nav-balance { background: #27ae60; color: white; padding: 5px 15px; border-radius: 20px; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 { color: #333; }
        .header p { color: #27ae60; font-size: 1.2em; font-weight: bold; }
        
        .videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .video-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .video-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .video-thumbnail {
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3em;
        }
        
        .video-info {
            padding: 20px;
        }
        
        .video-info h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .video-info p {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .video-reward {
            background: #27ae60;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 15px;
            font-weight: bold;
        }
        
        .watch-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: opacity 0.3s ease;
        }
        
        .watch-btn:hover:not(:disabled) {
            opacity: 0.9;
        }
        
        .watch-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .watched-badge {
            background: #27ae60;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
            text-align: center;
        }
        
        /* Modal vidéo */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
        }
        
        .modal-content {
            position: relative;
            width: 80%;
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            padding: 20px;
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 30px;
            cursor: pointer;
            color: #333;
        }
        
        .video-player {
            width: 100%;
            height: 400px;
            background: #000;
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2em;
        }
        
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .reward-message {
            text-align: center;
            color: #27ae60;
            font-weight: bold;
            font-size: 1.2em;
        }

        .no-videos {
            background: white;
            padding: 50px;
            text-align: center;
            border-radius: 10px;
            grid-column: 1 / -1;
        }

        .no-videos p {
            color: #666;
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        /* Styles pour la navigation */
.navbar {
    background: white;
    padding: 15px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}
.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.nav-logo {
    font-size: 1.5em;
    font-weight: bold;
    color: #667eea;
    text-decoration: none;
}
.nav-menu {
    display: flex;
    list-style: none;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}
.nav-menu li a {
    text-decoration: none;
    font-size: 0.95em;
}
.nav-link {
    color: #333;
    padding: 8px 12px;
    border-radius: 5px;
    transition: all 0.3s ease;
}
.nav-link:hover {
    background: #f7f7f7;
    color: #667eea;
}
.nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important;
}
.nav-register {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 8px 20px;
    border-radius: 5px;
    font-weight: bold;
}
.nav-logout {
    background: #e74c3c;
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
}
.nav-balance {
    background: #27ae60;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: bold;
}

/* Responsive */
@media (max-width: 768px) {
    .nav-container {
        flex-direction: column;
        gap: 15px;
    }
    .nav-menu {
        justify-content: center;
    }
}
    </style>
</head>
<body>
    <!-- Dans videos.php - Remplacer la navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">💰 PubWatch Pro</a>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="videos.php" class="nav-link active">Vidéos</a></li>
                <li><a href="referrals.php" class="nav-link">Parrainage</a></li>
                <li><a href="leaderboard.php" class="nav-link">🏆 Classement</a></li>
                <li><a href="withdraw.php" class="nav-link">Retrait</a></li>
                <li class="nav-balance"><?= number_format($user['balance']) ?> FCFA</li>
                <li><a href="logout.php" class="nav-logout">Déconnexion</a></li>
                <li><a href="how-it-works.php" class="nav-link">📚 Guide</a></li>
            
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <div>
                <h1>📺 Vidéos disponibles</h1>
                <p>Gagnez 100 000 FCFA par vidéo</p>
            </div>
            <div>
                <p>Votre solde: <?= number_format($user['balance']) ?> FCFA</p>
            </div>
        </div>

        <div class="videos-grid">
            <?php if(empty($videos)): ?>
                <div class="no-videos">
                    <p>😴 Aucune vidéo disponible pour le moment</p>
                    <p>Revenez plus tard, de nouvelles vidéos sont ajoutées régulièrement !</p>
                </div>
            <?php else: ?>
                <?php foreach($videos as $video): ?>
                <div class="video-card">
                    <div class="video-thumbnail">🎬</div>
                    <div class="video-info">
                        <h3><?= htmlspecialchars($video['title']) ?></h3>
                        <p><?= htmlspecialchars($video['description']) ?></p>
                        <div class="video-reward">
                            🏆 <?= number_format($video['reward_amount']) ?> FCFA
                        </div>
                        <?php if($video['already_watched']): ?>
                            <div class="watched-badge">✅ Déjà regardée</div>
                        <?php else: ?>
                            <button class="watch-btn" onclick="openVideo(<?= $video['id'] ?>, '<?= addslashes($video['title']) ?>')">
                                Regarder et gagner
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal pour la vidéo -->
    <div id="videoModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="videoTitle"></h2>
            <div class="video-player" id="videoPlayer">
                🎬 Simulation vidéo
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="reward-message" id="rewardMessage">
                Regardez la vidéo jusqu'à la fin pour gagner 100 000 FCFA
            </div>
            <button class="watch-btn" id="simulateWatch" onclick="simulateWatch()" style="display: none;">
                Simuler le visionnage (test)
            </button>
        </div>
    </div>

    <script>
    let currentVideoId = null;
    let watchInterval = null;
    let progress = 0;

    function openVideo(videoId, title) {
        currentVideoId = videoId;
        document.getElementById('videoTitle').innerHTML = title;
        document.getElementById('videoModal').style.display = 'block';
        
        // Simuler le visionnage (en vrai, ici tu mettrais une vraie vidéo)
        progress = 0;
        document.getElementById('progressFill').style.width = '0%';
        document.getElementById('rewardMessage').innerHTML = 'Regardez la vidéo jusqu\'à la fin...';
        
        // Démarrer la progression
        watchInterval = setInterval(function() {
            progress += 2;
            document.getElementById('progressFill').style.width = progress + '%';
            
            if(progress >= 100) {
                clearInterval(watchInterval);
                completeWatch();
            }
        }, 1000);
    }
/*      function openVideo(videoId, videoUrl, isYouTube) {
    currentVideoId = videoId;
    document.getElementById('videoTitle').innerHTML = title;
    document.getElementById('videoModal').style.display = 'block';
    
    if (isYouTube) {
        // Intégration YouTube
        document.getElementById('videoPlayer').innerHTML = 
            '<iframe width="100%" height="400" src="' + videoUrl + '" frameborder="0" allowfullscreen></iframe>';
    } else {
        // Vidéo normale
        document.getElementById('videoPlayer').innerHTML = '<video width="100%" height="400" controls><source src="' + videoUrl + '" type="video/mp4"></video>';
    }
} */

    function closeModal() {
        document.getElementById('videoModal').style.display = 'none';
        if(watchInterval) {
            clearInterval(watchInterval);
        }
    }

    function completeWatch() {
        document.getElementById('rewardMessage').innerHTML = '✅ Félicitations ! Gain de 100 000 FCFA !';
        
        // Envoyer la requête au serveur
        fetch('api/watch.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                video_id: currentVideoId
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                setTimeout(function() {
                    alert('🎉 Vous avez gagné 100 000 FCFA !');
                    location.reload(); // Recharger pour mettre à jour la liste
                }, 2000);
            } else {
                alert('Erreur: ' + data.error);
                closeModal();
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'enregistrement');
            closeModal();
        });
        checkChallenges();
    }
    // Ajouter la fonction checkChallenges
    function checkChallenges() {
    fetch('api/check_challenges.php')
        .then(response => response.json())
        .then(data => {
            if (data.rewards_claimed && data.rewards_claimed.length > 0) {
                let message = '🎉 Défis complétés ! Vous avez gagné :\n';
                data.rewards_claimed.forEach(reward => {
                    message += `\n- ${reward.title} : ${reward.reward.toLocaleString()} FCFA`;
                });
                alert(message);
                
                // Mettre à jour le solde affiché
                const balanceElements = document.querySelectorAll('.nav-balance');
                balanceElements.forEach(el => {
                    el.textContent = new Intl.NumberFormat('fr-FR').format(data.new_balance) + ' FCFA';
                });
            }
        });
    }
    // Fonction pour tester (à retirer en production)
    function simulateWatch() {
        if(watchInterval) clearInterval(watchInterval);
        progress = 100;
        document.getElementById('progressFill').style.width = '100%';
        completeWatch();
    }

    // Fermer le modal si on clique en dehors
    window.onclick = function(event) {
        var modal = document.getElementById('videoModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    </script>
</body>
</html>