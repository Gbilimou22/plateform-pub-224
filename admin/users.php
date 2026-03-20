<?php
// admin/users.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer tous les utilisateurs
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM watch_history WHERE user_id = u.id) as total_watches,
          (SELECT COUNT(*) FROM withdrawals WHERE user_id = u.id) as total_withdrawals
          FROM users u 
          ORDER BY u.id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestion Utilisateurs - Admin</title>
    <style>
        /* Mêmes styles que index.php */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; }
        .container { display: flex; min-height: 100vh; }
        
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
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        .search-box input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            width: 300px;
        }
        .search-box button {
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
        }
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            margin: 0 2px;
        }
        .btn-view { background: #3498db; color: white; }
        .btn-edit { background: #f39c12; color: white; }
        .btn-ban { background: #e74c3c; color: white; }
        
        .status-active {
            background: #27ae60;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
        }
        .status-banned {
            background: #e74c3c;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
        }
        
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; }
            .search-box input { width: 100%; }
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
                <li><a href="users.php" class="active"><span class="icon">👥</span> Utilisateurs</a></li>
                <li><a href="videos.php"><span class="icon">🎬</span> Vidéos</a></li>
                <li><a href="withdrawals.php"><span class="icon">💸</span> Retraits</a></li>
                <li><a href="stats.php"><span class="icon">📈</span> Statistiques</a></li>
                <li><a href="settings.php"><span class="icon">⚙️</span> Paramètres</a></li>
                <li><a href="../index.php" target="_blank"><span class="icon">🌐</span> Voir le site</a></li>
                <li><a href="video_renewals.php"><span class="icon">🔄</span> Renouvellements</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>👥 Gestion des utilisateurs</h1>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Rechercher un utilisateur...">
                    <button onclick="searchUsers()">Rechercher</button>
                </div>
            </div>

            <div class="table-container">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pseudo</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Solde</th>
                            <th>Gains totaux</th>
                            <th>Vidéos</th>
                            <th>Retraits</th>
                            <th>Inscription</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td>#<?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= $user['email'] ?></td>
                            <td><?= $user['phone'] ?></td>
                            <td><strong><?= number_format($user['balance']) ?> FCFA</strong></td>
                            <td><?= number_format($user['total_earned']) ?> FCFA</td>
                            <td><?= $user['videos_watched'] ?></td>
                            <td><?= $user['total_withdrawals'] ?></td>
                            <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <span class="status-<?= $user['is_active'] ? 'active' : 'banned' ?>">
                                    <?= $user['is_active'] ? 'Actif' : 'Banni' ?>
                                </span>
                            </td>
                            <td>
                                <a href="user_view.php?id=<?= $user['id'] ?>" class="btn btn-view">Voir</a>
                                <a href="user_edit.php?id=<?= $user['id'] ?>" class="btn btn-edit">Éditer</a>
                                <?php if($user['is_active']): ?>
                                    <a href="user_ban.php?id=<?= $user['id'] ?>" class="btn btn-ban" onclick="return confirm('Bannir cet utilisateur ?')">Bannir</a>
                                <?php else: ?>
                                    <a href="user_unban.php?id=<?= $user['id'] ?>" class="btn btn-view" style="background:#27ae60;">Réactiver</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function searchUsers() {
        var input = document.getElementById('searchInput');
        var filter = input.value.toUpperCase();
        var table = document.getElementById('usersTable');
        var tr = table.getElementsByTagName('tr');

        for (var i = 1; i < tr.length; i++) {
            var td = tr[i].getElementsByTagName('td');
            var found = false;
            for (var j = 0; j < td.length; j++) {
                if (td[j]) {
                    var txtValue = td[j].textContent || td[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
            }
            tr[i].style.display = found ? '' : 'none';
        }
    }
    </script>
</body>
</html>