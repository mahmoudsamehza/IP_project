<?php
/**
 * StreamVault - Global Header (Bootstrap 5)
 */
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$pageTitle   = $pageTitle ?? SITE_NAME;
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<link rel="icon" href="icon.jpeg" type="image/jpeg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> | <?= SITE_NAME ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Custom Overrides -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sv-navbar fixed-top" id="navbar">
    <div class="container-fluid px-4">
        <a class="navbar-brand sv-logo" href="<?= SITE_URL ?>/index.php">
            <i class="fa-solid fa-play"></i> <?= SITE_NAME ?>
        </a>
        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false">
            <i class="fa-solid fa-bars text-white"></i>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto ms-3">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='index'  ? 'active' : '' ?>" href="<?= SITE_URL ?>/index.php">
                        <i class="fa-solid fa-house me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='browse' ? 'active' : '' ?>" href="<?= SITE_URL ?>/browse.php">
                        <i class="fa-solid fa-film me-1"></i>Browse
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='search' ? 'active' : '' ?>" href="<?= SITE_URL ?>/search.php">
                        <i class="fa-solid fa-magnifying-glass me-1"></i>Search
                    </a>
                </li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <?php if (isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/profile.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-circle-user me-1"></i><?= sanitize($_SESSION['username']) ?>
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="<?= SITE_URL ?>/admin/index.php" class="btn btn-outline-danger btn-sm">
                            <i class="fa-solid fa-shield-halved me-1"></i>Admin
                        </a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/logout.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/login.php"    class="btn btn-outline-light btn-sm">Sign In</a>
                    <a href="<?= SITE_URL ?>/register.php" class="btn btn-danger btn-sm">
                        <i class="fa-solid fa-play me-1"></i>Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<main class="sv-main">
