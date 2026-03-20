<?php
// admin/stats.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer les stats générales
$stats = [];

// 1. Évolution des inscriptions (30 derniers jours)
$users_query = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
$users_stmt = $db->query($users_query);
$user_stats = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Évolution des gains (30 derniers jours)
$earnings_query = "SELECT DATE(watched_at) as date, COUNT(*) as views, COALESCE(SUM(reward_earned), 0) as total
                   FROM watch_history 
                   WHERE watched_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                   GROUP BY DATE(watched_at)
                   ORDER BY date DESC";
$earnings_stmt = $db->query($earnings_query);
$earning_stats = $earnings_stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Top 10 des utilisateurs
$top_users_query = "SELECT username, total_earned, videos_watched, created_at
                    FROM users 
                    WHERE total_earned > 0
                    ORDER BY total_earned DESC 
                    LIMIT 10";
$top_users = $db->query($top_users_query)->fetchAll(PDO::FETCH_ASSOC);

// 4. Stats par méthode de paiement
$payment_query = "SELECT payment_method, COUNT(*) as count, COALESCE(SUM(amount), 0) as total
                  FROM withdrawals 
                  WHERE status = 'COMPLETED'
                  GROUP BY payment_method";
$payment_stats = $db->query($payment_query)->fetchAll(PDO::FETCH_ASSOC);

