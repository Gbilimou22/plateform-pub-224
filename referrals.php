<?php
// referrals.php
session_start();
require_once 'includes/Auth.php';
require_once 'config/database.php';
require_once 'includes/User.php';

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

$user = $userModel->getUserById($_SESSION['user_id']);

// Récupérer les filleuls
$query = "SELECT id, username, email, created_at, total_earned 
          FROM users WHERE referred_by = :user_id 
          ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user['id']);
$stmt->execute();
$referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les gains du parrainage (10% des gains des filleuls)
$referral_earnings = 0;
foreach($referrals as $ref) {
    $referral_earnings += $ref['total_earned'] * 0.1; // 10% de commission
}

// Lien de parrainage
$referral_link = "http://" . $_SERVER['HTTP_HOST'] . "/register.php?ref=" . $user['referral_code'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Parrainage - PubWatch Pro</title>
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
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .referral-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .referral-banner h1 {
            font-size: 2.5em;
            margin-bottom: 15px;
        }
        
        .referral-banner p {
            font-size: 1.2em;
            margin-bottom: 20px;
        }
        
        .commission-badge {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 10px;
            display: inline-block;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stat-value {
            font-size: 2.2em;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        
        .referral-link-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .link-container {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        
        .link-container input {
            flex: 1;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1em;
        }
        
        .copy-btn {
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .copy-btn:hover {
            opacity: 0.9;
        }
        
        .share-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        
        .share-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        
        .whatsapp { background: #25D366; }
        .facebook { background: #1877F2; }
        .twitter { background: #1DA1F2; }
        .telegram { background: #0088cc; }
        
        .referrals-list {
            background: white;
            padding: 30px;
            border-radius: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f7f7f7;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .level-badge {
            background: #27ae60;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.85em;
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
            <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            <li><a href="videos.php" class="nav-link">Vidéos</a></li>
            <li><a href="referrals.php" class="nav-link active">Parrainage</a></li>
            <li><a href="leaderboard.php" class="nav-link">🏆 Classement</a></li>
            <li><a href="withdraw.php" class="nav-link">Retrait</a></li>
            <li class="nav-balance"><?= number_format($user['balance']) ?> FCFA</li>
            <li><a href="logout.php" class="nav-logout">Déconnexion</a></li>
            <li><a href="how-it-works.php" class="nav-link">📚 Guide</a></li>
        </ul>
    </div>
</nav>

    <div class="container">
        <div class="referral-banner">
            <h1>🤝 Programme de Parrainage</h1>
            <p>Invitez vos amis et gagnez 10% de leurs gains à vie !</p>
            <div class="commission-badge">
                <span style="font-size: 2em;">💰 10%</span><br>
                de commission sur les gains de vos filleuls
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Vos filleuls</h3>
                <div class="stat-value"><?= count($referrals) ?></div>
                <p>personnes parrainées</p>
            </div>
            <div class="stat-card">
                <h3>Gains parrainage</h3>
                <div class="stat-value"><?= number_format($referral_earnings) ?> FCFA</div>
                <p>10% des gains de vos filleuls</p>
            </div>
            <div class="stat-card">
                <h3>Potentiel</h3>
                <div class="stat-value">∞</div>
                <p>Plus vous parrainez, plus vous gagnez</p>
            </div>
        </div>
        <!-- Ajouter après les statistiques -->
    <div style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
        <h2 style="margin-bottom: 15px;">👑 Programme Premium</h2>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; text-align: center;">
            <div>
                <div style="font-size: 2em;">🥉</div>
                <h3>Bronze</h3>
                <p>5 filleuls</p>
                <p>Commission: 10%</p>
            </div>
            <div>
                <div style="font-size: 2em;">🥈</div>
                <h3>Argent</h3>
                <p>20 filleuls</p>
                <p>Commission: 15%</p>
            </div>
            <div>
                <div style="font-size: 2em;">🥇</div>
                <h3>Or</h3>
                <p>50+ filleuls</p>
                <p>Commission: 20%</p>
            </div>
        </div>
    </div>
        <div class="referral-link-box">
            <h2>🔗 Votre lien de parrainage</h2>
            <p>Partagez ce lien avec vos amis. Quand ils s'inscrivent, ils deviennent vos filleuls !</p>
            
            <div class="link-container">
                <input type="text" id="referralLink" value="<?= $referral_link ?>" readonly>
                <button class="copy-btn" onclick="copyLink()">Copier</button>
            </div>

            
        <div class="share-buttons">
            <a href="#" onclick="trackShare('whatsapp', 'https://wa.me/?text=<?= urlencode('Rejoins PubWatch Pro et gagne de l\'argent en regardant des pubs ! Inscris-toi avec mon lien : ' . $referral_link) ?>'); return false;" 
            class="share-btn whatsapp">📱 WhatsApp</a>
            <a href="#" onclick="trackShare('facebook', 'https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($referral_link) ?>'); return false;" 
            class="share-btn facebook">📘 Facebook</a>
            <a href="#" onclick="trackShare('telegram', 'https://t.me/share/url?url=<?= urlencode($referral_link) ?>&text=<?= urlencode('Gagne de l\'argent en regardant des pubs !') ?>'); return false;" 
            class="share-btn telegram">📲 Telegram</a>
        </div>
        </div>

        <div class="referrals-list">
            <h2>📋 Vos filleuls</h2>
            
            <?php if(empty($referrals)): ?>
                <div style="text-align: center; padding: 40px;">
                    <p style="font-size: 1.2em; color: #666;">Vous n'avez pas encore de filleuls</p>
                    <p>Partagez votre lien de parrainage pour commencer à gagner !</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Pseudo</th>
                            <th>Date d'inscription</th>
                            <th>Gains totaux</th>
                            <th>Votre commission (10%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($referrals as $ref): ?>
                        <tr>
                            <td><?= htmlspecialchars($ref['username']) ?></td>
                            <td><?= date('d/m/Y', strtotime($ref['created_at'])) ?></td>
                            <td><?= number_format($ref['total_earned']) ?> FCFA</td>
                            <td><?= number_format($ref['total_earned'] * 0.1) ?> FCFA</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="info-box" style="margin-top: 30px;">
            <h3>🎯 Comment ça marche ?</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Partagez votre lien de parrainage</li>
                <li>Vos amis s'inscrivent avec votre lien</li>
                <li>Vous gagnez 10% de TOUS leurs gains</li>
                <li>Les commissions sont créditées automatiquement</li>
                <li>Pas de limite de filleuls !</li>
            </ul>
        </div>
    </div>

    <script>
    function copyLink() {
        var copyText = document.getElementById("referralLink");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        document.execCommand("copy");
        
        var btn = document.querySelector('.copy-btn');
        var originalText = btn.innerHTML;
        btn.innerHTML = '✅ Copié !';
        setTimeout(function() {
            btn.innerHTML = originalText;
        }, 2000);
        // Ajouter cette fonction pour tracker les partages
function trackShare(platform, url) {
    // Ouvrir le lien de partage
    window.open(url, '_blank');
    
    // Enregistrer le partage
    fetch('api/track_share.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'platform=' + platform
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Vérifier les défis après le partage
            checkChallenges();
        }
    });
}

// Vérifier les défis
function checkChallenges() {
    fetch('api/check_challenges.php')
        .then(response => response.json())
        .then(data => {
            if (data.rewards_claimed && data.rewards_claimed.length > 0) {
                let message = '🎉 Félicitations ! Vous avez complété :\n';
                data.rewards_claimed.forEach(reward => {
                    message += `\n- ${reward.title} : ${reward.reward.toLocaleString()} FCFA`;
                });
                alert(message);
                location.reload();
            }
        });
    }
    }
    </script>
</body>
</html>