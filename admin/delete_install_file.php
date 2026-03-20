<?php
// delete_install_file.php
$file = 'install_admin.php';
if (file_exists($file)) {
    if (unlink($file)) {
        echo "✅ Fichier supprimé";
    } else {
        echo "❌ Erreur lors de la suppression";
    }
} else {
    echo "✅ Fichier déjà supprimé";
}
?>