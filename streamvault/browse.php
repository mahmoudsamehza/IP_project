<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
$pageTitle = 'Browse';
$db = getDB();

$genreSlug = $_GET['genre'] ?? 'all';
$type      = $_GET['type']  ?? 'all';
$sort      = $_GET['sort']  ?? 'recent';
$page      = max(1,(int)($_GET['page']??1));
$perPage   = 20;
$offset    = ($page-1)*$perPage;

$where=['1=1']; $params=[];
if ($genreSlug!=='all'){ $where[]='g.slug=?'; $params[]=$genreSlug; }
if ($type!=='all')     { $where[]='m.type=?'; $params[]=$type; }
$orderBy = match($sort){ 'rating'=>'m.rating DESC','title'=>'m.title ASC','year'=>'m.release_year DESC',default=>'m.created_at DESC' };
$w = implode(' AND ',$where);

$total = (int)$db->prepare("SELECT COUNT(*) FROM movies m LEFT JOIN genres g ON m.genre_id=g.id WHERE $w")->execute($params);
$cStmt = $db->prepare("SELECT COUNT(*) FROM movies m LEFT JOIN genres g ON m.genre_id=g.id WHERE $w");
$cStmt->execute($params); $total=(int)$cStmt->fetchColumn();
$pages = (int)ceil($total/$perPage);

$stmt = $db->prepare("SELECT m.*,g.name AS genre_name FROM movies m LEFT JOIN genres g ON m.genre_id=g.id WHERE $w ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
$stmt->execute($params); $movies=$stmt->fetchAll();

$genres = $db->query("SELECT * FROM genres ORDER BY name")->fetchAll();

$watchlistIds=[];
if (isLoggedIn()){ $w2=$db->prepare("SELECT movie_id FROM watchlist WHERE user_id=?"); $w2->execute([$_SESSION['user_id']]); $watchlistIds=array_column($w2->fetchAll(),'movie_id'); }

include 'includes/header.php';
?>

<div class="container-fluid px-4 px-lg-5" style="padding-top:5.5rem;">
    <div class="mb-3">
        <h1 class="mb-1"><i class="fa-solid fa-film text-danger me-2"></i>Browse</h1>
        <p class="text-secondary"><?= $total ?> titles available</p>
    </div>

    <!-- Filters -->
    <div class="sv-filter-bar border-bottom border-secondary pb-3">
        <a href="?genre=all&type=<?= urlencode($type) ?>&sort=<?= urlencode($sort) ?>" class="sv-pill <?= $genreSlug==='all'?'active':'' ?>">All Genres</a>
        <?php foreach ($genres as $g): ?>
            <a href="?genre=<?= urlencode($g['slug']) ?>&type=<?= urlencode($type) ?>&sort=<?= urlencode($sort) ?>"
               class="sv-pill <?= $genreSlug===$g['slug']?'active':'' ?>"><?= sanitize($g['name']) ?></a>
        <?php endforeach; ?>
        <span class="text-secondary px-1">|</span>
        <a href="?genre=<?= urlencode($genreSlug) ?>&type=all&sort=<?= urlencode($sort) ?>"    class="sv-pill <?= $type==='all'?'active':'' ?>">All</a>
        <a href="?genre=<?= urlencode($genreSlug) ?>&type=movie&sort=<?= urlencode($sort) ?>"  class="sv-pill <?= $type==='movie'?'active':'' ?>">Movies</a>
        <a href="?genre=<?= urlencode($genreSlug) ?>&type=series&sort=<?= urlencode($sort) ?>" class="sv-pill <?= $type==='series'?'active':'' ?>">Series</a>
        <span class="text-secondary px-1">|</span>
        <a href="?genre=<?= urlencode($genreSlug) ?>&type=<?= urlencode($type) ?>&sort=recent" class="sv-pill <?= $sort==='recent'?'active':'' ?>">Recent</a>
        <a href="?genre=<?= urlencode($genreSlug) ?>&type=<?= urlencode($type) ?>&sort=rating" class="sv-pill <?= $sort==='rating'?'active':'' ?>">Top Rated</a>
        <a href="?genre=<?= urlencode($genreSlug) ?>&type=<?= urlencode($type) ?>&sort=title"  class="sv-pill <?= $sort==='title'?'active':'' ?>">A-Z</a>
    </div>

    <!-- Grid -->
    <?php if (!empty($movies)): ?>
    <div class="sv-grid-lg mt-4">
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
                    <span class="sv-card-type"><?= $m['type']==='series'?'Series':'Movie' ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages>1): ?>
    <nav class="mt-4 d-flex justify-content-center">
        <ul class="pagination">
            <?php if ($page>1): ?>
                <li class="page-item"><a class="page-link" href="?genre=<?= urlencode($genreSlug) ?>&type=<?= urlencode($type) ?>&sort=<?= urlencode($sort) ?>&page=<?= $page-1 ?>"><i class="fa-solid fa-chevron-left"></i></a></li>
            <?php endif; ?>
            <?php for ($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?>
                <li class="page-item <?= $i===$page?'active':'' ?>">
                    <a class="page-link" href="?genre=<?= urlencode($genreSlug) ?>&type=<?= urlencode($type) ?>&sort=<?= urlencode($sort) ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($page<$pages): ?>
                <li class="page-item"><a class="page-link" href="?genre=<?= urlencode($genreSlug) ?>&type=<?= urlencode($type) ?>&sort=<?= urlencode($sort) ?>&page=<?= $page+1 ?>"><i class="fa-solid fa-chevron-right"></i></a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div class="sv-empty mt-5">
        <i class="fa-solid fa-film fa-3x mb-3"></i>
        <h4>No titles found</h4>
        <p class="text-secondary">Try a different filter.</p>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
