<?php /** StreamVault - Footer (Bootstrap 5) */ ?>
</main>

<footer class="sv-footer mt-5">
    <div class="container-fluid px-4">
        <div class="row py-4 border-bottom border-secondary">
            <div class="col-lg-4 mb-4 mb-lg-0">
                <a href="<?= SITE_URL ?>/index.php" class="sv-logo text-decoration-none d-inline-flex align-items-center gap-2 mb-2">
                    <i class="fa-solid fa-play text-danger"></i>
                    <span><?= SITE_NAME ?></span>
                </a>
                <p class="text-secondary small">Premium streaming. No limits.</p>
                <div class="d-flex gap-2 mt-3">
                    <a href="#" class="btn btn-outline-secondary btn-sm rounded-circle" style="width:36px;height:36px;padding:0;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-brands fa-x-twitter"></i>
                    </a>
                    <a href="#" class="btn btn-outline-secondary btn-sm rounded-circle" style="width:36px;height:36px;padding:0;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <a href="#" class="btn btn-outline-secondary btn-sm rounded-circle" style="width:36px;height:36px;padding:0;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-brands fa-youtube"></i>
                    </a>
                </div>
            </div>
            <div class="col-6 col-lg-2 mb-4 mb-lg-0">
                <h6 class="text-uppercase text-secondary fw-bold small mb-3">Platform</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?= SITE_URL ?>/index.php"   class="text-secondary text-decoration-none sv-footer-link">Home</a></li>
                    <li class="mb-2"><a href="<?= SITE_URL ?>/browse.php"  class="text-secondary text-decoration-none sv-footer-link">Browse</a></li>
                    <li class="mb-2"><a href="<?= SITE_URL ?>/search.php"  class="text-secondary text-decoration-none sv-footer-link">Search</a></li>
                    <li class="mb-2"><a href="<?= SITE_URL ?>/profile.php" class="text-secondary text-decoration-none sv-footer-link">Profile</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2 mb-4 mb-lg-0">
                <h6 class="text-uppercase text-secondary fw-bold small mb-3">Account</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?= SITE_URL ?>/register.php" class="text-secondary text-decoration-none sv-footer-link">Sign Up</a></li>
                    <li class="mb-2"><a href="<?= SITE_URL ?>/login.php"    class="text-secondary text-decoration-none sv-footer-link">Sign In</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h6 class="text-uppercase text-secondary fw-bold small mb-3">Legal</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="#" class="text-secondary text-decoration-none sv-footer-link">Privacy</a></li>
                    <li class="mb-2"><a href="#" class="text-secondary text-decoration-none sv-footer-link">Terms</a></li>
                </ul>
            </div>
        </div>
        <div class="py-3 text-center text-secondary small">
            &copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved. &nbsp;|&nbsp;
            Made with <i class="fa-solid fa-heart text-danger"></i> for cinema lovers.
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Main JS -->
<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>
