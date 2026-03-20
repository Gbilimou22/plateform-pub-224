<?php
// leaderboard.php
session_start();
require_once 'includes/Auth.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer le top 10 des gagneurs
$query = "SELECT username, total_earned, videos_watched, created_at 
          FROM users 
          WHERE is_active = 1 
          ORDER BY total_earned DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Classement - PubWatch Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .navbar { background: white; padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .nav-logo { font-size: 1.5em; font-weight: bold; color: #667eea; text-decoration: none; }
        .nav-menu { display: flex; list-style: none; gap: 20px; align-items: center; }
        .nav-link { color: #333; text-decoration: none; }
        .nav-balance { background: #27ae60; color: white; padding: 5px 15px; border-radius: 20px; }
        .container { max-width: 800px; margin: 30px auto; padding: 0 20px; }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .podium {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 20px;
            margin: 40px 0;
        }
        
        .podium-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .podium-1 { 
            background: linear-gradient(135deg, #FFD700 0%, #FDB931 100%);
            transform: scale(1.1);
        }
        .podium-2 { background: linear-gradient(135deg, #C0C0C0 0%, #E8E8E8 100%); }
        .podium-3 { background: linear-gradient(135deg, #CD7F32 0%, #E6B17E 100%); }
        
        .crown { font-size: 2em; margin-bottom: 10px; }
        .podium-name { font-weight: bold; font-size: 1.2em; margin: 10px 0; }
        .podium-amount { font-size: 1.5em; font-weight: bold; }
        
        .leaderboard {
            background: white;
            padding: 30px;
            border-radius: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f7f7f7;
        }
        
        .rank-1 { color: #FFD700; font-weight: bold; }
        .rank-2 { color: #C0C0C0; font-weight: bold; }
        .rank-3 { color: #CD7F32; font-weight: bold; }
        
        .medal {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            background: #f7f7f7;
            font-weight: bold;
        }
        
        .your-rank {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-top: 20px;
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
    <nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">💰 PubWatch Pro</a>
        <ul class="nav-menu">
            <?php if($user): ?>
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="videos.php" class="nav-link">Vidéos</a></li>
                <li><a href="referrals.php" class="nav-link">Parrainage</a></li>
                <li><a href="leaderboard.php" class="nav-link active">🏆 Classement</a></li>
                <li><a href="withdraw.php" class="nav-link">Retrait</a></li>
                <li class="nav-balance"><?= number_format($user['balance']) ?> FCFA</li>
                <li><a href="logout.php" class="nav-logout">Déconnexion</a></li>
                <li><a href="how-it-works.php" class="nav-link">📚 Guide</a></li>
                <?php else: ?>
                <li><a href="index.php" class="nav-link">Accueil</a></li>
                <li><a href="leaderboard.php" class="nav-link active">🏆 Classement</a></li>
                <li><a href="login.php" class="nav-link">Connexion</a></li>
                <li><a href="register.php" class="nav-register">S'inscrire</a></li>
                <li><a href="how-it-works.php" class="nav-link">📚 Guide</a></li>
                <?php endif; ?>
        </ul>
    </div>
</nav>

    <div class="container">
        <div class="header">
            <h1>🏆 Classement des meilleurs gagneurs</h1>
            <p>Les champions de PubWatch Pro</p>
        </div>

        <?php if(!empty($topUsers)): ?>
        <!-- Podium pour les 3 premiers -->
        <div class="podium">
            <?php if(isset($topUsers[1])): ?>
            <div class="podium-item podium-2">
                <div class="crown">🥈</div>
                <div class="podium-name"><?= htmlspecialchars($topUsers[1]['username']) ?></div>
                <div class="podium-amount"><?= number_format($topUsers[1]['total_earned']) ?> FCFA</div>
                <div><?= $topUsers[1]['videos_watched'] ?> vidéos</div>
            </div>
            <?php endif; ?>

            <?php if(isset($topUsers[0])): ?>
            <div class="podium-item podium-1">
                <div class="crown">👑</div>
                <div class="podium-name"><?= htmlspecialchars($topUsers[0]['username']) ?></div>
                <div class="podium-amount"><?= number_format($topUsers[0]['total_earned']) ?> FCFA</div>
                <div><?= $topUsers[0]['videos_watched'] ?> vidéos</div>
            </div>
            <?php endif; ?>

            <?php if(isset($topUsers[2])): ?>
            <div class="podium-item podium-3">
                <div class="crown">🥉</div>
                <div class="podium-name"><?= htmlspecialchars($topUsers[2]['username']) ?></div>
                <div class="podium-amount"><?= number_format($topUsers[2]['total_earned']) ?> FCFA</div>
                <div><?= $topUsers[2]['videos_watched'] ?> vidéos</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Tableau complet -->
        <div class="leaderboard">
            <table>
                <thead>
                    <tr>
                        <th>Rang</th>
                        <th>Utilisateur</th>
                        <th>Gains totaux</th>
                        <th>Vidéos</th>
                        <th>Membre depuis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($topUsers as $index => $u): ?>
                    <tr>
                        <td>
                            <span class="medal <?= $index < 3 ? 'rank-' . ($index+1) : '' ?>">
                                <?= $index + 1 ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><strong><?= number_format($u['total_earned']) ?> FCFA</strong></td>
                        <td><?= $u['videos_watched'] ?></td>
                        <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if($user): ?>
        <!-- Position de l'utilisateur connecté -->
        <?php
        $rankQuery = "SELECT COUNT(*) + 1 as rank 
                     FROM users 
                     WHERE total_earned > (SELECT total_earned FROM users WHERE id = :user_id)";
        $rankStmt = $db->prepare($rankQuery);
        $rankStmt->bindParam(":user_id", $user['id']);
        $rankStmt->execute();
        $userRank = $rankStmt->fetch(PDO::FETCH_ASSOC);
        ?>
        <div class="your-rank">
            <strong>Votre position :</strong> #<?= $userRank['rank'] ?> sur tous les utilisateurs
        </div>
        <?php endif; ?>

        <?php else: ?>
        <p>Aucun utilisateur pour le moment</p>
        <?php endif; ?>
    </div>
</body>
</html>