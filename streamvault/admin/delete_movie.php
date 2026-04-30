<?php
/**
 * StreamVault - Admin: Delete Movie
 */
require_once '../includes/db.php';
require_once '../includes/auth.php';

requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $db = getDB();
    $db->prepare("DELETE FROM movies WHERE id = ?")->execute([$id]);
}

header('Location: movies.php');
exit;
