<?php
// withdraw.php
session_start();
require_once 'includes/Auth.php';
require_once 'config/database.php';
require_once 'includes/User.php';
require_once 'includes/Withdrawal.php';

Auth::requireLogin();

$database = new Database();
$db = $database->getConnection();

$userModel = new User($db);
$withdrawalModel = new Withdrawal($db);

$user = $userModel->getUserById($_SESSION['user_id']);
$withdrawals = $withdrawalModel->getUserWithdrawals($_SESSION['user_id']);

$error = '';
$success = '';
$MIN_WITHDRAWAL = 500000; // Minimum 500 000 FCFA

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount']);
    $payment_method = $_POST['payment_method'];
    $phone_number = $_POST['phone_number'];
    
    if ($amount < $MIN_WITHDRAWAL) {
        $error = "Le montant minimum est de " . number_format($MIN_WITHDRAWAL) . " FCFA";
    } elseif ($amount > $user['balance']) {
        $error = "Solde insuffisant";
    } else {
        $db->beginTransaction();
        try {
            // Créer la demande
            $withdrawalModel->create($user['id'], $amount, $payment_method, $phone_number);
            
            // Déduire du solde
            $userModel->updateBalance($user['id'], -$amount);
            
            $db->commit();
            
            // Mettre à jour la session
            $updatedUser = $userModel->getUserById($user['id']);
            $_SESSION['balance'] = $updatedUser['balance'];
            
            $success = "Demande de retrait envoyée avec succès !";
            
            // Recharger les données
            $user = $updatedUser;
            $withdrawals = $withdrawalModel->getUserWithdrawals($_SESSION['user_id']);
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Erreur lors de la demande";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Retrait - PubWatch Pro</title>
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
        
        .balance-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .balance-card .amount {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .withdraw-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        
        .form-group small {
            color: #666;
            font-size: 0.85em;
        }
        
        .withdraw-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
        }
        
        .withdraw-btn:hover:not(:disabled) {
            opacity: 0.9;
        }
        
        .withdraw-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .history {
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
        
        .status-pending {
            background: #f39c12;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
        }
        
        .status-completed {
            background: #27ae60;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
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
    <nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">💰 PubWatch Pro</a>
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            <li><a href="videos.php" class="nav-link">Vidéos</a></li>
            <li><a href="referrals.php" class="nav-link">Parrainage</a></li>
            <li><a href="leaderboard.php" class="nav-link">🏆 Classement</a></li>
            <li><a href="withdraw.php" class="nav-link active">Retrait</a></li>
            <li class="nav-balance"><?= number_format($user['balance']) ?> FCFA</li>
            <li><a href="logout.php" class="nav-logout">Déconnexion</a></li>
            <li><a href="how-it-works.php" class="nav-link">📚 Guide</a></li>
        </ul>
    </div>
</nav>

    <div class="container">
        <div class="header">
            <h1>💸 Retrait de vos gains</h1>
        </div>

        <div class="balance-card">
            <p>Votre solde disponible</p>
            <div class="amount"><?= number_format($user['balance']) ?> FCFA</div>
            <p>Minimum de retrait: <?= number_format($MIN_WITHDRAWAL) ?> FCFA</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="info-box">
            <strong>ℹ️ Information:</strong> Les retraits sont traités sous 24-48h ouvrées.
            Le paiement est effectué sur votre compte mobile money.
        </div>

        <div class="withdraw-form">
            <h2>Faire une demande de retrait</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Montant (FCFA)</label>
                    <input type="number" name="amount" min="<?= $MIN_WITHDRAWAL ?>" max="<?= $user['balance'] ?>" required 
                           placeholder="Montant minimum <?= number_format($MIN_WITHDRAWAL) ?> FCFA">
                    <small>Minimum: <?= number_format($MIN_WITHDRAWAL) ?> FCFA</small>
                </div>

                <div class="form-group">
                    <label>Méthode de paiement</label>
                    <select name="payment_method" required>
                        <option value="ORANGE_MONEY">Orange Money</option>
                        <option value="MOOV_MONEY">Moov Money</option>
                        <option value="WAVE">Wave</option>
                        <option value="MTN_MONEY">MTN Money</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Numéro de téléphone</label>
                    <input type="tel" name="phone_number" pattern="[0-9]{10}" required 
                           placeholder="Ex: 0708091011">
                    <small>Numéro à 10 chiffres</small>
                </div>

                <button type="submit" class="withdraw-btn" <?= $user['balance'] < $MIN_WITHDRAWAL ? 'disabled' : '' ?>>
                    Demander le retrait
                </button>
            </form>
        </div>

        <?php if(!empty($withdrawals)): ?>
        <div class="history">
            <h2>Historique des retraits</h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Montant</th>
                        <th>Méthode</th>
                        <th>Téléphone</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($withdrawals as $w): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($w['requested_at'])) ?></td>
                        <td><?= number_format($w['amount']) ?> FCFA</td>
                        <td><?= str_replace('_', ' ', $w['payment_method']) ?></td>
                        <td><?= $w['phone_number'] ?></td>
                        <td>
                            <span class="status-<?= strtolower($w['status']) ?>">
                                <?= $w['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script>
    // Validation en temps réel
    document.querySelector('input[name="amount"]').addEventListener('input', function() {
        let amount = parseFloat(this.value);
        let minAmount = <?= $MIN_WITHDRAWAL ?>;
        let balance = <?= $user['balance'] ?>;
        let submitBtn = document.querySelector('.withdraw-btn');
        
        if (amount < minAmount || amount > balance || isNaN(amount)) {
            submitBtn.disabled = true;
        } else {
            submitBtn.disabled = false;
        }
    });
    </script>
</body>
</html>