<?php
// cron/setup_cron.php
// À exécuter une seule fois pour configurer le cron via un service en ligne

$cron_url = "http://" . $_SERVER['HTTP_HOST'] . "/pub-watching-platform/cron/renew_videos.php";

echo "<h1>Configuration du renouvellement automatique</h1>";
echo "<p>URL à appeler toutes les heures : <strong>$cron_url</strong></p>";
echo "<h2>Options :</h2>";
echo "<ul>";
echo "<li><strong>Hébergement mutualisé :</strong> Utilise le gestionnaire de cron de ton hébergeur</li>";
echo "<li><strong>Serveur dédié :</strong> Ajoute cette ligne dans crontab :<br>";
echo "<code>0 * * * * php " . __DIR__ . "/renew_videos.php</code></li>";
echo "<li><strong>Service en ligne :</strong> Utilise https://cron-job.org avec l'URL : $cron_url</li>";
echo "</ul>";

// Test manuel
if (isset($_GET['test'])) {
    echo "<h2>Test manuel</h2>";
    include 'renew_videos.php';
}
?>