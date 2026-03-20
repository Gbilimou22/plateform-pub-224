<?php
// index.php
session_start();
require_once 'includes/Auth.php';
$user = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PubWatch Pro - Gagnez en regardant des pubs</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .navbar { background: white; padding: 15px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .nav-logo { font-size: 1.5em; font-weight: bold; color: #667eea; text-decoration: none; }
        .nav-menu { display: flex; list-style: none; gap: 20px; align-items: center; }
        .nav-link { color: #333; text-decoration: none; }
        .nav-register { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 20px; border-radius: 5px; text-decoration: none; }
        .nav-balance { background: #27ae60; color: white; padding: 5px 15px; border-radius: 20px; }
        .container { max-width: 1200px; margin: 50px auto; padding: 0 20px; text-align: center; color: white; }
        .hero h1 { font-size: 2.5em; margin-bottom: 20px; }
        .hero p { font-size: 1.2em; margin-bottom: 30px; }
        .cta-button { display: inline-block; background: white; color: #667eea; padding: 15px 40px; border-radius: 50px; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
   <!-- Dans index.php - Remplacer la navbar existante -->
<nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">💰 PubWatch Pro</a>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">Accueil</a></li>
            
            <?php if($user): ?>
                <li><a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="videos.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'videos.php' ? 'active' : '' ?>">Vidéos</a></li>
                <li><a href="referrals.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'referrals.php' ? 'active' : '' ?>">Parrainage</a></li>
                <li><a href="leaderboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'leaderboard.php' ? 'active' : '' ?>">🏆 Classement</a></li>
                <li><a href="withdraw.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'withdraw.php' ? 'active' : '' ?>">Retrait</a></li>
                <li class="nav-balance"><?= number_format($user['balance'] ?? 0) ?> FCFA</li>
                <li><a href="logout.php" class="nav-logout">Déconnexion</a></li>
                <li><a href="how-it-works.php" class="nav-link">📚 Guide</a></li>
                <?php else: ?>
                <li><a href="login.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : '' ?>">Connexion</a></li>
                <li><a href="register.php" class="nav-register">S'inscrire</a></li>
                <li><a href="how-it-works.php" class="nav-link">📚 Guide</a></li>
                <?php endif; ?>
        </ul>
    </div>
</nav>

<style>
/* Ajouter ce style pour le menu actif */
.nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important;
    padding: 8px 15px;
    border-radius: 5px;
}
.nav-logout {
    background: #e74c3c;
    color: white;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
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

    <div class="container">
        <div class="hero">
            <h1>Gagnez de l'argent en regardant des publicités !</h1>
            <p>💰 100 000 FCFA par vidéo • Paiement instantané • 100% Gratuit</p>
            <?php if(!$user): ?>
                <a href="register.php" class="cta-button">Commencer maintenant</a>
            <?php else: ?>
                <a href="videos.php" class="cta-button">Voir les vidéos</a>
            <?php endif; ?>
        </div>
    </div>
    
</body>
</html>