<?php
// admin/withdrawals.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Traiter un retrait
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdrawal_id'])) {
    $withdrawal_id = $_POST['withdrawal_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $query = "UPDATE withdrawals SET status = 'COMPLETED', processed_at = NOW() WHERE id = :id";
    } else if ($action === 'reject') {
        $query = "UPDATE withdrawals SET status = 'REJECTED', processed_at = NOW() WHERE id = :id";
        // Rembourser l'utilisateur
        $getAmount = "SELECT user_id, amount FROM withdrawals WHERE id = :id";
        $stmt = $db->prepare($getAmount);
        $stmt->bindParam(":id", $withdrawal_id);
        $stmt->execute();
        $w = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($w) {
            $refund = "UPDATE users SET balance = balance + :amount WHERE id = :user_id";
            $refundStmt = $db->prepare($refund);
            $refundStmt->bindParam(":amount", $w['amount']);
            $refundStmt->bindParam(":user_id", $w['user_id']);
            $refundStmt->execute();
        }
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $withdrawal_id);
    $stmt->execute();
}

// Récupérer les retraits
$query = "SELECT w.*, u.username, u.email, u.phone 
          FROM withdrawals w
          JOIN users u ON w.user_id = u.id
          ORDER BY w.requested_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestion Retraits - Admin</title>
    <style>
        /* Mêmes styles que précédemment */
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
        
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-mini-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-mini-value { font-size: 1.5em; font-weight: bold; color: #667eea; }
        
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f7f7f7; }
        
        .status-pending { background: #f39c12; color: white; padding: 3px 8px; border-radius: 3px; }
        .status-completed { background: #27ae60; color: white; padding: 3px 8px; border-radius: 3px; }
        .status-rejected { background: #e74c3c; color: white; padding: 3px 8px; border-radius: 3px; }
        
        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
            margin: 0 2px;
        }
        .btn-approve { background: #27ae60; color: white; }
        .btn-reject { background: #e74c3c; color: white; }
        .btn-view { background: #3498db; color: white; }
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
                <li><a href="withdrawals.php" class="active"><span class="icon">💸</span> Retraits</a></li>
                <li><a href="stats.php"><span class="icon">📈</span> Statistiques</a></li>
                <li><a href="settings.php"><span class="icon">⚙️</span> Paramètres</a></li>
                <li><a href="video_renewals.php"><span class="icon">🔄</span> Renouvellements</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>💸 Gestion des Retraits</h1>
            </div>

            <?php
            // Stats rapides
            $pending = array_filter($withdrawals, function($w) { return $w['status'] == 'PENDING'; });
            $completed = array_filter($withdrawals, function($w) { return $w['status'] == 'COMPLETED'; });
            $total_pending = array_sum(array_column($pending, 'amount'));
            $total_completed = array_sum(array_column($completed, 'amount'));
            ?>
            
            <div class="stats-mini">
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= count($pending) ?></div>
                    <div>En attente</div>
                    <small><?= number_format($total_pending) ?> FCFA</small>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= count($completed) ?></div>
                    <div>Traités</div>
                    <small><?= number_format($total_completed) ?> FCFA</small>
                </div>
                <div class="stat-mini-card">
                    <div class="stat-mini-value"><?= count($withdrawals) ?></div>
                    <div>Total</div>
                </div>
            </div>
        
            <div class="table-container">
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
                        <?php foreach($withdrawals as $w): ?>
                        <tr>
                            <td>#<?= $w['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($w['username']) ?></strong><br>
                                <small><?= $w['email'] ?></small>
                            </td>
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
                                <?php if($w['status'] == 'PENDING'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-approve" onclick="return confirm('Confirmer le paiement ?')">✅ Approuver</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn btn-reject" onclick="return confirm('Rejeter cette demande ?')">❌ Rejeter</button>
                                </form>
                                <?php else: ?>
                                <span class="btn btn-view">Voir</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <!-- Dans la page de gestion des retraits, ajoute cette section -->
<div class="payment-section" style="background: white; padding: 20px; border-radius: 10px; margin-top: 20px;">
    <h2>💳 Traiter un paiement</h2>
    
    <form id="paymentForm" onsubmit="processPayment(event)">
        <div class="form-group">
            <label>ID du retrait</label>
            <input type="text" id="withdrawal_id" readonly required>
        </div>
        
        <div class="form-group">
            <label>Montant</label>
            <input type="text" id="amount" readonly>
        </div>
        
        <div class="form-group">
            <label>Téléphone bénéficiaire</label>
            <input type="tel" id="phone" placeholder="0708091011" required>
        </div>
        
        <div class="form-group">
            <label>Opérateur</label>
            <select id="operator" required>
                <option value="">Sélectionner...</option>
                <option value="ORANGE_MONEY">📱 Orange Money</option>
                <option value="MOOV_MONEY">📱 Moov Money</option>
                <option value="WAVE">🌊 Wave</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">💰 Envoyer le paiement</button>
    </form>
</div>

<script>
function processPayment(event) {
    event.preventDefault();
    
    const formData = new FormData();
    formData.append('withdrawal_id', document.getElementById('withdrawal_id').value);
    formData.append('phone', document.getElementById('phone').value);
    formData.append('operator', document.getElementById('operator').value);
    
    fetch('api/process_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Paiement initié : ' + data.message);
            location.reload();
        } else {
            alert('❌ Erreur : ' + data.error);
        }
    })
    .catch(error => {
        alert('Erreur réseau : ' + error);
    });
}

function loadWithdrawal(id, amount, phone) {
    document.getElementById('withdrawal_id').value = id;
    document.getElementById('amount').value = amount + ' FCFA';
    document.getElementById('phone').value = phone;
}
</script>
        </div>
        
    </div>
</body>
</html>