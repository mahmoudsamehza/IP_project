<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: browse.php'); exit; }

$db   = getDB();
$stmt = $db->prepare("SELECT m.*,g.name AS genre_name FROM movies m LEFT JOIN genres g ON m.genre_id=g.id WHERE m.id=?");
$stmt->execute([$id]);
$movie = $stmt->fetch();
if (!$movie) { header('Location: browse.php'); exit; }

$pageTitle  = $movie['title'];
$startPos   = 0;
$inList     = false;
$userRating = 0;

if (isLoggedIn()) {
    $h = $db->prepare("SELECT watch_position FROM watch_history WHERE user_id=? AND movie_id=?");
    $h->execute([$_SESSION['user_id'], $id]);
    $hist     = $h->fetch();
    $startPos = $hist ? (int)$hist['watch_position'] : 0;
    $db->prepare("INSERT INTO watch_history (user_id,movie_id,watch_position) VALUES (?,?,0) ON DUPLICATE KEY UPDATE last_watched=NOW()")->execute([$_SESSION['user_id'], $id]);

    $wq = $db->prepare("SELECT id FROM watchlist WHERE user_id=? AND movie_id=?");
    $wq->execute([$_SESSION['user_id'], $id]);
    $inList = (bool)$wq->fetch();

    $rq = $db->prepare("SELECT score FROM ratings WHERE user_id=? AND movie_id=?");
    $rq->execute([$_SESSION['user_id'], $id]);
    $r = $rq->fetch();
    $userRating = $r ? (int)$r['score'] : 0;
}

$ratingMsg = '';
if (isLoggedIn() && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['rating'])) {
    $score = (int)$_POST['rating'];
    if ($score >= 1 && $score <= 10) {
        $db->prepare("INSERT INTO ratings (user_id,movie_id,score) VALUES (?,?,?) ON DUPLICATE KEY UPDATE score=VALUES(score)")->execute([$_SESSION['user_id'],$id,$score]);
        $avg = $db->prepare("SELECT AVG(score) AS avg, COUNT(*) AS cnt FROM ratings WHERE movie_id=?");
        $avg->execute([$id]); $a = $avg->fetch();
        $db->prepare("UPDATE movies SET rating=?,rating_count=? WHERE id=?")->execute([round($a['avg'],1),$a['cnt'],$id]);
        $ratingMsg  = 'Rating saved!';
        $userRating = $score;
    }
}

$rel = $db->prepare("SELECT m.*,g.name AS genre_name FROM movies m LEFT JOIN genres g ON m.genre_id=g.id WHERE m.genre_id=? AND m.id!=? ORDER BY m.rating DESC LIMIT 6");
$rel->execute([$movie['genre_id'], $id]);
$related = $rel->fetchAll();

$thumb      = $movie['thumbnail'] ? SITE_URL.'/assets/thumbs/'.basename($movie['thumbnail']) : '';
$hasTrailer = !empty($movie['trailer_url']);

function resolveVideoUrl(string $value): string {
    $value = trim($value);
    if (empty($value)) return '';
    if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) return $value;
    return SITE_URL . '/videos/' . basename($value);
}

include 'includes/header.php';
?>

<link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">

