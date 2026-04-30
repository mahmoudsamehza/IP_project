<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();
$pageTitle = 'Add Title';
$db = getDB();
$genres = $db->query("SELECT * FROM genres ORDER BY name")->fetchAll();
$error = ''; $success = '';

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
            INSERT INTO movies (title, description, thumbnail, video_url, trailer_url, genre_id, type, release_year, duration, featured)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([$title, $desc, $thumb, $video, $trailer ?: null, $genre ?: null, $type, $year, $dur, $feat]);
        $success = 'Title added successfully!';
    }
}

include '../includes/header.php';
?>
<div class="d-flex" style="min-height:calc(100vh - 68px);padding-top:68px;">
    <?php include 'sidebar.php'; ?>
    <div class="sv-admin-content">
        <h1 class="mb-4"><i class="fa-solid fa-plus text-danger me-2"></i>Add New Title</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= sanitize($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= sanitize($success) ?>
                <a href="movies.php" class="text-danger ms-2">View all titles</a>
            </div>
        <?php endif; ?>

        

        <div class="p-4 rounded-3" style="background:var(--sv-bg-card);border:1px solid rgba(255,255,255,.07);max-width:720px;">
        <form method="POST">
            <div class="row g-3">

                <div class="col-md-8">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="Movie or series title">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Type</label>
                    <select name="type" class="form-select">
                        <option value="movie">Movie</option>
                        <option value="series">Series</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Short synopsis..."></textarea>
                </div>

                <!-- Full Video -->
                <div class="col-12">
                    <label class="form-label text-secondary small text-uppercase fw-bold">
                        <i class="fa-solid fa-play text-danger me-1"></i>Full Video *
                    </label>
                    <input type="text" name="video_url" class="form-control" required id="videoInput"
                           placeholder="https://... or mymovie.mp4">
                    <div class="form-text text-secondary mt-1">
                        URL or filename &mdash; local files go in <code>/videos/</code>
                    </div>
                </div>

                <!-- Trailer -->
                <div class="col-12">
                    <label class="form-label text-secondary small text-uppercase fw-bold">
                        <i class="fa-solid fa-clapperboard text-danger me-1"></i>Trailer
                        <span class="text-secondary fw-normal text-lowercase">(optional &mdash; shown to guests)</span>
                    </label>
                    <input type="text" name="trailer_url" class="form-control" id="trailerInput"
                           placeholder="https://... or trailer.mp4">
                    <div class="form-text text-secondary mt-1">
                        URL or filename &mdash; local files go in <code>/videos/</code>
                    </div>
                </div>

                <!-- Thumbnail / Poster -->
                <div class="col-12">
                    <label class="form-label text-secondary small text-uppercase fw-bold">
                        <i class="fa-solid fa-image text-danger me-1"></i>Poster / Thumbnail
                        <span class="text-secondary fw-normal text-lowercase">(optional)</span>
                    </label>
                    <input type="text" name="thumbnail" class="form-control" id="thumbInput"
                           placeholder="poster.jpg">
                    <div class="form-text text-secondary mt-1">
                        Filename only &mdash; place file in <code>/assets/thumbs/</code>
                    </div>
                    <!-- Live preview -->
                    <div id="thumbPreview" class="mt-2" style="display:none;">
                        <img id="thumbPreviewImg" src="" alt="Preview"
                             style="height:90px;border-radius:6px;border:1px solid rgba(255,255,255,.1);object-fit:cover;">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Genre</label>
                    <select name="genre_id" class="form-select">
                        <option value="">-- Select Genre --</option>
                        <?php foreach ($genres as $g): ?>
                            <option value="<?= (int)$g['id'] ?>"><?= sanitize($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Release Year</label>
                    <input type="number" name="year" class="form-control" value="<?= date('Y') ?>" min="1900" max="2030">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-secondary small text-uppercase fw-bold">Duration (min)</label>
                    <input type="number" name="duration" class="form-control" placeholder="90" min="0">
                </div>

                <div class="col-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="featured" value="1" id="featCheck">
                        <label class="form-check-label text-secondary" for="featCheck">Feature on homepage hero</label>
                    </div>
                </div>

                <div class="col-12 d-flex gap-2 pt-2 border-top border-secondary mt-1">
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-plus me-1"></i>Add Title
                    </button>
                    <a href="movies.php" class="btn btn-outline-secondary">Cancel</a>
                </div>

            </div>
        </form>
        </div>
    </div>
</div>

<script>
// Live thumbnail preview
const thumbInput   = document.getElementById('thumbInput');
const thumbPreview = document.getElementById('thumbPreview');
const thumbImg     = document.getElementById('thumbPreviewImg');
const siteUrl      = '<?= SITE_URL ?>';

thumbInput?.addEventListener('input', function () {
    const val = this.value.trim();
    if (!val) { thumbPreview.style.display = 'none'; return; }
    const src = val.startsWith('http') ? val : siteUrl + '/assets/thumbs/' + val;
    thumbImg.src = src;
    thumbPreview.style.display = 'block';
    thumbImg.onerror = () => { thumbPreview.style.display = 'none'; };
});
</script>

<?php include '../includes/footer.php'; ?>
