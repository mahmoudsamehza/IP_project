<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$db = getDB();
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) { header('Location: movies.php'); exit; }

$st = $db->prepare("SELECT * FROM movies WHERE id = ?");
$st->execute([$id]);
$movie = $st->fetch();
if (!$movie) { header('Location: movies.php'); exit; }

$genres    = $db->query("SELECT * FROM genres ORDER BY name")->fetchAll();
$pageTitle = 'Edit: ' . $movie['title'];
$error     = '';
$success   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']       ?? '');
    $desc    = trim($_POST['description'] ?? '');
    $video   = trim($_POST['video_url']   ?? '');
    $trailer = trim($_POST['trailer_url'] ?? '');
    $thumb   = trim($_POST['thumbnail']   ?? '');
    $genre   = (int)($_POST['genre_id']   ?? 0);
    $type    = in_array($_POST['type'] ?? '', ['movie','series']) ? $_POST['type'] : 'movie';
    $year    = (int)($_POST['year']       ?? date('Y'));
    $dur     = (int)($_POST['duration']   ?? 0);
    $feat    = isset($_POST['featured'])  ? 1 : 0;

    if (!$title || !$video) {
        $error = 'Title and video are required.';
    } else {
        $db->prepare("
            UPDATE movies
            SET title=?, description=?, thumbnail=?, video_url=?, trailer_url=?,
                genre_id=?, type=?, release_year=?, duration=?, featured=?
            WHERE id=?
        ")->execute([$title, $desc, $thumb, $video, $trailer ?: null, $genre ?: null, $type, $year, $dur, $feat, $id]);

        $success = 'Changes saved successfully!';
        $st->execute([$id]);
        $movie = $st->fetch();
    }
}

// Thumb preview URL
$thumbPreviewUrl = $movie['thumbnail'] ? (str_starts_with($movie['thumbnail'], 'http') ? $movie['thumbnail'] : SITE_URL.'/assets/thumbs/'.basename($movie['thumbnail'])) : '';

include '../includes/header.php';
?>
<div class="d-flex" style="min-height:calc(100vh - 68px);padding-top:68px;">
    <?php include 'sidebar.php'; ?>
    <div class="sv-admin-content">
        <h1 class="mb-4"><i class="fa-solid fa-pen text-danger me-2"></i>Edit Title</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= sanitize($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= sanitize($success) ?></div>
        <?php endif; ?>

        

        <div class="p-4 rounded-3" style="background:var(--sv-bg-card);border:1px solid rgba(255,255,255,.07);max-width:720px;">
        <form method="POST" action="edit_movie.php?id=<?= $id ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            <div class="row g-3">

                <div class="col-md-8">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Title *</label>
                    <input type="text" name="title" class="form-control" required value="<?= sanitize($movie['title']) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Type</label>
                    <select name="type" class="form-select">
                        <option value="movie"  <?= $movie['type']==='movie'  ? 'selected':'' ?>>Movie</option>
                        <option value="series" <?= $movie['type']==='series' ? 'selected':'' ?>>Series</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= sanitize($movie['description'] ?? '') ?></textarea>
                </div>

                <!-- Full Video -->
                <div class="col-12">
                    <label class="form-label text-secondary small text-uppercase fw-bold">
                        <i class="fa-solid fa-play text-danger me-1"></i>Full Video *
                        <span class="text-secondary fw-normal text-lowercase">(signed-in users only)</span>
                    </label>
                    <input type="text" name="video_url" class="form-control" required id="videoInput"
                           value="<?= sanitize($movie['video_url']) ?>"
                           placeholder="https://... or mymovie.mp4">
                    <div class="form-text text-secondary mt-1">URL or filename &mdash; local files go in <code>/videos/</code></div>
                    <!-- Mini video preview -->
                    
                </div>

                <!-- Trailer -->
                <div class="col-12">
                    <label class="form-label text-secondary small text-uppercase fw-bold">
                        <i class="fa-solid fa-clapperboard text-danger me-1"></i>Trailer
                        <span class="text-secondary fw-normal text-lowercase">(optional &mdash; shown to guests)</span>
                    </label>
                    <input type="text" name="trailer_url" class="form-control" id="trailerInput"
                           value="<?= sanitize($movie['trailer_url'] ?? '') ?>"
                           placeholder="https://... or trailer.mp4">
                    <div class="form-text text-secondary mt-1">URL or filename &mdash; local files go in <code>/videos/</code></div>
                    <!-- Mini trailer preview -->
                    
                </div>

                <!-- Thumbnail / Poster -->
                <div class="col-12">
                    <label class="form-label text-secondary small text-uppercase fw-bold">
                        <i class="fa-solid fa-image text-danger me-1"></i>Poster / Thumbnail
                    </label>
                    <input type="text" name="thumbnail" class="form-control" id="thumbInput"
                           value="<?= sanitize($movie['thumbnail'] ?? '') ?>"
                           placeholder="poster.jpg">
                    <div class="form-text text-secondary mt-1">Filename only &mdash; place in <code>/assets/thumbs/</code></div>
                    <!-- Poster preview -->
                    <div id="thumbPreview" class="mt-2" style="<?= $thumbPreviewUrl ? '' : 'display:none;' ?>">
                        <img id="thumbPreviewImg"
                             src="<?= sanitize($thumbPreviewUrl) ?>"
                             alt="Poster preview"
                             style="height:90px;border-radius:6px;border:1px solid rgba(255,255,255,.1);object-fit:cover;">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Genre</label>
                    <select name="genre_id" class="form-select">
                        <option value="">-- None --</option>
                        <?php foreach ($genres as $g): ?>
                            <option value="<?= (int)$g['id'] ?>" <?= (int)$movie['genre_id']===(int)$g['id'] ? 'selected':'' ?>>
                                <?= sanitize($g['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Release Year</label>
                    <input type="number" name="year" class="form-control"
                           value="<?= (int)$movie['release_year'] ?>" min="1900" max="2030">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Duration (min)</label>
                    <input type="number" name="duration" class="form-control" value="<?= (int)$movie['duration'] ?>">
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="featured" value="1"
                               id="featCheck" <?= $movie['featured'] ? 'checked':'' ?>>
                        <label class="form-check-label text-secondary" for="featCheck">Feature on homepage hero</label>
                    </div>
                </div>

                <div class="col-12 d-flex gap-2 pt-2 border-top border-secondary mt-1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-save me-1"></i>Save Changes
                    </button>
                    <a href="movies.php" class="btn btn-outline-secondary">Cancel</a>
                    <a href="../movie.php?id=<?= $id ?>" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa-solid fa-eye me-1"></i>View Page
                    </a>
                </div>

            </div>
        </form>
        </div>
    </div>
</div>

<script>
const siteUrl    = '<?= SITE_URL ?>';
const thumbInput = document.getElementById('thumbInput');
const thumbPrev  = document.getElementById('thumbPreview');
const thumbImg   = document.getElementById('thumbPreviewImg');

// Live poster preview on input
thumbInput?.addEventListener('input', function () {
    const val = this.value.trim();
    if (!val) { thumbPrev.style.display = 'none'; return; }
    const src = val.startsWith('http') ? val : siteUrl + '/assets/thumbs/' + val;
    thumbImg.src = src;
    thumbPrev.style.display = 'block';
    thumbImg.onerror = () => { thumbPrev.style.display = 'none'; };
    thumbImg.onload  = () => { thumbPrev.style.display = 'block'; };
});
</script>

<?php include '../includes/footer.php'; ?>
