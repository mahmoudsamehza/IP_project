/**
 * StreamVault - Main JavaScript (Bootstrap 5 edition)
 */

/* ---- Navbar scroll effect ---- */
(function () {
    const navbar = document.getElementById('navbar');
    if (!navbar) return;
    const update = () => navbar.classList.toggle('scrolled', window.scrollY > 50);
    window.addEventListener('scroll', update, { passive: true });
    update();
})();

/* ---- Watchlist AJAX toggle ---- */
function toggleWatchlist(movieId, btn) {
    if (!movieId) return;
    const icon = btn.querySelector('i');
    fetch('ajax/watchlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'movie_id=' + encodeURIComponent(movieId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'added') {
            btn.classList.add('in-list');
            if (icon) icon.className = 'fa-solid fa-check';
        } else if (data.status === 'removed') {
            btn.classList.remove('in-list');
            if (icon) icon.className = 'fa-solid fa-plus';
        } else if (data.status === 'login') {
            window.location.href = 'login.php';
        }
    });
}

/* ---- Star rating widget ---- */
(function () {
    const stars = document.querySelectorAll('.sv-star');
    if (!stars.length) return;
    let selected = parseInt(document.getElementById('ratingValue')?.value || 0);

    stars.forEach((star, idx) => {
        star.addEventListener('mouseenter', () => {
            stars.forEach((s, i) => s.classList.toggle('active', i <= idx));
        });
        star.addEventListener('mouseleave', () => {
            stars.forEach((s, i) => s.classList.toggle('active', i < selected));
        });
        star.addEventListener('click', () => {
            selected = idx + 1;
            const rv = document.getElementById('ratingValue');
            if (rv) rv.value = selected;
            stars.forEach((s, i) => s.classList.toggle('active', i < selected));
        });
    });
})();

/* ---- Video resume position ---- */
(function () {
    const video = document.getElementById('mainVideo');
    if (!video) return;
    const movieId = video.dataset.movieId;
    const startPos = parseInt(video.dataset.startPos || 0);
    if (startPos > 0) video.currentTime = startPos;

    let lastSave = 0;
    video.addEventListener('timeupdate', () => {
        const now = Math.floor(video.currentTime);
        if (now - lastSave >= 10 && movieId) {
            lastSave = now;
            fetch('ajax/progress.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'movie_id=' + movieId + '&position=' + now
            }).catch(() => {});
        }
    });
})();

/* ---- Client-side form validation ---- */
(function () {
    const form = document.querySelector('form[data-validate]');
    if (!form) return;
    form.addEventListener('submit', e => {
        let valid = true;

        form.querySelectorAll('[required]').forEach(field => {
            const errEl = field.closest('.mb-3, .mb-4')?.querySelector('.form-error');
            if (!field.value.trim()) {
                valid = false;
                field.classList.add('is-invalid');
                if (errEl) errEl.textContent = 'This field is required.';
            } else {
                field.classList.remove('is-invalid');
                if (errEl) errEl.textContent = '';
            }
        });

        const emailField = form.querySelector('input[type="email"]');
        if (emailField?.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value)) {
            valid = false;
            emailField.classList.add('is-invalid');
            const errEl = emailField.closest('.mb-3,.mb-4')?.querySelector('.form-error');
            if (errEl) errEl.textContent = 'Enter a valid email address.';
        }

        const pw1 = form.querySelector('#password');
        const pw2 = form.querySelector('#password_confirm');
        if (pw1 && pw2 && pw1.value !== pw2.value) {
            valid = false;
            pw2.classList.add('is-invalid');
            const errEl = pw2.closest('.mb-3,.mb-4')?.querySelector('.form-error');
            if (errEl) errEl.textContent = 'Passwords do not match.';
        }

        if (!valid) e.preventDefault();
    });
})();
