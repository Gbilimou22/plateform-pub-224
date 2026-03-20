<?php
// login.php
session_start();
require_once 'config/database.php';
require_once 'includes/User.php';
require_once 'includes/Auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $user_data = $user->login($email, $password);
    
    if ($user_data) {
        Auth::login($user_data['id'], $user_data['username'], $user_data['email'], $user_data['balance']);
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Email ou mot de passe incorrect";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Connexion - PubWatch Pro</title>
    <style>
        body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 100vh; display: flex; justify-content: center; align-items: center; }
        .container { background: white; padding: 40px; border-radius: 10px; width: 400px; }
        h1 { text-align: center; color: #333; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        button { width: 100%; padding: 10px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { color: red; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Connexion</h1>
        <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>
        <p style="text-align: center">Pas de compte ? <a href="register.php">Inscrivez-vous</a></p>
    </div>
</body>
</html>