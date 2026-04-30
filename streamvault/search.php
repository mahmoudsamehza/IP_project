<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
$pageTitle='Search'; $db=getDB();
$query=trim($_GET['q']??''); $movies=[];
if ($query!=='') {
    $s='%'.$query.'%';
    $stmt=$db->prepare("SELECT m.*,g.name AS genre_name FROM movies m LEFT JOIN genres g ON m.genre_id=g.id WHERE m.title LIKE ? OR m.description LIKE ? ORDER BY m.rating DESC");
    $stmt->execute([$s,$s]); $movies=$stmt->fetchAll();
}
$watchlistIds=[];
if (isLoggedIn()) { $w=$db->prepare("SELECT movie_id FROM watchlist WHERE user_id=?"); $w->execute([$_SESSION['user_id']]); $watchlistIds=array_column($w->fetchAll(),'movie_id'); }
include 'includes/header.php';
?>
<div class="container-fluid px-4 px-lg-5" style="padding-top:6rem;">
    <div class="text-center mb-5">
        <h1 class="mb-4"><i class="fa-solid fa-magnifying-glass text-danger me-2"></i>Search</h1>
        <form class="sv-search-wrap" action="" method="GET">
            <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="searchInput" name="q" class="form-control"
                       placeholder="Search movies and series..." value="<?= sanitize($query) ?>" autocomplete="off">
                <button type="submit" class="btn btn-danger px-4">Search</button>
            </div>
        </form>
    </div>

    <?php if ($query!==''): ?>
        <p class="text-secondary mb-4">
            <?= count($movies) ?> result<?= count($movies)!==1?'s':'' ?> for <strong class="text-white">"<?= sanitize($query) ?>"</strong>
        </p>
    <?php endif; ?>

    <?php if (!empty($movies)): ?>
    <div class="sv-grid-lg">
        <?php foreach ($movies as $m):
            $inList=in_array($m['id'],$watchlistIds);
            $thumb=$m['thumbnail']?SITE_URL.'/assets/thumbs/'.basename($m['thumbnail']):'';
        ?>
        <div class="sv-card">
            <?php if($thumb): ?><img src="<?= $thumb ?>" alt="<?= sanitize($m['title']) ?>" loading="lazy">
            <?php else: ?><div class="sv-card-placeholder"><i class="fa-solid fa-film"></i></div><?php endif; ?>
            <div class="sv-card-overlay">
                <div class="sv-card-actions">
                    <a href="movie.php?id=<?= (int)$m['id'] ?>" class="sv-icon-btn play-btn"><i class="fa-solid fa-play"></i></a>
                    <button class="sv-icon-btn <?= $inList?'in-list':'' ?>" onclick="toggleWatchlist(<?= (int)$m['id'] ?>,this)">
                        <i class="fa-solid <?= $inList?'fa-check':'fa-plus' ?>"></i>
                    </button>
                </div>
                <div class="sv-card-title"><?= sanitize($m['title']) ?></div>
                <div class="sv-card-meta">
                    <span class="sv-rating"><i class="fa-solid fa-star"></i> <?= number_format($m['rating'],1) ?></span>
                    <span><?= (int)$m['release_year'] ?></span>
                    <span class="sv-card-type"><?= sanitize($m['genre_name']??'') ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php elseif ($query!==''): ?>
    <div class="sv-empty">
        <i class="fa-solid fa-magnifying-glass fa-3x mb-3"></i>
        <h4>Nothing found</h4>
        <p class="text-secondary">Try different keywords.</p>
        <a href="browse.php" class="btn btn-danger mt-3"><i class="fa-solid fa-film me-1"></i>Browse All</a>
    </div>
    <?php else: ?>
    <div class="mb-5">
        <h2 class="sv-section-title mb-3">Popular <span>Picks</span></h2>
        <?php $pop=$db->query("SELECT m.*,g.name AS genre_name FROM movies m LEFT JOIN genres g ON m.genre_id=g.id ORDER BY m.rating DESC LIMIT 8")->fetchAll(); ?>
        <div class="sv-grid">
            <?php foreach ($pop as $m): $thumb=$m['thumbnail']?SITE_URL.'/assets/thumbs/'.basename($m['thumbnail']):''; ?>
            <div class="sv-card">
                <?php if($thumb): ?><img src="<?= $thumb ?>" alt="<?= sanitize($m['title']) ?>" loading="lazy">
                <?php else: ?><div class="sv-card-placeholder"><i class="fa-solid fa-film"></i></div><?php endif; ?>
                <div class="sv-card-overlay">
                    <div class="sv-card-actions">
                        <a href="movie.php?id=<?= (int)$m['id'] ?>" class="sv-icon-btn play-btn"><i class="fa-solid fa-play"></i></a>
                    </div>
                    <div class="sv-card-title"><?= sanitize($m['title']) ?></div>
                    <div class="sv-card-meta"><span class="sv-rating"><i class="fa-solid fa-star"></i> <?= number_format($m['rating'],1) ?></span></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
