<?php
require_once '../includes/db.php'; require_once '../includes/auth.php'; requireAdmin();
$pageTitle='Manage Titles'; $db=getDB();
$movies=$db->query("SELECT m.*,g.name AS genre_name FROM movies m LEFT JOIN genres g ON m.genre_id=g.id ORDER BY m.created_at DESC")->fetchAll();
include '../includes/header.php';
?>
<div class="d-flex" style="min-height:calc(100vh - 68px);padding-top:68px;">
    <?php include 'sidebar.php'; ?>
    <div class="sv-admin-content">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h1 class="mb-0"><i class="fa-solid fa-film text-danger me-2"></i>All Titles</h1>
            <a href="add_movie.php" class="btn btn-danger btn-sm"><i class="fa-solid fa-plus me-1"></i>Add Title</a>
        </div>
        <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Thumb</th><th>Title</th><th>Genre</th><th>Type</th><th>Year</th><th>Rating</th><th>Featured</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($movies as $m): ?>
            <tr>
                <td><?php if($m['thumbnail']): ?><img src="<?= SITE_URL ?>/assets/thumbs/<?= sanitize(basename($m['thumbnail'])) ?>" class="sv-admin-thumb">
                    <?php else: ?><div class="sv-admin-thumb d-flex align-items-center justify-content-center" style="background:var(--sv-bg-card2);border-radius:4px;"><i class="fa-solid fa-image text-secondary"></i></div><?php endif; ?></td>
                <td><a href="../movie.php?id=<?= (int)$m['id'] ?>" target="_blank" class="text-decoration-none text-white"><?= sanitize($m['title']) ?></a></td>
                <td><?= sanitize($m['genre_name']??'-') ?></td>
                <td><span class="badge <?= $m['type']==='series'?'badge-series':'badge-movie' ?>"><?= $m['type'] ?></span></td>
                <td><?= (int)$m['release_year'] ?></td>
                <td><span class="sv-rating"><i class="fa-solid fa-star me-1"></i><?= number_format($m['rating'],1) ?></span></td>
                <td><?= $m['featured']?'<i class="fa-solid fa-check text-danger"></i>':'-' ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="edit_movie.php?id=<?= (int)$m['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-pen"></i></a>
                        <a href="delete_movie.php?id=<?= (int)$m['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete \'<?= addslashes($m['title']) ?>\'?')"><i class="fa-solid fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
