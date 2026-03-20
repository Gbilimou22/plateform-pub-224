<?php
// admin/index.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
require_once '../includes/Admin.php';

$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db);

$stats = $admin->getDashboardStats();
$recentUsers = $admin->getRecentUsers(5);
$recentWithdrawals = $admin->getRecentWithdrawals(5);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - PubWatch Pro</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        .container { display: flex; min-height: 100vh; }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .sidebar-header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header h2 { margin-bottom: 5px; }
        .sidebar-menu {
            list-style: none;
            margin-top: 30px;
        }
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        .sidebar-menu a {
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            display: block;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: rgba(255,255,255,0.1);
        }
        .sidebar-menu .icon { margin-right: 10px; }
        
        /* Main content */
        .main-content {
            flex: 1;
            padding: 20px;
        }
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
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        
        /* Stats cards */
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .stat-change {
            color: #27ae60;
            font-size: 0.9em;
        }
        
        /* Tables */
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-container h2 {
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f7f7f7;
            color: #666;
        }
        .status-pending {
            background: #f39c12;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
        }
        .status-completed {
            background: #27ae60;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
        }
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
        }
        .btn-view { background: #3498db; color: white; }
        .btn-edit { background: #f39c12; color: white; }
        
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; }
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
                <li><a href="index.php" class="active"><span class="icon">📊</span> Dashboard</a></li>
                <li><a href="users.php"><span class="icon">👥</span> Utilisateurs</a></li>
                <li><a href="videos.php"><span class="icon">🎬</span> Vidéos</a></li>
                <li><a href="youtube_import.php"><span class="icon">📺</span> Import YouTube</a></li>
                <li><a href="video_renewals.php"><span class="icon">🔄</span> Renouvellements</a></li>
                <li><a href="withdrawals.php"><span class="icon">💸</span> Retraits</a></li>
                <li><a href="stats.php"><span class="icon">📈</span> Statistiques</a></li>
                <li><a href="settings.php"><span class="icon">⚙️</span> Paramètres</a></li>
                <li><a href="../index.php" target="_blank"><span class="icon">🌐</span> Voir le site</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
                <div class="user-info">
                    <span>👤 <?= $_SESSION['admin_username'] ?> (<?= $_SESSION['admin_role'] ?>)</span>
                    <a href="logout.php" class="logout-btn">Déconnexion</a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>UTILISATEURS TOTAL</h3>
                    <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                    <div class="stat-change">+<?= $stats['new_users_today'] ?> aujourd'hui</div>
                </div>
                <div class="stat-card">
                    <h3>UTILISATEURS ACTIFS</h3>
                    <div class="stat-value"><?= number_format($stats['active_users_today']) ?></div>
                    <div class="stat-change">aujourd'hui</div>
                </div>
                <div class="stat-card">
                    <h3>GAINS TOTAUX</h3>
                    <div class="stat-value"><?= number_format($stats['total_earnings']) ?> FCFA</div>
                    <div class="stat-change">+<?= number_format($stats['today_earnings']) ?> FCFA aujourd'hui</div>
                </div>
                <div class="stat-card">
                    <h3>VIDÉOS REGARDÉES</h3>
                    <div class="stat-value"><?= number_format($stats['total_watches']) ?></div>
                    <div class="stat-change">+<?= $stats['today_watches'] ?> aujourd'hui</div>
                </div>
                <div class="stat-card">
                    <h3>RETRAITS EN ATTENTE</h3>
                    <div class="stat-value"><?= number_format($stats['pending_withdrawals']) ?></div>
                    <div class="stat-change"><?= number_format($stats['pending_amount']) ?> FCFA à payer</div>
                </div>
                <div class="stat-card">
                    <h3>TOTAL PAYÉ</h3>
                    <div class="stat-value"><?= number_format($stats['total_paid']) ?> FCFA</div>
                    <div class="stat-change">depuis le début</div>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="table-container">
                <h2>👥 Derniers utilisateurs inscrits</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pseudo</th>
                            <th>Email</th>
                            <th>Solde</th>
                            <th>Gains totaux</th>
                            <th>Vidéos</th>
                            <th>Inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentUsers as $user): ?>
                        <tr>
                            <td>#<?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= $user['email'] ?></td>
                            <td><?= number_format($user['balance']) ?> FCFA</td>
                            <td><?= number_format($user['total_earned']) ?> FCFA</td>
                            <td><?= $user['videos_watched'] ?></td>
                            <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <a href="users.php?view=<?= $user['id'] ?>" class="btn btn-view">Voir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Withdrawals -->
            <div class="table-container">
                <h2>💸 Dernières demandes de retrait</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Utilisateur</th>
                            <th>Montant</th>
                            <th>Méthode</th>
                            <th>Téléphone</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentWithdrawals as $w): ?>
                        <tr>
                            <td>#<?= $w['id'] ?></td>
                            <td><?= htmlspecialchars($w['username']) ?></td>
                            <td><strong><?= number_format($w['amount']) ?> FCFA</strong></td>
                            <td><?= str_replace('_', ' ', $w['payment_method']) ?></td>
                            <td><?= $w['phone_number'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($w['requested_at'])) ?></td>
                            <td>
                                <span class="status-<?= strtolower($w['status']) ?>">
                                    <?= $w['status'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="withdrawals.php?process=<?= $w['id'] ?>" class="btn btn-edit">Traiter</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>