<?php
/**
 * StreamVault - AJAX: Watchlist Toggle
 */
require_once '../includes/db.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'login']);
    exit;
}

$movieId = (int)($_POST['movie_id'] ?? 0);
if (!$movieId) {
    echo json_encode(['status' => 'error', 'msg' => 'Invalid movie.']);
    exit;
}

$db = getDB();

// Check exists
$check = $db->prepare("SELECT id FROM watchlist WHERE user_id = ? AND movie_id = ?");
$check->execute([$_SESSION['user_id'], $movieId]);

if ($check->fetch()) {
    // Remove
    $db->prepare("DELETE FROM watchlist WHERE user_id = ? AND movie_id = ?")->execute([$_SESSION['user_id'], $movieId]);
    echo json_encode(['status' => 'removed']);
} else {
    // Add
    $db->prepare("INSERT INTO watchlist (user_id, movie_id) VALUES (?,?)")->execute([$_SESSION['user_id'], $movieId]);
    echo json_encode(['status' => 'added']);
}
