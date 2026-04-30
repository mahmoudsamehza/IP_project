<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }
$pageTitle='Sign In'; $error=''; $redirect=$_GET['redirect']??'index.php';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $email=trim($_POST['email']??''); $password=trim($_POST['password']??'');
    if (!$email||!$password) $error='Please fill in all fields.';
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) $error='Invalid email address.';
    elseif (loginUser($email,$password)) { header('Location: index.php'); exit; }
    else $error='Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Sign In | <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="sv-auth-page">
    <div class="sv-auth-bg"></div>
    <div class="sv-auth-card">
        <div class="text-center mb-4">
            <a href="index.php" class="sv-logo text-decoration-none d-inline-flex align-items-center gap-2">
                <i class="fa-solid fa-play"></i><?= SITE_NAME ?>
            </a>
        </div>
        <h2 class="mb-1">Welcome back</h2>
        <p class="text-secondary mb-4">Sign in to continue watching.</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= sanitize($error) ?></div>
        <?php endif; ?>

        <form method="POST" data-validate>
            <div class="mb-3">
                <label class="form-label text-secondary small text-uppercase fw-bold">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="you@example.com" required value="<?= sanitize($_POST['email']??'') ?>">
                </div>
                <div class="form-error text-danger small mt-1"></div>
            </div>
            <div class="mb-4">
                <label class="form-label text-secondary small text-uppercase fw-bold">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Your password" required>
                </div>
                <div class="form-error text-danger small mt-1"></div>
            </div>
            <button type="submit" class="btn btn-danger w-100 py-2">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
            </button>
        </form>
        <p class="text-center text-secondary mt-4 mb-0 small">
            Don't have an account? <a href="register.php" class="text-danger">Sign up free</a>
        </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body></html>
