<?php

require_once 'includes/db.php';
require_once 'includes/auth.php';

$pageTitle = 'Home';
$db = getDB();

$featured  = $db->query("SELECT m.*, g.name AS genre_name FROM movies m LEFT JOIN genres g ON m.genre_id=g.id WHERE m.featured=1 ORDER BY m.created_at DESC LIMIT 5")->fetchAll();
$recent    = $db->query("SELECT m.*, g.name AS genre_name FROM movies m LEFT JOIN genres g ON m.genre_id=g.id ORDER BY m.created_at DESC LIMIT 12")->fetchAll();
$topRated  = $db->query("SELECT m.*, g.name AS genre_name FROM movies m LEFT JOIN genres g ON m.genre_id=g.id ORDER BY m.rating DESC LIMIT 12")->fetchAll();
$series    = $db->query("SELECT m.*, g.name AS genre_name FROM movies m LEFT JOIN genres g ON m.genre_id=g.id WHERE m.type='series' LIMIT 12")->fetchAll();
$hero      = $featured[0] ?? null;

$watchlistIds = [];
if (isLoggedIn()) {
    $w = $db->prepare("SELECT movie_id FROM watchlist WHERE user_id=?");
    $w->execute([$_SESSION['user_id']]);
    $watchlistIds = array_column($w->fetchAll(), 'movie_id');
}

include 'includes/header.php';
?>

<style>
/* ---- Carousel Row ---- */
.sv-carousel-wrap {
    position: relative;
}

