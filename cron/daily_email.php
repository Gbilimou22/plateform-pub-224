<?php
// cron/daily_email.php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Récupérer les utilisateurs actifs
$query = "SELECT email, username, balance FROM users WHERE is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $to = $user['email'];
    $subject = "📧 PubWatch Pro - Vos gains du jour";
    
    $message = "
    <html>
    <head>
        <title>Vos gains du jour</title>
    </head>
    <body>
        <h2>Bonjour {$user['username']} !</h2>
        <p>Voici votre solde actuel : <strong>{$user['balance']} FCFA</strong></p>
        <p>Connectez-vous aujourd'hui pour gagner votre bonus quotidien !</p>
        <p><a href='https://tonsite.com/login.php'>Se connecter</a></p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    
    mail($to, $subject, $message, $headers);
}
?>