<?php
require_once 'includes/db.php';
$hash = password_hash('admin123', PASSWORD_BCRYPT);
$db = getDB();
$db->prepare("UPDATE users SET password = ? WHERE email = 'admin@streamvault.com'")->execute([$hash]);
echo "Done! New hash: " . $hash;
?>