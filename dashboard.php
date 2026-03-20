<?php
// dashboard.php
session_start();
require_once 'includes/Auth.php';
require_once 'config/database.php';
require_once 'includes/User.php';
require_once 'includes/Video.php';
require_once 'includes/Withdrawal.php';

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

$userModel = new User($db);
$videoModel = new Video($db);
$withdrawalModel = new Withdrawal($db);

$user = $userModel->getUserById($_SESSION['user_id']);
$recentWatches = $videoModel->getRecentWatches($_SESSION['user_id'], 5);
$monthlyEarnings = $videoModel->getMonthlyEarnings($_SESSION['user_id']);
$withdrawals = $withdrawalModel->getUserWithdrawals($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard - PubWatch Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .navbar { background: white; padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .nav-logo { font-size: 1.5em; font-weight: bold; color: #667eea; text-decoration: none; }
        .nav-menu { display: flex; list-style: none; gap: 20px; align-items: center; }
        .nav-link { color: #333; text-decoration: none; }
        .nav-balance { background: #27ae60; color: white; padding: 5px 15px; border-radius: 20px; }
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .welcome { background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-value { font-size: 2em; font-weight: bold; color: #667eea; margin: 10px 0; }
        .recent-activity { background: white; padding: 20px; border-radius: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
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
    <nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">💰 PubWatch Pro</a>
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
            <li><a href="videos.php" class="nav-link">Vidéos</a></li>
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
        <div class="welcome">
            <h1>Bienvenue, <?= htmlspecialchars($user['username']) ?> !</h1>
            <p>Voici vos statistiques</p>
        </div>
        
    <!-- Bonus quotidien -->
    <div style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 style="margin-bottom: 5px;">🎁 Bonus de connexion quotidien</h3>
            <p>Connectez-vous chaque jour pour gagner plus !</p>
        </div>
        <button id="claimBonusBtn" onclick="claimDailyBonus()" style="background: white; color: #e67e22; border: none; padding: 10px 25px; border-radius: 5px; font-weight: bold; cursor: pointer;">
            Réclamer mon bonus
        </button>
    </div>

    <script>
    function claimDailyBonus() {
        fetch('api/daily_bonus.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert(data.message || 'Erreur');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la réclamation');
            });
    }
    </script>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Solde</h3>
                <div class="stat-value"><?= number_format($user['balance']) ?> FCFA</div>
            </div>
            <div class="stat-card">
                <h3>Gains totaux</h3>
                <div class="stat-value"><?= number_format($user['total_earned']) ?> FCFA</div>
            </div>
            <div class="stat-card">
                <h3>Vidéos regardées</h3>
                <div class="stat-value"><?= $user['videos_watched'] ?></div>
            </div>
            <div class="stat-card">
                <h3>Ce mois-ci</h3>
                <div class="stat-value"><?= number_format($monthlyEarnings) ?> FCFA</div>
            </div>
        </div>
        <?php
    
    require_once 'includes/Gamification.php';
    $gamification = new Gamification($db);
    $userLevel = $gamification->getUserLevel($user['videos_watched']);
    $userBadges = $gamification->getUserBadges($user['id']);
    ?>

    <!-- Section Niveau et Badges -->
    <div style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
        <h2 style="margin-bottom: 20px;">🎮 Votre progression</h2>
        
        <!-- Niveau actuel -->
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
            <div style="font-size: 4em;"><?= $userLevel['icon'] ?></div>
            <div style="flex: 1;">
                <h3 style="color: #333;">Niveau <?= $userLevel['level'] ?> - <?= $userLevel['title'] ?></h3>
                
                <?php if($userLevel['next_level']): ?>
                <div style="margin-top: 10px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span>Prochain niveau: <?= $userLevel['next_level'] ?> vidéos</span>
                        <span><?= round($userLevel['progress']) ?>%</span>
                    </div>
                    <div style="width: 100%; height: 10px; background: #e0e0e0; border-radius: 5px; overflow: hidden;">
                        <div style="width: <?= $userLevel['progress'] ?>%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                    </div>
                </div>
                <?php else: ?>
                <p style="color: gold; font-weight: bold;">🏆 Niveau maximum atteint !</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Badges -->
        <h3 style="margin-bottom: 15px;">🏅 Vos badges</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <?php foreach($userBadges as $badge): ?>
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 10px; text-align: center;">
                <div style="font-size: 2em; margin-bottom: 5px;"><?= $badge['icon'] ?></div>
                <div style="font-weight: bold; margin-bottom: 5px;"><?= $badge['name'] ?></div>
                <div style="font-size: 0.8em; opacity: 0.9;"><?= $badge['description'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    

<?php
require_once 'includes/Challenges.php';
$challenges = new Challenges($db);
$dailyChallenges = $challenges->getDailyChallenges($user['id']);
?>

<!-- Défis quotidiens -->
<div style="background: white; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
    <h2 style="margin-bottom: 20px;">🎯 Défis du jour</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <?php foreach($dailyChallenges as $challenge): ?>
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px;">
            <div style="font-size: 2em; margin-bottom: 10px;"><?= $challenge['icon'] ?></div>
            <h3 style="margin-bottom: 5px;"><?= $challenge['title'] ?></h3>
            <p style="font-size: 0.9em; margin-bottom: 15px;"><?= $challenge['description'] ?></p>
            <div style="margin-bottom: 10px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <span>Progression</span>
                    <span><?= $challenge['progress'] ?? 0 ?>/<?= $challenge['target'] ?></span>
                </div>
                <div style="width: 100%; height: 8px; background: rgba(255,255,255,0.3); border-radius: 4px; overflow: hidden;">
                    <div style="width: <?= (($challenge['progress'] ?? 0) / $challenge['target']) * 100 ?>%; height: 100%; background: white;"></div>
                </div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span>🏆 <?= number_format($challenge['reward']) ?> FCFA</span>
                <?php if(($challenge['progress'] ?? 0) >= $challenge['target']): ?>
                <span style="background: #27ae60; padding: 5px 10px; border-radius: 5px; font-size: 0.8em;">✅ Complété</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

    <div class="recent-activity">
            <h2>Activité récente</h2>
            <?php if(empty($recentWatches)): ?>
                <p>Vous n'avez pas encore regardé de vidéos.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Vidéo</th>
                        <th>Gain</th>
                        <th>Date</th>
                    </tr>
                    <?php foreach($recentWatches as $watch): ?>
                    <tr>
                        <td><?= htmlspecialchars($watch['title']) ?></td>
                        <td><?= number_format($watch['reward_earned']) ?> FCFA</td>
                        <td><?= date('d/m/Y H:i', strtotime($watch['watched_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
<script>
    // Vérifier les défis toutes les 30 secondes
setInterval(function() {
    fetch('api/check_challenges.php')
        .then(response => response.json())
        .then(data => {
            if (data.rewards_claimed && data.rewards_claimed.length > 0) {
                // Afficher une notification discrète
                const notification = document.createElement('div');
                notification.style.cssText = 'position:fixed; top:20px; right:20px; background:#27ae60; color:white; padding:15px; border-radius:5px; z-index:1000; animation:slideIn 0.3s;';
                notification.innerHTML = '🎉 Défi complété ! Vérifiez vos récompenses.';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                    location.reload();
                }, 3000);
            }
        });
}, 30000);

// Animation
const style = document.createElement('style');
style.innerHTML = `
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}`;
document.head.appendChild(style);
</script>
</html>