<style>
:root {
    --plyr-color-main: #e50914;
    --plyr-video-background: #000;
    --plyr-menu-background: #141414;
    --plyr-menu-color: #e5e5e5;
    --plyr-badge-background: #e50914;
    --plyr-font-family: 'DM Sans', sans-serif;
    --plyr-video-control-background-hover: rgba(229,9,20,.8);
    --plyr-range-fill-background: #e50914;
}
.sv-player-page { background:#000; padding-top:68px; }
.plyr__control[data-plyr="download"] { display:none !important; }

.sv-locked-wrap {
    position: relative; width: 100%;
    aspect-ratio: 16/9; background: #000; overflow: hidden;
}
.sv-locked-bg {
    position: absolute; inset: 0;
    background-size: cover; background-position: center;
    filter: blur(18px) brightness(.35); transform: scale(1.1);
}
.sv-locked-overlay {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 1.25rem; text-align: center; padding: 2rem;
    background: rgba(0,0,0,.55);
}
.sv-lock-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: rgba(229,9,20,.15); border: 2px solid rgba(229,9,20,.4);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; color: #e50914;
    animation: lockPulse 2s ease-in-out infinite;
}
@keyframes lockPulse {
    0%,100% { box-shadow: 0 0 0 0 rgba(229,9,20,.3); }
    50%      { box-shadow: 0 0 0 16px rgba(229,9,20,0); }
}
.sv-locked-overlay h3 { color:#fff; font-size:clamp(1.2rem,3vw,1.8rem); margin:0; }
.sv-locked-overlay p  { color:rgba(255,255,255,.6); margin:0; font-size:.9rem; max-width:360px; }
.sv-trailer-label {
    display: inline-flex; align-items:center; gap:.4rem;
    background: rgba(229,9,20,.15); border: 1px solid rgba(229,9,20,.3);
    color: #e50914; padding:.25rem .75rem; border-radius:4px;
    font-size:.72rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
    margin-bottom: 1rem;
}
.sv-divider { border:none; border-top:1px solid rgba(255,255,255,.07); margin:0; }
</style>

<div class="sv-player-page">

    <!-- ====== TOP: POSTER + INFO + TRAILER + RATING ====== -->
    <div class="container-fluid px-4 px-lg-5 py-4">
        <div class="row g-4">

            <!-- Poster -->
            <?php if ($thumb): ?>
            <div class="col-lg-4 col-md-5 d-none d-md-block">
                <img src="<?= $thumb ?>" alt="<?= sanitize($movie['title']) ?>"
                     class="rounded-3 w-100 shadow"
                     style="object-fit:cover;aspect-ratio:16/9;border:1px solid rgba(255,255,255,.08);">
            </div>
            <?php endif; ?>

            <!-- Info -->
            <div class="col-lg-<?= $thumb ? '8' : '12' ?> col-md-<?= $thumb ? '7' : '12' ?>">

                <h1 class="mb-2"><?= sanitize($movie['title']) ?></h1>

                <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                    <span class="sv-rating fw-bold">
                        <i class="fa-solid fa-star me-1"></i><?= number_format($movie['rating'],1) ?>
                        <span class="text-secondary fw-normal ms-1 small">/10 (<?= (int)$movie['rating_count'] ?> votes)</span>
                    </span>
                    <span class="text-secondary"><?= (int)$movie['release_year'] ?></span>
                    <?php if ($movie['duration']): ?>
                        <span class="text-secondary"><i class="fa-regular fa-clock me-1"></i><?= (int)$movie['duration'] ?> min</span>
                    <?php endif; ?>
                    <?php if ($movie['genre_name']): ?>
                        <span class="badge bg-secondary"><?= sanitize($movie['genre_name']) ?></span>
                    <?php endif; ?>
                    <span class="badge <?= $movie['type']==='series'?'badge-series':'badge-movie' ?>"><?= $movie['type'] ?></span>
                </div>

                <p class="text-secondary mb-4" style="line-height:1.75;font-size:.97rem;max-width:700px;">
                    <?= sanitize($movie['description']) ?>
                </p>

                <?php if (isLoggedIn()): ?>
                <div class="d-flex gap-2 flex-wrap mb-4">
                    <a href="#player" class="btn btn-danger">
                        <i class="fa-solid fa-play me-1"></i>Watch Now
                    </a>
                    <button class="btn <?= $inList?'btn-danger':'btn-outline-light' ?>" onclick="toggleWatchlist(<?= $id ?>,this)">
                        <i class="fa-solid <?= $inList?'fa-check':'fa-plus' ?> me-1"></i><?= $inList?'In My List':'Add to My List' ?>
                    </button>
                    <a href="browse.php" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-film me-1"></i>Browse All
                    </a>
                </div>
                <?php else: ?>
                <div class="d-flex gap-3 flex-wrap mb-4">
                    <a href="login.php?redirect=<?= urlencode('movie.php?id='.$id) ?>" class="btn btn-danger px-4">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In to Watch
                    </a>
                    <a href="register.php" class="btn btn-outline-light px-4">
                        <i class="fa-solid fa-user-plus me-2"></i>Create Free Account
                    </a>
                </div>
                <div class="alert alert-info d-inline-flex align-items-center gap-2 mb-4" style="max-width:500px;">
                    <i class="fa-solid fa-circle-info fa-lg"></i>
                    <span>Free account required to stream. No credit card needed.</span>
                </div>
                <?php endif; ?>

                <!-- Trailer -->
                <?php if ($hasTrailer): ?>
                <div class="mb-4">
                    <div class="sv-trailer-label">
                        <i class="fa-solid fa-clapperboard"></i> Official Trailer
                    </div>
                    <div style="max-width:780px;aspect-ratio:16/9;background:#000;border-radius:10px;overflow:hidden;border:1px solid rgba(255,255,255,.08);">
                        <video id="trailerVideo" playsinline controls style="width:100%;height:100%;">
                            <source src="<?= sanitize(resolveVideoUrl($movie['trailer_url'])) ?>" type="video/mp4">
                        </video>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Rating -->
                <?php if (isLoggedIn()): ?>
                <div class="p-3 rounded-3" style="background:var(--sv-bg-card);border:1px solid rgba(255,255,255,.07);max-width:400px;">
                    <h6 class="text-secondary text-uppercase fw-bold small mb-3">
                        <i class="fa-solid fa-star me-1"></i>Rate This
                    </h6>
                    <?php if ($ratingMsg): ?>
                        <div class="alert alert-success py-2 small mb-2">
                            <i class="fa-solid fa-circle-check me-1"></i><?= sanitize($ratingMsg) ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="rating" id="ratingValue" value="<?= $userRating ?>">
                        <div class="d-flex gap-1 mb-3">
                            <?php for ($i=1;$i<=10;$i++): ?>
                                <span class="sv-star <?= $i<=$userRating?'active':'' ?>">
                                    <i class="fa-solid fa-star"></i>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fa-solid fa-check me-1"></i>Submit Rating
                        </button>
                    </form>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <hr class="sv-divider">

    <!-- ====== BOTTOM: FULL VIDEO PLAYER ====== -->
    <div id="player" style="background:#000;">
        <?php if (isLoggedIn()): ?>
        <div style="max-width:1200px;margin:0 auto;">
            <video id="mainVideo" playsinline
                   data-movie-id="<?= $id ?>"
                   data-start-pos="<?= $startPos ?>"
                   poster="<?= $thumb ?>">
                <source src="<?= sanitize(resolveVideoUrl($movie['video_url'])) ?>" type="video/mp4" size="1080">
            </video>
        </div>
        <?php else: ?>
        <div class="sv-locked-wrap" style="max-width:1200px;margin:0 auto;">
            <?php if ($thumb): ?>
                <div class="sv-locked-bg" style="background-image:url('<?= $thumb ?>')"></div>
            <?php endif; ?>
            <div class="sv-locked-overlay">
                <div class="sv-lock-icon"><i class="fa-solid fa-lock"></i></div>
                <h3>Sign in to Watch</h3>
                <p>Create a free account or sign in to stream this <?= $movie['type']==='series'?'series':'movie' ?>.</p>
                <div class="d-flex gap-3 flex-wrap justify-content-center">
                    <a href="login.php?redirect=<?= urlencode('movie.php?id='.$id) ?>" class="btn btn-danger px-4">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In
                    </a>
                    <a href="register.php" class="btn btn-outline-light px-4">
                        <i class="fa-solid fa-user-plus me-2"></i>Create Free Account
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ====== RELATED ====== -->
    <?php if (!empty($related)): ?>
    <div class="container-fluid px-4 px-lg-5 py-5">
        <h2 class="sv-section-title mb-3">More <span><?= sanitize($movie['genre_name']??'Like This') ?></span></h2>
        <div class="sv-grid">
            <?php foreach ($related as $rel):
                $rThumb = $rel['thumbnail'] ? SITE_URL.'/assets/thumbs/'.basename($rel['thumbnail']) : '';
            ?>
            <div class="sv-card">
                <?php if($rThumb): ?>
                    <img src="<?= $rThumb ?>" alt="<?= sanitize($rel['title']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="sv-card-placeholder"><i class="fa-solid fa-film"></i></div>
                <?php endif; ?>
                <div class="sv-card-overlay">
                    <div class="sv-card-actions">
                        <a href="movie.php?id=<?= (int)$rel['id'] ?>" class="sv-icon-btn play-btn">
                            <i class="fa-solid fa-play"></i>
                        </a>
                    </div>
                    <div class="sv-card-title"><?= sanitize($rel['title']) ?></div>
                    <div class="sv-card-meta">
                        <span class="sv-rating"><i class="fa-solid fa-star"></i> <?= number_format($rel['rating'],1) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    <?php if (isLoggedIn()): ?>
    const player = new Plyr('#mainVideo', {
        controls: ['play-large','play','rewind','fast-forward','progress','current-time','duration','mute','volume','settings','fullscreen'],
        settings: ['speed'],
        speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2] },
        disableContextMenu: true,
        keyboard: { focused: true, global: true },
        tooltips: { controls: true, seek: true },
        ratio: '16:9',
    });

    const videoEl  = document.getElementById('mainVideo');
    const movieId  = videoEl?.dataset.movieId;
    const startPos = parseInt(videoEl?.dataset.startPos || 0);

    player.on('ready', () => { if (startPos > 0) player.currentTime = startPos; });

    let lastSave = 0;
    player.on('timeupdate', () => {
        const now = Math.floor(player.currentTime);
        if (now - lastSave >= 10 && movieId) {
            lastSave = now;
            fetch('ajax/progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'movie_id=' + movieId + '&position=' + now
            }).catch(() => {});
        }
    });

    document.querySelector('.plyr')?.addEventListener('contextmenu', e => e.preventDefault());
    <?php endif; ?>

    <?php if ($hasTrailer): ?>
    new Plyr('#trailerVideo', {
        controls: ['play-large','play','progress','current-time','mute','volume','fullscreen'],
        disableContextMenu: true,
        ratio: '16:9',
    });
    <?php endif; ?>

});
</script>

<?php include 'includes/footer.php'; ?>