// 5. Stats globales avec des COALESCE pour éviter les NULL
$global_query = "SELECT 
                    (SELECT COUNT(*) FROM users) as total_users,
                    (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today,
                    (SELECT COUNT(*) FROM watch_history) as total_views,
                    (SELECT COUNT(*) FROM watch_history WHERE DATE(watched_at) = CURDATE()) as views_today,
                    (SELECT COALESCE(SUM(reward_earned), 0) FROM watch_history) as total_earned,
                    (SELECT COALESCE(SUM(reward_earned), 0) FROM watch_history WHERE DATE(watched_at) = CURDATE()) as earned_today,
                    (SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'COMPLETED') as total_paid,
                    (SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE status = 'PENDING') as pending_paid";
$global_stmt = $db->query($global_query);
$global = $global_stmt->fetch(PDO::FETCH_ASSOC);

// Sécuriser les valeurs NULL
if (!$global) {
    $global = [
        'total_users' => 0,
        'new_users_today' => 0,
        'total_views' => 0,
        'views_today' => 0,
        'total_earned' => 0,
        'earned_today' => 0,
        'total_paid' => 0,
        'pending_paid' => 0
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Statistiques - Admin</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .stat-card h3 { color: #666; font-size: 0.9em; margin-bottom: 10px; text-transform: uppercase; }
        .stat-value { font-size: 2em; font-weight: bold; color: #333; }
        .stat-change { color: #27ae60; font-size: 0.9em; margin-top: 5px; }
        
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .chart-card h2 { margin-bottom: 20px; font-size: 1.2em; color: #333; }
        
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-container h2 { margin-bottom: 20px; color: #333; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f7f7f7; font-weight: 600; }
        tr:hover { background: #f9f9f9; }
        
        .export-btn {
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        .export-btn:hover { opacity: 0.9; }
        
        .no-data {
            text-align: center;
            color: #999;
            padding: 40px;
            font-style: italic;
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            font-weight: bold;
        }
        .badge-primary { background: #667eea; color: white; }
    </style>
    <!-- Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li><a href="withdrawals.php"><span class="icon">💸</span> Retraits</a></li>
                <li><a href="stats.php" class="active"><span class="icon">📈</span> Statistiques</a></li>
                <li><a href="settings.php"><span class="icon">⚙️</span> Paramètres</a></li>
                <li><a href="video_renewals.php"><span class="icon">🔄</span> Renouvellements</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>📈 Statistiques détaillées</h1>
                <button class="export-btn" onclick="exportStats()">
                    📥 Exporter les données
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>👥 UTILISATEURS</h3>
                    <div class="stat-value"><?= number_format($global['total_users']) ?></div>
                    <div class="stat-change">+<?= $global['new_users_today'] ?> aujourd'hui</div>
                </div>
                <div class="stat-card">
                    <h3>🎬 VIDÉOS VISIONNÉES</h3>
                    <div class="stat-value"><?= number_format($global['total_views']) ?></div>
                    <div class="stat-change">+<?= $global['views_today'] ?> aujourd'hui</div>
                </div>
                <div class="stat-card">
                    <h3>💰 GAINS TOTAUX</h3>
                    <div class="stat-value"><?= number_format($global['total_earned']) ?> FCFA</div>
                    <div class="stat-change">+<?= number_format($global['earned_today']) ?> FCFA aujourd'hui</div>
                </div>
                <div class="stat-card">
                    <h3>💸 PAIEMENTS EFFECTUÉS</h3>
                    <div class="stat-value"><?= number_format($global['total_paid']) ?> FCFA</div>
                    <div class="stat-change"><?= number_format($global['pending_paid']) ?> FCFA en attente</div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="charts-container">
                <div class="chart-card">
                    <h2>📅 Évolution des inscriptions (30 jours)</h2>
                    <canvas id="usersChart" height="200"></canvas>
                </div>
                <div class="chart-card">
                    <h2>💰 Évolution des gains (30 jours)</h2>
                    <canvas id="earningsChart" height="200"></canvas>
                </div>
            </div>

            <!-- Top utilisateurs -->
            <div class="table-container">
                <h2>🏆 Top 10 des meilleurs gagneurs</h2>
                <?php if (empty($top_users)): ?>
                    <div class="no-data">
                        Aucun utilisateur n'a encore gagné de l'argent
                    </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Rang</th>
                            <th>Utilisateur</th>
                            <th>Gains totaux</th>
                            <th>Vidéos regardées</th>
                            <th>Membre depuis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($top_users as $index => $user): ?>
                        <tr>
                            <td>
                                <span class="badge badge-primary">#<?= $index + 1 ?></span>
                            </td>
                            <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                            <td><?= number_format($user['total_earned']) ?> FCFA</td>
                            <td><?= $user['videos_watched'] ?></td>
                            <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Statistiques par méthode de paiement -->
            <div class="table-container">
                <h2>💳 Répartition par méthode de paiement</h2>
                <?php if (empty($payment_stats)): ?>
                    <div class="no-data">
                        Aucun paiement n'a encore été effectué
                    </div>
                <?php else: 
                    $total_payments = array_sum(array_column($payment_stats, 'total'));
                ?>
                <table>
                    <thead>
                        <tr>
                            <th>Méthode</th>
                            <th>Nombre de retraits</th>
                            <th>Montant total</th>
                            <th>Pourcentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($payment_stats as $payment): 
                        $percentage = ($total_payments > 0) ? ($payment['total'] / $total_payments) * 100 : 0;
                        ?>
                        <tr>
                            <td><?= str_replace('_', ' ', $payment['payment_method']) ?></td>
                            <td><?= $payment['count'] ?></td>
                            <td><strong><?= number_format($payment['total']) ?> FCFA</strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 100px; height: 10px; background: #eee; border-radius: 5px;">
                                        <div style="width: <?= $percentage ?>%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 5px;"></div>
                                    </div>
                                    <span><?= round($percentage, 1) ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Préparer les données pour les graphiques
    <?php
    // Inverser les tableaux pour avoir l'ordre chronologique
    $user_labels = array_reverse(array_column($user_stats, 'date'));
    $user_counts = array_reverse(array_column($user_stats, 'count'));
    
    $earning_labels = array_reverse(array_column($earning_stats, 'date'));
    $earning_totals = array_reverse(array_column($earning_stats, 'total'));
    ?>
    
    // Graphique des inscriptions
    const usersCtx = document.getElementById('usersChart').getContext('2d');
    new Chart(usersCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($user_labels) ?>,
            datasets: [{
                label: 'Nouveaux utilisateurs',
                data: <?= json_encode($user_counts) ?>,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // Graphique des gains
    const earningsCtx = document.getElementById('earningsChart').getContext('2d');
    new Chart(earningsCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($earning_labels) ?>,
            datasets: [{
                label: 'Gains (FCFA)',
                data: <?= json_encode($earning_totals) ?>,
                backgroundColor: 'rgba(102, 126, 234, 0.5)',
                borderColor: '#667eea',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' FCFA';
                        }
                    }
                }
            }
        }
    });

    function exportStats() {
        // Créer un CSV avec les stats
        let csv = "Date,Inscriptions,Gains (FCFA)\n";
        
        <?php
        // Créer un tableau associatif pour les gains par date
        $earnings_by_date = [];
        foreach ($earning_stats as $earning) {
            $earnings_by_date[$earning['date']] = $earning['total'];
        }
        
        // Pour chaque date d'inscription, ajouter les gains correspondants
        foreach ($user_stats as $stat):
            $date = $stat['date'];
            $users = $stat['count'];
            $earnings = isset($earnings_by_date[$date]) ? $earnings_by_date[$date] : 0;
        ?>
        csv += "<?= $date ?>,<?= $users ?>,<?= $earnings ?>\n";
        <?php endforeach; ?>
        
        // Télécharger le fichier
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'statistiques_' + new Date().toISOString().slice(0,10) + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    </script>
</body>
</html>