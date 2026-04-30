<?php
/**
 * StreamVault - AJAX: Save Watch Progress
 */
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['ok' => false]); exit; }

$movieId  = (int)($_POST['movie_id'] ?? 0);
$position = (int)($_POST['position'] ?? 0);

if (!$movieId || $position < 0) { echo json_encode(['ok' => false]); exit; }

$db = getDB();
$db->prepare("INSERT INTO watch_history (user_id, movie_id, watch_position) VALUES (?,?,?) ON DUPLICATE KEY UPDATE watch_position=VALUES(watch_position), last_watched=NOW()")->execute([$_SESSION['user_id'], $movieId, $position]);

echo json_encode(['ok' => true]);
