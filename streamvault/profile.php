<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
requireLogin();
$pageTitle='My Profile'; $db=getDB(); $user=getCurrentUser();
$wStmt=$db->prepare("SELECT m.*,g.name AS genre_name FROM watchlist wl JOIN movies m ON wl.movie_id=m.id LEFT JOIN genres g ON m.genre_id=g.id WHERE wl.user_id=? ORDER BY wl.added_at DESC");
$wStmt->execute([$_SESSION['user_id']]); $watchlist=$wStmt->fetchAll();
$hStmt=$db->prepare("SELECT m.*,g.name AS genre_name,wh.watch_position,wh.last_watched FROM watch_history wh JOIN movies m ON wh.movie_id=m.id LEFT JOIN genres g ON m.genre_id=g.id WHERE wh.user_id=? ORDER BY wh.last_watched DESC LIMIT 12");
$hStmt->execute([$_SESSION['user_id']]); $history=$hStmt->fetchAll();
$rStmt=$db->prepare("SELECT r.score,m.title,m.id FROM ratings r JOIN movies m ON r.movie_id=m.id WHERE r.user_id=? ORDER BY r.created_at DESC LIMIT 10");
$rStmt->execute([$_SESSION['user_id']]); $ratings=$rStmt->fetchAll();
include 'includes/header.php';
?>
<div class="container-fluid px-4 px-lg-5" style="padding-top:5.5rem;">
    <!-- Header -->
    <div class="d-flex align-items-center gap-4 flex-wrap border-bottom border-secondary pb-4 mb-4">
        <div class="sv-avatar"><i class="fa-solid fa-user"></i></div>
        <div class="flex-grow-1">
            <h2 class="mb-1"><?= sanitize($user['username']) ?></h2>
            <p class="text-secondary mb-2"><i class="fa-solid fa-envelope me-2"></i><?= sanitize($user['email']) ?></p>
            <div class="d-flex gap-4">
                <div class="text-center"><div class="sv-stat-num"><?= count($watchlist) ?></div><div class="sv-stat-label">Watchlist</div></div>
                <div class="text-center"><div class="sv-stat-num"><?= count($history) ?></div><div class="sv-stat-label">Watched</div></div>
                <div class="text-center"><div class="sv-stat-num"><?= count($ratings) ?></div><div class="sv-stat-label">Ratings</div></div>
            </div>
        </div>
        <a href="logout.php" class="btn btn-outline-secondary ms-auto">
            <i class="fa-solid fa-right-from-bracket me-1"></i>Sign Out
        </a>
    </div>

    <!-- Continue Watching -->
    <?php if (!empty($history)): ?>
    <section class="mb-5">
        <h2 class="sv-section-title mb-3"><i class="fa-solid fa-clock-rotate-left text-danger me-2"></i>Continue Watching</h2>
        <div class="sv-grid">
            <?php foreach (array_slice($history,0,6) as $item):
                $thumb=$item['thumbnail']?SITE_URL.'/assets/thumbs/'.basename($item['thumbnail']):'';
                $pct=$item['duration']?min(100,round(($item['watch_position']/($item['duration']*60))*100)):0;
            ?>
            <div class="sv-card">
                <?php if($thumb): ?><img src="<?= $thumb ?>" alt="<?= sanitize($item['title']) ?>" loading="lazy">
                <?php else: ?><div class="sv-card-placeholder"><i class="fa-solid fa-film"></i></div><?php endif; ?>
                <?php if($pct>0): ?>
                <div class="sv-progress-bar"><div class="sv-progress-bar-fill" style="width:<?= $pct ?>%"></div></div>
                <?php endif; ?>
                <div class="sv-card-overlay">
                    <div class="sv-card-actions">
                        <a href="movie.php?id=<?= (int)$item['id'] ?>" class="sv-icon-btn play-btn"><i class="fa-solid fa-play"></i></a>
                    </div>
                    <div class="sv-card-title"><?= sanitize($item['title']) ?></div>
                    <div class="sv-card-meta"><span><?= gmdate('H:i:s',$item['watch_position']) ?> watched</span></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Watchlist -->
    <section class="mb-5">
        <h2 class="sv-section-title mb-3"><i class="fa-solid fa-bookmark text-danger me-2"></i>My Watchlist</h2>
        <?php if (!empty($watchlist)): ?>
        <div class="sv-grid">
            <?php foreach ($watchlist as $item):
                $thumb=$item['thumbnail']?SITE_URL.'/assets/thumbs/'.basename($item['thumbnail']):'';
            ?>
            <div class="sv-card">
                <?php if($thumb): ?><img src="<?= $thumb ?>" alt="<?= sanitize($item['title']) ?>" loading="lazy">
                <?php else: ?><div class="sv-card-placeholder"><i class="fa-solid fa-film"></i></div><?php endif; ?>
                <div class="sv-card-overlay">
                    <div class="sv-card-actions">
                        <a href="movie.php?id=<?= (int)$item['id'] ?>" class="sv-icon-btn play-btn"><i class="fa-solid fa-play"></i></a>
                        <button class="sv-icon-btn in-list" onclick="toggleWatchlist(<?= (int)$item['id'] ?>,this)">
                            <i class="fa-solid fa-minus"></i>
                        </button>
                    </div>
                    <div class="sv-card-title"><?= sanitize($item['title']) ?></div>
                    <div class="sv-card-meta">
                        <span class="sv-rating"><i class="fa-solid fa-star"></i> <?= number_format($item['rating'],1) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="sv-empty"><i class="fa-solid fa-bookmark"></i><h5>Watchlist is empty</h5>
            <p class="text-secondary">Browse and add titles to your list.</p>
            <a href="browse.php" class="btn btn-danger mt-2"><i class="fa-solid fa-film me-1"></i>Browse Now</a>
        </div>
        <?php endif; ?>
    </section>

    <!-- Ratings -->
    <?php if (!empty($ratings)): ?>
    <section class="mb-5">
        <h2 class="sv-section-title mb-3"><i class="fa-solid fa-star text-danger me-2"></i>My Ratings</h2>
        <div class="list-group" style="max-width:500px;">
            <?php foreach ($ratings as $r): ?>
            <div class="list-group-item d-flex align-items-center justify-content-between" style="background:var(--sv-bg-card);border-color:rgba(255,255,255,.07);">
                <a href="movie.php?id=<?= (int)$r['id'] ?>" class="text-decoration-none text-white fw-500"><?= sanitize($r['title']) ?></a>
                <span class="sv-rating fw-bold"><i class="fa-solid fa-star me-1"></i><?= (int)$r['score'] ?>/10</span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