.sv-carousel {
    display: flex;
    gap: .75rem;
    overflow: hidden;         /* hide scrollbar */
    scroll-behavior: smooth;
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.sv-carousel::-webkit-scrollbar { display: none; }

/* Each card fixed width inside carousel */
.sv-carousel .sv-card {
    flex: 0 0 220px;
    min-width: 220px;
    aspect-ratio: 16/9;
}

/* Arrow buttons */
.sv-carousel-btn {
    position: absolute;
    top: 50%; transform: translateY(-50%);
    z-index: 10;
    width: 42px; height: 42px;
    border-radius: 50%;
    background: rgba(20,20,20,.92);
    border: 1px solid rgba(255,255,255,.15);
    color: #fff;
    font-size: 1rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: background .2s, border-color .2s, opacity .2s;
    opacity: 0;
}
.sv-carousel-wrap:hover .sv-carousel-btn { opacity: 1; }
.sv-carousel-btn:hover { background: #e50914; border-color: #e50914; }
.sv-carousel-btn.prev { left: -18px; }
.sv-carousel-btn.next { right: -18px; }

/* Fade edges */
.sv-carousel-wrap::before,
.sv-carousel-wrap::after {
    content: '';
    position: absolute;
    top: 0; bottom: 0;
    width: 48px;
    z-index: 5;
    pointer-events: none;
    opacity: 0;
    transition: opacity .2s;
}
.sv-carousel-wrap:hover::before { opacity: 1; }
.sv-carousel-wrap:hover::after  { opacity: 1; }
.sv-carousel-wrap::before {
    left: 0;
    background: linear-gradient(to right, #0a0a0a, transparent);
}
.sv-carousel-wrap::after {
    right: 0;
    background: linear-gradient(to left, #0a0a0a, transparent);
}

@media (max-width: 576px) {
    .sv-carousel .sv-card { flex: 0 0 160px; min-width: 160px; }
    .sv-carousel-btn { width:34px; height:34px; font-size:.85rem; }
}
</style>

<!-- HERO -->
<?php if ($hero): ?>
<section class="sv-hero">
    <div class="sv-hero-bg" style="background-image:url('<?= SITE_URL ?>/assets/thumbs/<?= sanitize(basename($hero['thumbnail']??'')) ?>')"></div>
    <div class="sv-hero-content">
        <div class="sv-hero-badge mb-2"><i class="fa-solid fa-fire me-1"></i> Featured</div>
        <h1 class="sv-hero-title text-white mb-2"><?= sanitize($hero['title']) ?></h1>
        <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
            <span class="sv-rating"><i class="fa-solid fa-star me-1"></i><?= number_format($hero['rating'],1) ?></span>
            <span class="text-secondary"><?= (int)$hero['release_year'] ?></span>
            <?php if ($hero['duration']): ?>
                <span class="text-secondary"><i class="fa-regular fa-clock me-1"></i><?= (int)$hero['duration'] ?> min</span>
            <?php endif; ?>
            <span class="badge bg-secondary"><?= sanitize($hero['genre_name']??'') ?></span>
        </div>
        <p class="text-white-50 mb-4" style="max-width:540px;line-height:1.7;">
            <?= sanitize(mb_substr($hero['description'],0,200)) ?>...
        </p>
        <div class="d-flex gap-3 flex-wrap">
            <a href="<?= SITE_URL ?>/movie.php?id=<?= (int)$hero['id'] ?>" class="btn btn-danger btn-lg px-4">
                <i class="fa-solid fa-play me-2"></i>Watch Now
            </a>
            <button class="btn btn-outline-light btn-lg px-4" onclick="toggleWatchlist(<?= (int)$hero['id'] ?>,this)">
                <i class="fa-solid <?= in_array($hero['id'],$watchlistIds)?'fa-check':'fa-plus' ?> me-2"></i>My List
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="container-fluid px-4 px-lg-5 mt-5">

    <!-- Recently Added -->
    <?php if (!empty($recent)): ?>
    <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="sv-section-title mb-0"><span>Recently</span> Added</h2>
            <a href="browse.php" class="text-secondary text-decoration-none small">See all <i class="fa-solid fa-chevron-right ms-1"></i></a>
        </div>
        <?= svCarousel('recent', $recent, $watchlistIds) ?>
    </section>
    <?php endif; ?>

    <!-- Top Rated -->
    <?php if (!empty($topRated)): ?>
    <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="sv-section-title mb-0"><span>Top</span> Rated</h2>
            <a href="browse.php?sort=rating" class="text-secondary text-decoration-none small">See all <i class="fa-solid fa-chevron-right ms-1"></i></a>
        </div>
        <?= svCarousel('toprated', $topRated, $watchlistIds) ?>
    </section>
    <?php endif; ?>

    <!-- Series -->
    <?php if (!empty($series)): ?>
    <section class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="sv-section-title mb-0"><span>Series</span> &amp; Shows</h2>
            <a href="browse.php?type=series" class="text-secondary text-decoration-none small">See all <i class="fa-solid fa-chevron-right ms-1"></i></a>
        </div>
        <?= svCarousel('series', $series, $watchlistIds) ?>
    </section>
    <?php endif; ?>

    <!-- CTA -->
    <?php if (!isLoggedIn()): ?>
    <section class="mb-5">
        <div class="text-center p-5 rounded-4" style="background:linear-gradient(135deg,#1a0005,#0f0000);border:1px solid rgba(229,9,20,.2);">
            <i class="fa-solid fa-play fa-3x text-danger mb-3"></i>
            <h2 class="text-white mb-2">Unlimited Cinema</h2>
            <p class="text-secondary mb-4">Join StreamVault and access thousands of movies and series.</p>
            <a href="register.php" class="btn btn-danger btn-lg px-5">
                <i class="fa-solid fa-user-plus me-2"></i>Start Watching Free
            </a>
        </div>
    </section>
    <?php endif; ?>

</div>

<?php
function svCarousel(string $id, array $movies, array $wl = []): string {
    ob_start();
    ?>
    <div class="sv-carousel-wrap">
        <button class="sv-carousel-btn prev" onclick="svScroll('<?= $id ?>','prev')" aria-label="Previous">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <div class="sv-carousel" id="carousel-<?= $id ?>">
            <?php foreach ($movies as $m):
                $inList = in_array($m['id'], $wl);
                $thumb  = $m['thumbnail'] ? SITE_URL.'/assets/thumbs/'.basename($m['thumbnail']) : '';
            ?>
            <div class="sv-card">
                <?php if ($thumb): ?>
                    <img src="<?= $thumb ?>" alt="<?= sanitize($m['title']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="sv-card-placeholder"><i class="fa-solid fa-film"></i></div>
                <?php endif; ?>
                <div class="sv-card-overlay">
                    <div class="sv-card-actions">
                        <a href="<?= SITE_URL ?>/movie.php?id=<?= (int)$m['id'] ?>" class="sv-icon-btn play-btn">
                            <i class="fa-solid fa-play"></i>
                        </a>
                        <button class="sv-icon-btn <?= $inList?'in-list':'' ?>" onclick="toggleWatchlist(<?= (int)$m['id'] ?>,this)">
                            <i class="fa-solid <?= $inList?'fa-check':'fa-plus' ?>"></i>
                        </button>
                    </div>
                    <div class="sv-card-title"><?= sanitize($m['title']) ?></div>
                    <div class="sv-card-meta">
                        <span class="sv-rating"><i class="fa-solid fa-star"></i> <?= number_format($m['rating'],1) ?></span>
                        <span><?= (int)$m['release_year'] ?></span>
                        <span class="sv-card-type"><?= $m['type']==='series'?'Series':'Movie' ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="sv-carousel-btn next" onclick="svScroll('<?= $id ?>','next')" aria-label="Next">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
    </div>
    <?php
    return ob_get_clean();
}

include 'includes/footer.php';
?>

<script>
function svScroll(id, dir) {
    const track = document.getElementById('carousel-' + id);
    if (!track) return;

    const cardW  = track.querySelector('.sv-card')?.offsetWidth + 12 || 232; 
    const visible = Math.floor(track.offsetWidth / cardW);
    const step   = cardW * visible;                 
    const maxScroll = track.scrollWidth - track.offsetWidth;

    if (dir === 'next') {
        
        if (track.scrollLeft + 2 >= maxScroll) {
            track.scrollLeft = 0;
        } else {
            track.scrollLeft += step;
        }
    } else {
        
        if (track.scrollLeft <= 2) {
            track.scrollLeft = maxScroll;
        } else {
            track.scrollLeft -= step;
        }
    }
}
</script>
