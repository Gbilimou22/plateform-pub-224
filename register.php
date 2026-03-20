<?php
// register.php (modifié pour accepter le paramètre ref)
session_start();
require_once 'config/database.php';
require_once 'includes/User.php';

$error = '';
$success = '';

// Récupérer le code de parrainage s'il existe
$ref_code = isset($_GET['ref']) ? $_GET['ref'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $referral_code = $_POST['referral_code'] ?? '';

    // Ajouter la logique de parrainage
    $referred_by = null;
    if (!empty($referral_code)) {
        // Chercher l'utilisateur qui a ce code
        $query = "SELECT id FROM users WHERE referral_code = :code";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":code", $referral_code);
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $parrain = $stmt->fetch(PDO::FETCH_ASSOC);
            $referred_by = $parrain['id'];
        }
    }

    if ($user->register($username, $email, $password, $phone, $referred_by)) {
        $success = "Inscription réussie ! <a href='login.php'>Connectez-vous</a>";
    } else {
        $error = "Erreur lors de l'inscription";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inscription - PubWatch Pro</title>
    <style>
        body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100vh; display: flex; justify-content: center; align-items: center; }
        .container { background: white; padding: 40px; border-radius: 10px; width: 400px; }
        h1 { text-align: center; color: #333; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { width: 100%; padding: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { color: red; text-align: center; }
        .success { color: green; text-align: center; }
        .info-box { background: #e7f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; font-size: 0.9em; }
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
    <div class="container">
        <h1>Inscription</h1>
        <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <?php if($success): ?><div class="success"><?= $success ?></div><?php endif; ?>
        
        <?php if(!empty($ref_code)): ?>
        <div class="info-box">
            🤝 Vous êtes parrainé ! Code: <?= htmlspecialchars($ref_code) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="tel" name="phone" placeholder="Téléphone" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <input type="hidden" name="referral_code" value="<?= htmlspecialchars($ref_code) ?>">
            <button type="submit">S'inscrire</button>
        </form>
        <p style="text-align: center">Déjà inscrit ? <a href="login.php">Connexion</a></p>
    </div>
</body>
</html>