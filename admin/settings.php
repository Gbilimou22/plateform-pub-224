<?php
// admin/settings.php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer les paramètres actuels
$query = "SELECT * FROM settings ORDER BY setting_key";
$stmt = $db->query($query);
$settings = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Traitement du formulaire
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        foreach ($_POST as $key => $value) {
            if ($key != 'save_settings') {
                // Mettre à jour ou insérer
                $check = "SELECT * FROM settings WHERE setting_key = :key";
                $checkStmt = $db->prepare($check);
                $checkStmt->bindParam(":key", $key);
                $checkStmt->execute();
                
                if ($checkStmt->rowCount() > 0) {
                    $update = "UPDATE settings SET setting_value = :value WHERE setting_key = :key";
                    $stmt = $db->prepare($update);
                } else {
                    $update = "INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)";
                    $stmt = $db->prepare($update);
                }
                
                $stmt->bindParam(":key", $key);
                $stmt->bindParam(":value", $value);
                $stmt->execute();
            }
        }
        $message = "Paramètres enregistrés avec succès !";
    }
    
    if (isset($_POST['test_email'])) {
        // Test d'envoi d'email
        $to = $_POST['test_email_address'];
        $subject = "Test de configuration email";
        $message = "Cet email confirme que votre configuration email fonctionne correctement.";
        $headers = "From: " . ($settings['site_email'] ?? 'noreply@pubwatch.com');
        
        if (mail($to, $subject, $message, $headers)) {
            $message = "Email de test envoyé avec succès !";
        } else {
            $error = "Erreur lors de l'envoi de l'email";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Paramètres - Admin</title>
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
        
        .settings-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .settings-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .tab:hover { background: #f7f7f7; }
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .form-group {
            margin-bottom: 20px;
            max-width: 600px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        .form-group small {
            color: #666;
            font-size: 0.85em;
            margin-top: 5px;
            display: block;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            margin-right: 10px;
        }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn-success { background: #27ae60; color: white; }
        
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
        
        .card {
            background: #f7f7f7;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .card h3 { margin-bottom: 15px; color: #333; }
        
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
                <li><a href="users.php"><span class="icon">👥</span> Utilisateurs</a></li>
                <li><a href="videos.php"><span class="icon">🎬</span> Vidéos</a></li>
                <li><a href="withdrawals.php"><span class="icon">💸</span> Retraits</a></li>
                <li><a href="stats.php"><span class="icon">📈</span> Statistiques</a></li>
                <li><a href="settings.php" class="active"><span class="icon">⚙️</span> Paramètres</a></li>
                <li><a href="video_renewals.php"><span class="icon">🔄</span> Renouvellements</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>⚙️ Paramètres de la plateforme</h1>
            </div>

            <?php if($message): ?>
                <div class="message success"><?= $message ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="message error"><?= $error ?></div>
            <?php endif; ?>

            <div class="settings-container">
                <!-- Onglets -->
                <div class="settings-tabs">
                    <div class="tab active" onclick="showTab('general')">Général</div>
                    <div class="tab" onclick="showTab('payment')">Paiements</div>
                    <div class="tab" onclick="showTab('email')">Email</div>
                    <div class="tab" onclick="showTab('security')">Sécurité</div>
                    <div class="tab" onclick="showTab('limits')">Limites</div>
                </div>

                <form method="POST">
                    <!-- Onglet Général -->
                    <div id="general" class="tab-content active">
                        <h2 style="margin-bottom: 20px;">Paramètres généraux</h2>
                        
                        <div class="form-group">
                            <label>Nom du site</label>
                            <input type="text" name="site_name" value="<?= $settings['site_name'] ?? 'PubWatch Pro' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Description du site</label>
                            <textarea name="site_description" rows="3"><?= $settings['site_description'] ?? 'Gagnez de l\'argent en regardant des publicités' ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>URL du site</label>
                            <input type="url" name="site_url" value="<?= $settings['site_url'] ?? 'http://localhost/pub-watching-platform' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Email de contact</label>
                            <input type="email" name="contact_email" value="<?= $settings['contact_email'] ?? 'contact@pubwatch.com' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Langue par défaut</label>
                            <select name="default_language">
                                <option value="fr" <?= ($settings['default_language'] ?? 'fr') == 'fr' ? 'selected' : '' ?>>Français</option>
                                <option value="en" <?= ($settings['default_language'] ?? '') == 'en' ? 'selected' : '' ?>>English</option>
                            </select>
                        </div>
                    </div>

                    <!-- Onglet Paiements -->
                    <div id="payment" class="tab-content">
                        <h2 style="margin-bottom: 20px;">Paramètres des paiements</h2>
                        
                        <div class="two-columns">
                            <div class="card">
                                <h3>Orange Money</h3>
                                <div class="form-group">
                                    <label>Activer</label>
                                    <select name="orange_money_enabled">
                                        <option value="1" <?= ($settings['orange_money_enabled'] ?? '1') == '1' ? 'selected' : '' ?>>Oui</option>
                                        <option value="0" <?= ($settings['orange_money_enabled'] ?? '') == '0' ? 'selected' : '' ?>>Non</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Numéro de compte</label>
                                    <input type="text" name="orange_money_number" value="<?= $settings['orange_money_number'] ?? '0708091011' ?>">
                                </div>
                            </div>
                            
                            <div class="card">
                                <h3>Moov Money</h3>
                                <div class="form-group">
                                    <label>Activer</label>
                                    <select name="moov_money_enabled">
                                        <option value="1" <?= ($settings['moov_money_enabled'] ?? '1') == '1' ? 'selected' : '' ?>>Oui</option>
                                        <option value="0" <?= ($settings['moov_money_enabled'] ?? '') == '0' ? 'selected' : '' ?>>Non</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Numéro de compte</label>
                                    <input type="text" name="moov_money_number" value="<?= $settings['moov_money_number'] ?? '0708091011' ?>">
                                </div>
                            </div>
                            
                            <div class="card">
                                <h3>Wave</h3>
                                <div class="form-group">
                                    <label>Activer</label>
                                    <select name="wave_enabled">
                                        <option value="1" <?= ($settings['wave_enabled'] ?? '1') == '1' ? 'selected' : '' ?>>Oui</option>
                                        <option value="0" <?= ($settings['wave_enabled'] ?? '') == '0' ? 'selected' : '' ?>>Non</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Numéro de compte</label>
                                    <input type="text" name="wave_number" value="<?= $settings['wave_number'] ?? '0708091011' ?>">
                                </div>
                            </div>
                            
                            <div class="card">
                                <h3>MTN Money</h3>
                                <div class="form-group">
                                    <label>Activer</label>
                                    <select name="mtn_money_enabled">
                                        <option value="1" <?= ($settings['mtn_money_enabled'] ?? '1') == '1' ? 'selected' : '' ?>>Oui</option>
                                        <option value="0" <?= ($settings['mtn_money_enabled'] ?? '') == '0' ? 'selected' : '' ?>>Non</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Numéro de compte</label>
                                    <input type="text" name="mtn_money_number" value="<?= $settings['mtn_money_number'] ?? '0708091011' ?>">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Email -->
                    <div id="email" class="tab-content">
                        <h2 style="margin-bottom: 20px;">Configuration email</h2>
                        
                        <div class="form-group">
                            <label>Méthode d'envoi</label>
                            <select name="mail_method">
                                <option value="mail" <?= ($settings['mail_method'] ?? 'mail') == 'mail' ? 'selected' : '' ?>>PHP Mail</option>
                                <option value="smtp" <?= ($settings['mail_method'] ?? '') == 'smtp' ? 'selected' : '' ?>>SMTP</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Email expéditeur</label>
                            <input type="email" name="site_email" value="<?= $settings['site_email'] ?? 'noreply@pubwatch.com' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Nom de l'expéditeur</label>
                            <input type="text" name="site_email_name" value="<?= $settings['site_email_name'] ?? 'PubWatch Pro' ?>">
                        </div>
                        
                        <h3 style="margin: 30px 0 15px;">Configuration SMTP</h3>
                        
                        <div class="form-group">
                            <label>Hôte SMTP</label>
                            <input type="text" name="smtp_host" value="<?= $settings['smtp_host'] ?? 'smtp.gmail.com' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Port SMTP</label>
                            <input type="text" name="smtp_port" value="<?= $settings['smtp_port'] ?? '587' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Nom d'utilisateur SMTP</label>
                            <input type="text" name="smtp_user" value="<?= $settings['smtp_user'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Mot de passe SMTP</label>
                            <input type="password" name="smtp_pass" value="<?= $settings['smtp_pass'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Chiffrement</label>
                            <select name="smtp_encryption">
                                <option value="tls" <?= ($settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= ($settings['smtp_encryption'] ?? '') == 'none' ? 'selected' : '' ?>>Aucun</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="test_email" class="btn btn-secondary">Tester la configuration</button>
                        <input type="email" name="test_email_address" placeholder="Email de test" style="padding: 12px; margin-left: 10px;">
                    </div>

                    <!-- Onglet Sécurité -->
                    <div id="security" class="tab-content">
                        <h2 style="margin-bottom: 20px;">Paramètres de sécurité</h2>
                        
                        <div class="form-group">
                            <label>Activer le CAPTCHA</label>
                            <select name="captcha_enabled">
                                <option value="1" <?= ($settings['captcha_enabled'] ?? '1') == '1' ? 'selected' : '' ?>>Oui</option>
                                <option value="0" <?= ($settings['captcha_enabled'] ?? '') == '0' ? 'selected' : '' ?>>Non</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Clé reCAPTCHA</label>
                            <input type="text" name="recaptcha_site_key" value="<?= $settings['recaptcha_site_key'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Clé secrète reCAPTCHA</label>
                            <input type="text" name="recaptcha_secret_key" value="<?= $settings['recaptcha_secret_key'] ?? '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Limite de connexions par IP</label>
                            <input type="number" name="login_limit" value="<?= $settings['login_limit'] ?? '5' ?>">
                            <small>Nombre maximum de tentatives de connexion par heure</small>
                        </div>
                    </div>

                    <!-- Onglet Limites -->
                    <div id="limits" class="tab-content">
                        <h2 style="margin-bottom: 20px;">Limites de la plateforme</h2>
                        
                        <div class="form-group">
                            <label>Gain par vidéo (FCFA)</label>
                            <input type="number" name="reward_per_video" value="<?= $settings['reward_per_video'] ?? '100000' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Montant minimum de retrait (FCFA)</label>
                            <input type="number" name="min_withdrawal" value="<?= $settings['min_withdrawal'] ?? '500000' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Montant maximum de retrait (FCFA)</label>
                            <input type="number" name="max_withdrawal" value="<?= $settings['max_withdrawal'] ?? '5000000' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Vidéos maximum par jour</label>
                            <input type="number" name="max_videos_per_day" value="<?= $settings['max_videos_per_day'] ?? '50' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Commission de parrainage (%)</label>
                            <input type="number" name="referral_commission" value="<?= $settings['referral_commission'] ?? '10' ?>" step="0.1">
                        </div>
                        
                        <div class="form-group">
                            <label>Bonus de bienvenue (FCFA)</label>
                            <input type="number" name="welcome_bonus" value="<?= $settings['welcome_bonus'] ?? '5000' ?>">
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <button type="submit" name="save_settings" class="btn btn-primary">💾 Enregistrer tous les paramètres</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function showTab(tabId) {
        // Cacher tous les contenus
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Désactiver tous les onglets
        document.querySelectorAll('.tab').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Activer l'onglet sélectionné
        document.getElementById(tabId).classList.add('active');
        event.target.classList.add('active');
    }
    </script>
</body>
</html>