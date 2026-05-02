# StreamVault — Full Project Documentation

**Stack:** PHP 8.0+ · MySQL · Bootstrap 5 · Plyr.js · Font Awesome 6

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Database — db.sql](#2-database--dbsql)
3. [includes/db.php](#3-includesdbphp)
4. [includes/auth.php](#4-includesauthphp)
5. [includes/header.php](#5-includesheaderphp)
6. [includes/footer.php](#6-includesfooterphp)
7. [index.php — Homepage](#7-indexphp--homepage)
8. [browse.php — Browse Page](#8-browsephp--browse-page)
9. [movie.php — Movie/Watch Page](#9-moviephp--moviewatch-page)
10. [search.php — Search Page](#10-searchphp--search-page)
11. [login.php — Login Page](#11-loginphp--login-page)
12. [register.php — Register Page](#12-registerphp--register-page)
13. [logout.php](#13-logoutphp)
14. [profile.php — User Profile](#14-profilephp--user-profile)
15. [ajax/watchlist.php](#15-ajaxwatchlistphp)
16. [ajax/progress.php](#16-ajaxprogressphp)
17. [admin/index.php — Dashboard](#17-adminindexphp--dashboard)
18. [admin/movies.php — All Titles](#18-adminmoviesphp--all-titles)
19. [admin/add_movie.php — Add Title](#19-adminadd_moviephp--add-title)
20. [admin/edit_movie.php — Edit Title](#20-adminedit_moviephp--edit-title)
21. [admin/delete_movie.php](#21-admindelete_moviephp)
22. [admin/users.php — User Management](#22-adminusersphp--user-management)
23. [admin/sidebar.php](#23-adminsidebarphp)
24. [css/style.css](#24-cssstylecss)
25. [js/main.js](#25-jsmainjs)
26. [How Everything Connects](#26-how-everything-connects)
27. [Security Model](#27-security-model)
28. [Common Errors & Fixes](#28-common-errors--fixes)

---

## 1. Project Overview

StreamVault is a multi-page PHP web application that mimics a streaming platform like Netflix. Users can browse, search, and watch movies and series. Guests can see descriptions and trailers but must register to watch full videos. Admins can manage all content and users through a protected panel.

### Request Lifecycle

Every page request follows this flow:

```
Browser requests page
    → Apache routes to PHP file
        → PHP includes db.php (database connection)
        → PHP includes auth.php (session handling)
        → PHP runs database queries
        → PHP includes header.php (outputs HTML head + navbar)
        → PHP outputs page-specific content
        → PHP includes footer.php (outputs footer + JS)
    → Complete HTML sent to browser
    → Browser loads CSS and JS
    → JavaScript adds interactivity
```

### Folder Layout

```
streamvault/
├── index.php          ← Homepage
├── browse.php         ← Browse all content
├── movie.php          ← Watch a movie
├── search.php         ← Search results
├── login.php          ← Sign in
├── register.php       ← Create account
├── logout.php         ← Sign out
├── profile.php        ← User profile
├── includes/          ← Shared PHP components
├── admin/             ← Admin panel (protected)
├── ajax/              ← AJAX endpoints
├── css/               ← Stylesheets
├── js/                ← JavaScript
├── assets/thumbs/     ← Poster images
├── videos/            ← Local video files
└── db.sql             ← Database schema
```

---

## 2. Database — db.sql

This file creates the entire database structure and fills it with starter data. It is only run once during setup by importing it into phpMyAdmin.

### Tables

#### `users`
Stores all registered accounts.

| Column | Type | Purpose |
|--------|------|---------|
| id | INT AUTO_INCREMENT | Unique identifier for each user |
| username | VARCHAR(50) UNIQUE | Display name, must be unique |
| email | VARCHAR(100) UNIQUE | Login email, must be unique |
| password | VARCHAR(255) | bcrypt hash of the password |
| role | ENUM('user','admin') | Controls access level |
| created_at | TIMESTAMP | When the account was created |

The `password` column is 255 characters long because bcrypt hashes are always 60 characters but the column is given extra space for future-proofing.

#### `genres`
A simple lookup table for movie categories.

| Column | Type | Purpose |
|--------|------|---------|
| id | INT AUTO_INCREMENT | Unique identifier |
| name | VARCHAR(50) | Display name e.g. "Action" |
| slug | VARCHAR(50) | URL-safe version e.g. "action" |

The `slug` is used in URL query strings like `browse.php?genre=sci-fi` so spaces and special characters are avoided.

#### `movies`
The main content table. Stores both movies and series.

| Column | Type | Purpose |
|--------|------|---------|
| id | INT AUTO_INCREMENT | Unique identifier |
| title | VARCHAR(255) | Display title |
| description | TEXT | Synopsis shown on movie page |
| thumbnail | VARCHAR(255) | Poster filename e.g. "poster.jpg" |
| video_url | VARCHAR(500) | Full video — URL or local filename |
| trailer_url | VARCHAR(500) | Trailer — URL or local filename, shown to guests |
| genre_id | INT | Foreign key linking to genres table |
| type | ENUM('movie','series') | Determines badge label |
| release_year | YEAR | Four digit year |
| duration | INT | Length in minutes |
| rating | DECIMAL(3,1) | Average score 0.0–10.0, recalculated when rated |
| rating_count | INT | How many users have rated this title |
| featured | TINYINT(1) | 1 = show in homepage hero banner |
| created_at | TIMESTAMP | When the record was added |

The `FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE SET NULL` means if a genre is deleted, movies in that genre don't get deleted — their `genre_id` just becomes NULL.

#### `watchlist`
Tracks which users have saved which movies.

| Column | Type | Purpose |
|--------|------|---------|
| id | INT AUTO_INCREMENT | Unique identifier |
| user_id | INT | Which user saved it |
| movie_id | INT | Which movie was saved |
| added_at | TIMESTAMP | When it was saved |

The `UNIQUE KEY unique_watchlist (user_id, movie_id)` prevents a user from saving the same movie twice. This constraint also makes the `INSERT ... ON DUPLICATE KEY` pattern work in the AJAX handler.

Both `user_id` and `movie_id` have `ON DELETE CASCADE` — if a user or movie is deleted, their watchlist entries are automatically removed too.

#### `watch_history`
Tracks viewing progress for the resume feature.

| Column | Type | Purpose |
|--------|------|---------|
| user_id | INT | Which user watched |
| movie_id | INT | Which movie was watched |
| watch_position | INT | How many seconds in they got |
| last_watched | TIMESTAMP | Updated every time they watch |

Same `UNIQUE KEY` as watchlist — one row per user per movie. The position is updated in place rather than inserting new rows each time.

#### `ratings`
Stores individual star ratings from users.

| Column | Type | Purpose |
|--------|------|---------|
| user_id | INT | Who rated |
| movie_id | INT | What was rated |
| score | TINYINT | 1 to 10 |
| created_at | TIMESTAMP | When they rated |

After a rating is saved, the app recalculates `movies.rating` as `AVG(score)` and updates `movies.rating_count` with `COUNT(*)` from this table.

### Seed Data

The SQL file also inserts:
- 8 genres (Action, Drama, Comedy, etc.)
- 1 admin user with a bcrypt-hashed password
- 10 sample movies using Google's public test MP4 URLs

---

## 3. includes/db.php

**Purpose:** Database connection and global site constants.

Every page includes this file first. It defines constants used throughout the project and provides the `getDB()` function that returns a database connection.

### Constants

```php
define('DB_HOST', 'localhost');    // MySQL server address
define('DB_NAME', 'streamvault'); // Database name
define('DB_USER', 'root');        // MySQL username
define('DB_PASS', '');            // MySQL password
define('DB_CHARSET', 'utf8mb4'); // Character encoding (supports emoji and special chars)

define('SITE_NAME', 'StreamVault');           // Used in page titles and footer
define('SITE_URL', 'http://localhost/streamvault'); // Used to build all links and asset paths
```

`SITE_URL` is the most important constant. Every link, image path, and redirect in the project uses it. If you move the project to a different server you only need to change this one value.

### getDB() Function

```php
function getDB(): PDO {
    static $pdo = null;        // static means the variable persists between calls
    if ($pdo === null) {       // only create connection if one doesn't exist yet
        $dsn = "mysql:host=...;dbname=...;charset=...";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,       // throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // return arrays with column names
            PDO::ATTR_EMULATE_PREPARES => false,               // use real prepared statements
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;               // return the existing or new connection
}
```

The `static $pdo = null` trick means the function can be called 100 times on one page but the database only connects once. This is called the Singleton pattern.

`PDO::ATTR_EMULATE_PREPARES => false` is important for security — it forces PHP to use the database's own prepared statement handling rather than emulating it in PHP, which gives stronger SQL injection protection.

---

## 4. includes/auth.php

**Purpose:** Everything related to user sessions, login, registration, and access control.

Included on every page that needs to know who is logged in.

### Session Start

```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

The `if` check prevents errors if `session_start()` is called twice — for example on pages that include both `auth.php` and `header.php`.

### isLoggedIn()

```php
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}
```

Checks if the session has a `user_id` value. When a user logs in, `$_SESSION['user_id']` is set. When they log out, the session is destroyed. This is the core check used on every page to show/hide elements.

### isAdmin()

```php
function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
```

Two conditions must both be true: the user must be logged in AND their session role must be exactly `'admin'`. Both are set when they log in.

### requireLogin() and requireAdmin()

```php
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}
```

Called at the top of pages that require a login. If not logged in, the user is redirected to the login page. The `redirect` parameter in the URL stores where they were trying to go, so after logging in they can be sent back there.

`exit` after `header()` is critical — without it, PHP would continue executing the rest of the page even though the redirect header was sent.

### loginUser()

```php
function loginUser(string $email, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, email, role, password FROM users WHERE email = ?");
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    }
    return false;
}
```

Step by step:
1. Fetch user record by email
2. `password_verify()` compares the plain text password against the stored bcrypt hash
3. bcrypt hashes are one-way — you cannot reverse them to get the original password
4. If match: store user data in session and return `true`
5. If no match or user not found: return `false`

The email is searched in the database, not the password — because passwords are hashed and you cannot search for a hash. You fetch by email, then verify the password locally.

### registerUser()

```php
function registerUser(string $username, string $email, string $password): bool|string {
    $db = getDB();
    // Check for existing email or username
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $stmt->execute([trim($email), trim($username)]);
    if ($stmt->fetch()) return 'Email or username already exists.';

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([trim($username), trim($email), $hash]);
    return true;
}
```

Returns `true` on success or an error message string on failure. The calling code checks `if ($result === true)` to determine which happened.

`password_hash($password, PASSWORD_BCRYPT)` automatically generates a random salt and produces a different hash each time even for the same password. This means two users with the same password will have different hashes in the database.

### sanitize()

```php
function sanitize(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}
```

Converts special HTML characters to safe entities:
- `<` becomes `&lt;`
- `>` becomes `&gt;`
- `"` becomes `&quot;`
- `'` becomes `&#039;`

This prevents Cross-Site Scripting (XSS) attacks. Any data from the database that gets displayed on screen should be passed through `sanitize()` first.

---

## 5. includes/header.php

**Purpose:** Outputs the HTML head section and navigation bar. Included at the top of every public page.

### Variables It Expects

The page including this file should set `$pageTitle` before the include:
```php
$pageTitle = 'Browse';
include 'includes/header.php';
```

If not set, it defaults to `SITE_NAME` using the null coalescing operator:
```php
$pageTitle = $pageTitle ?? SITE_NAME;
```

### External Libraries Loaded

```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans...&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
```

All external libraries are loaded from CDNs (Content Delivery Networks). This means the files are served from servers around the world that are geographically close to the user, making them load faster. The custom `style.css` is loaded last so it can override Bootstrap styles.

### Active Nav Link Detection

```php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
```

`$_SERVER['PHP_SELF']` contains the path of the current PHP file like `/streamvault/browse.php`. `basename()` strips the directory and `.php` extension, leaving just `browse`. This is then compared in each nav link:

```php
class="nav-link <?= $currentPage==='browse' ? 'active' : '' ?>"
```

### Conditional Navbar Items

```php
<?php if (isLoggedIn()): ?>
    <!-- Show: username, admin link (if admin), logout -->
<?php else: ?>
    <!-- Show: Sign In, Get Started buttons -->
<?php endif; ?>
```

The navbar renders different content depending on login state. This is checked on every page load using the session.

---

## 6. includes/footer.php

**Purpose:** Outputs the footer HTML and loads JavaScript files. Included at the bottom of every public page.

### Why JavaScript is Loaded Here

Bootstrap JS and `main.js` are loaded at the very bottom of the page, just before `</body>`. This is a performance practice — the browser renders the HTML and CSS first, showing the page to the user, then loads the JavaScript. If JS was in the `<head>` the browser would have to download and execute it before showing anything.

### Bootstrap Bundle

```html
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
```

The `bundle` version includes Popper.js which is required for dropdowns, tooltips, and the mobile navbar collapse. Without it the hamburger menu would not work.

---

## 7. index.php — Homepage

**Purpose:** The main landing page. Shows a hero banner and scrollable carousels of movies.

### Database Queries

```php
$featured = $db->query("SELECT ... WHERE m.featured=1 ORDER BY m.created_at DESC LIMIT 5")->fetchAll();
$recent   = $db->query("SELECT ... ORDER BY m.created_at DESC LIMIT 12")->fetchAll();
$topRated = $db->query("SELECT ... ORDER BY m.rating DESC LIMIT 12")->fetchAll();
$series   = $db->query("SELECT ... WHERE m.type='series' LIMIT 12")->fetchAll();
```

Four separate queries run on every homepage load. Each uses `LEFT JOIN genres` so the genre name is available without a second query. `LEFT JOIN` means movies without a genre are still returned — a regular `JOIN` would exclude them.

### Hero Banner

```php
$hero = $featured[0] ?? null;
```

Takes the first featured movie. The `??` operator returns `null` if `$featured` is empty (no featured movies set). The hero section is wrapped in `<?php if ($hero): ?>` so nothing breaks if there are no featured movies.

The hero background image uses an inline CSS style:
```html
<div class="sv-hero-bg" style="background-image:url('...')"></div>
```

CSS then applies `filter: blur() brightness()` and `animation` to create the subtle zoom effect.

### Watchlist Button State

Before rendering cards, the homepage fetches which movies the logged-in user has already saved:

```php
$watchlistIds = [];
if (isLoggedIn()) {
    $w = $db->prepare("SELECT movie_id FROM watchlist WHERE user_id=?");
    $w->execute([$_SESSION['user_id']]);
    $watchlistIds = array_column($w->fetchAll(), 'movie_id');
}
```

`array_column()` extracts just the `movie_id` values into a flat array like `[3, 7, 12]`. Then when rendering each card, `in_array($m['id'], $watchlistIds)` checks if that movie is in the list to show a checkmark or plus icon.

### svCarousel() Function

```php
function svCarousel(string $id, array $movies, array $wl = []): string {
    ob_start();
    // ... HTML output ...
    return ob_get_clean();
}
```

`ob_start()` turns on output buffering — any `echo` or HTML output goes into a buffer instead of being sent to the browser. `ob_get_clean()` returns everything in the buffer as a string and clears it. This lets the function return HTML as a string which is then echoed by the calling code.

Each carousel gets a unique `id` parameter so the JavaScript can target the correct scrollable container.

### Carousel JavaScript

```javascript
function svScroll(id, dir) {
    const track = document.getElementById('carousel-' + id);
    const cardW = track.querySelector('.sv-card')?.offsetWidth + 12;
    const visible = Math.floor(track.offsetWidth / cardW);
    const step = cardW * visible;
    const maxScroll = track.scrollWidth - track.offsetWidth;

    if (dir === 'next') {
        if (track.scrollLeft + 2 >= maxScroll) {
            track.scrollLeft = 0;           // at end → jump to start
        } else {
            track.scrollLeft += step;       // scroll forward one page
        }
    } else {
        if (track.scrollLeft <= 2) {
            track.scrollLeft = maxScroll;   // at start → jump to end
        } else {
            track.scrollLeft -= step;       // scroll back one page
        }
    }
}
```

`scrollWidth` is the total width of all content. `offsetWidth` is the visible width of the container. Their difference is the maximum scroll distance. The `+ 2` and `<= 2` add small tolerances because browser scrolling can land on fractional pixel values.

`scroll-behavior: smooth` in CSS makes the jump animated.

---

## 8. browse.php — Browse Page

**Purpose:** Shows all movies with filtering by genre, type, and sort order. Includes pagination.

### Reading Filters from URL

```php
$genreSlug = $_GET['genre'] ?? 'all';
$type      = $_GET['type']  ?? 'all';
$sort      = $_GET['sort']  ?? 'recent';
$page      = max(1, (int)($_GET['page'] ?? 1));
```

All values come from the URL query string. The `?? 'all'` default means visiting `browse.php` with no parameters shows everything. `max(1, ...)` prevents negative page numbers.

`(int)` casts the page value to an integer, which means even if someone puts `?page=abc` in the URL it becomes `0`, then `max(1, 0)` makes it `1`. This is basic input sanitization.

### Dynamic SQL Building

```php
$where  = ['1=1'];  // always true condition to start
$params = [];

if ($genreSlug !== 'all') {
    $where[]  = 'g.slug = ?';
    $params[] = $genreSlug;
}
if ($type !== 'all') {
    $where[]  = 'm.type = ?';
    $params[] = $type;
}

$whereStr = implode(' AND ', $where);
```

Starting with `'1=1'` means the WHERE clause always has at least one condition. Then additional conditions are added based on which filters are active. `implode(' AND ', $where)` joins them into `WHERE 1=1 AND g.slug = ? AND m.type = ?`.

The values go into `$params` array in the same order as the `?` placeholders. PDO matches them positionally.

### Sort Order

```php
$orderBy = match($sort) {
    'rating' => 'm.rating DESC',
    'title'  => 'm.title ASC',
    'year'   => 'm.release_year DESC',
    default  => 'm.created_at DESC'
};
```

`match()` is PHP 8's cleaner version of `switch`. It returns one value. Using a `match` instead of putting `$_GET['sort']` directly into SQL prevents SQL injection even though it's not a prepared statement parameter — the value can only ever be one of the four safe strings.

### Pagination

```php
$perPage = 20;
$offset  = ($page - 1) * $perPage;
$pages   = (int)ceil($total / $perPage);
```

Page 1: offset = 0 (start from beginning)
Page 2: offset = 20 (skip first 20)
Page 3: offset = 40 (skip first 40)

`ceil()` rounds up — if there are 45 results with 20 per page, that's 2.25 pages rounded up to 3.

---

## 9. movie.php — Movie/Watch Page

**Purpose:** Shows detailed information about one title and plays the video for logged-in users.

### Fetching the Movie

```php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: browse.php'); exit; }

$stmt = $db->prepare("SELECT m.*,g.name AS genre_name FROM movies m LEFT JOIN genres g ON m.genre_id=g.id WHERE m.id=?");
$stmt->execute([$id]);
$movie = $stmt->fetch();
if (!$movie) { header('Location: browse.php'); exit; }
```

Two layers of protection: if no ID in URL, or if no movie found with that ID, redirect immediately. `(int)` casting means a URL like `?id=abc` becomes `?id=0` which triggers the first redirect.

### Watch History Upsert

```php
$db->prepare("INSERT INTO watch_history (user_id,movie_id,watch_position) VALUES (?,?,0) 
              ON DUPLICATE KEY UPDATE last_watched=NOW()")
   ->execute([$_SESSION['user_id'], $id]);
```

`INSERT ... ON DUPLICATE KEY UPDATE` is a MySQL feature. It tries to insert a new row. If the row already exists (duplicate unique key), it runs the UPDATE part instead. This means:
- First visit: creates a new row with position 0
- Subsequent visits: just updates the timestamp

This is more efficient than checking if the row exists first.

### resolveVideoUrl()

```php
function resolveVideoUrl(string $value): string {
    $value = trim($value);
    if (empty($value)) return '';
    if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) return $value;
    return SITE_URL . '/videos/' . basename($value);
}
```

Allows admin to enter either a full URL or just a filename. `basename()` strips any directory path from the filename for security — prevents path traversal where someone could enter `../../config.php` as a filename.

### Rating Submission

```php
if (isLoggedIn() && $_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['rating'])) {
    $score = (int)$_POST['rating'];
    if ($score >= 1 && $score <= 10) {
        // Save rating
        $db->prepare("INSERT INTO ratings ... ON DUPLICATE KEY UPDATE score=VALUES(score)")
           ->execute(...);
        // Recalculate average
        $avg = $db->prepare("SELECT AVG(score) AS avg, COUNT(*) AS cnt FROM ratings WHERE movie_id=?");
        $avg->execute([$id]); $a = $avg->fetch();
        $db->prepare("UPDATE movies SET rating=?, rating_count=? WHERE id=?")
           ->execute([round($a['avg'],1), $a['cnt'], $id]);
    }
}
```

Three checks before processing: must be logged in, must be a POST request, must have a rating value. The score is validated between 1 and 10. After saving, the movie's average rating is immediately recalculated from all ratings in the table — this keeps `movies.rating` always accurate.

### Guest vs Logged-in View

```php
<?php if (isLoggedIn()): ?>
    <!-- Full Plyr video player -->
<?php else: ?>
    <!-- Blurred locked overlay with sign-in buttons -->
<?php endif; ?>
```

The locked overlay shows the thumbnail image blurred with CSS `filter: blur(18px) brightness(.35)` as a background, giving the impression of a video that can't be watched yet. The `lockPulse` animation makes the lock icon glow rhythmically.

### Plyr.js Integration

```javascript
const player = new Plyr('#mainVideo', {
    controls: ['play-large','play','rewind','fast-forward','progress',
               'current-time','duration','mute','volume','settings','fullscreen'],
    settings: ['speed'],
    speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 1.75, 2] },
    disableContextMenu: true,
});
```

Plyr replaces the browser's default video controls with a custom interface. `disableContextMenu: true` removes the right-click "Save video as" option. The download button is hidden via CSS: `.plyr__control[data-plyr="download"] { display:none }`.

The resume position is applied after Plyr is ready:
```javascript
player.on('ready', () => {
    if (startPos > 0) player.currentTime = startPos;
});
```

The `ready` event is used rather than setting it immediately because Plyr needs to finish initializing before the seek command works.

---

## 10. search.php — Search Page

**Purpose:** Full-text search across movie titles and descriptions.

### Search Query

```php
if ($query !== '') {
    $search = '%' . $query . '%';
    $stmt = $db->prepare("SELECT ... WHERE m.title LIKE ? OR m.description LIKE ? ORDER BY m.rating DESC");
    $stmt->execute([$search, $search]);
}
```

The `%` wildcards mean "anything before or after". So searching `"action"` finds titles containing the word "action" anywhere. The same search term is used twice (for title and description) and passed as two separate parameters.

When no query is provided, the page shows popular picks instead of an empty screen.

---

## 11. login.php — Login Page

**Purpose:** Standalone authentication page. Does not use header.php/footer.php — has its own complete HTML structure so the layout can be a centered card.

### Form Processing

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (loginUser($email, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
```

Validation runs in order — check empty fields first, then check email format, then try to authenticate. This order matters: if the email is empty, `filter_var()` would fail anyway, but the empty check gives a more helpful error message.

`$_SERVER['REQUEST_METHOD'] === 'POST'` is how PHP knows if the form was submitted. On the first visit the method is GET and the form just displays. On submission it becomes POST and the code runs.

### Redirect After Login

```php
$redirect = $_GET['redirect'] ?? 'index.php';
// ...
header('Location: ' . (strpos($redirect, SITE_URL) === 0 ? $redirect : 'index.php'));
```

If the user was sent to login from `movie.php?id=5`, the URL will be `login.php?redirect=movie.php%3Fid%3D5`. After login, they go back to that movie. The security check `strpos($redirect, SITE_URL) === 0` ensures the redirect only goes to pages on this site, not to external URLs (open redirect prevention).

---

## 12. register.php — Register Page

**Purpose:** Account creation page. Also standalone with its own HTML.

### Validation Chain

```php
if (!$username || !$email || !$password || !$confirm) {
    $error = 'All fields are required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Invalid email address.';
} elseif (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters.';
} elseif ($password !== $confirm) {
    $error = 'Passwords do not match.';
} elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
    $error = 'Username: 3-30 chars, letters/numbers/underscores only.';
} else {
    $result = registerUser($username, $email, $password);
    if ($result === true) $success = 'Account created!';
    else $error = $result;
}
```

The regex `^[a-zA-Z0-9_]{3,30}$` means: start of string, 3 to 30 characters that are letters/numbers/underscores, end of string. This prevents usernames with spaces, symbols, or SQL special characters.

---

## 13. logout.php

**Purpose:** Destroys the session and redirects to homepage.

```php
require_once 'includes/auth.php';
logoutUser();
```

`logoutUser()` in auth.php calls `session_destroy()` which removes all session data from the server, then redirects to homepage. The browser's session cookie becomes invalid.

---

## 14. profile.php — User Profile

**Purpose:** Shows the logged-in user's watchlist, watch history with progress bars, and their ratings.

### requireLogin()

```php
requireLogin();
```

First line after includes. If not logged in, execution stops here and the user is redirected. Nothing else in the file runs.

### Progress Bar Calculation

```php
$pct = $item['duration']
    ? min(100, round(($item['watch_position'] / ($item['duration'] * 60)) * 100))
    : 0;
```

`duration` in the database is in minutes. `watch_position` is in seconds. So `duration * 60` converts minutes to seconds for comparison. The result is a percentage capped at 100. The `min(100, ...)` prevents the bar from going over 100% if there's any rounding.

---

## 15. ajax/watchlist.php

**Purpose:** Handles watchlist toggle requests from the JavaScript `toggleWatchlist()` function.

This file is not visited directly — it only responds to AJAX requests from the browser.

```php
header('Content-Type: application/json');
```

This header tells the browser the response is JSON, not HTML. The browser then parses it as data.

### Toggle Logic

```php
$check = $db->prepare("SELECT id FROM watchlist WHERE user_id = ? AND movie_id = ?");
$check->execute([$_SESSION['user_id'], $movieId]);

if ($check->fetch()) {
    // Already saved → remove it
    $db->prepare("DELETE FROM watchlist WHERE user_id = ? AND movie_id = ?")
       ->execute([$_SESSION['user_id'], $movieId]);
    echo json_encode(['status' => 'removed']);
} else {
    // Not saved → add it
    $db->prepare("INSERT INTO watchlist (user_id, movie_id) VALUES (?,?)")
       ->execute([$_SESSION['user_id'], $movieId]);
    echo json_encode(['status' => 'added']);
}
```

The JavaScript reads the `status` field and updates the button icon accordingly without reloading the page.

### Not Logged In Response

```php
if (!isLoggedIn()) {
    echo json_encode(['status' => 'login']);
    exit;
}
```

If somehow the button is clicked while not logged in, the JavaScript receives `{"status":"login"}` and redirects to the login page.

---

## 16. ajax/progress.php

**Purpose:** Saves video playback position every 10 seconds while a user watches.

```php
$db->prepare("INSERT INTO watch_history (user_id, movie_id, watch_position) VALUES (?,?,?) 
              ON DUPLICATE KEY UPDATE watch_position=VALUES(watch_position), last_watched=NOW()")
   ->execute([$_SESSION['user_id'], $movieId, $position]);
```

`VALUES(watch_position)` in the UPDATE clause refers to the value that was about to be inserted. This is a MySQL shorthand to use the same value in both the INSERT and UPDATE parts.

This runs silently in the background — the user never sees any response from it.

---

## 17. admin/index.php — Dashboard

**Purpose:** Admin homepage showing statistics and recent movies.

```php
requireAdmin();
```

The very first protection check. Only continues if the user is both logged in and has the admin role.

### Statistics Queries

```php
$totalMovies = $db->query("SELECT COUNT(*) FROM movies")->fetchColumn();
$totalUsers  = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalWatch  = $db->query("SELECT COUNT(*) FROM watchlist")->fetchColumn();
$totalRating = $db->query("SELECT COUNT(*) FROM ratings")->fetchColumn();
```

`fetchColumn()` returns just the first column of the first row — perfect for `COUNT(*)` which returns a single number.

---

## 18. admin/movies.php — All Titles

**Purpose:** Table showing every movie in the database with edit and delete buttons.

No filters or pagination — shows everything. For a site with thousands of movies this would need pagination added, but for a local project it works fine.

---

## 19. admin/add_movie.php — Add Title

**Purpose:** Form to add a new movie or series to the database.

### Live Poster Preview

```javascript
thumbInput?.addEventListener('input', function () {
    const val = this.value.trim();
    if (!val) { thumbPreview.style.display = 'none'; return; }
    const src = val.startsWith('http') ? val : siteUrl + '/assets/thumbs/' + val;
    thumbImg.src = src;
    thumbPreview.style.display = 'block';
    thumbImg.onerror = () => { thumbPreview.style.display = 'none'; };
});
```

As the admin types in the thumbnail field, JavaScript updates an image element in real time. If the image fails to load (file not found), the `onerror` callback hides the preview. This gives instant visual feedback without submitting the form.

---

## 20. admin/edit_movie.php — Edit Title

**Purpose:** Pre-filled form for editing an existing movie.

### ID Handling

```php
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
```

On the initial page load (GET request), the ID comes from the URL. On form submission (POST request), the ID comes from a hidden form field. Both are checked with `??` so neither causes an error if missing.

The hidden field in the form:
```html
<input type="hidden" name="id" value="<?= $id ?>">
```

Without this, the ID would be lost when the form submits because POST requests don't automatically include GET parameters.

### Refreshing After Save

```php
$success = 'Changes saved successfully!';
$st->execute([$id]);        // re-run the SELECT query
$movie = $st->fetch();      // get fresh data from database
```

After a successful update, the movie data is re-fetched from the database. This ensures the form shows the values that were actually saved, not just the values that were submitted.

---

## 21. admin/delete_movie.php

**Purpose:** Deletes a movie record from the database and redirects back.

```php
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $db->prepare("DELETE FROM movies WHERE id = ?")->execute([$id]);
}
header('Location: movies.php');
exit;
```

The `ON DELETE CASCADE` foreign keys in the database automatically delete all related watchlist entries, watch history, and ratings when a movie is deleted. No separate cleanup queries are needed.

The delete is confirmed with JavaScript `onclick="return confirm('Delete?')"` on the button in movies.php — `confirm()` shows a browser dialog, and `return false` from the function cancels the link navigation.

---

## 22. admin/users.php — User Management

**Purpose:** Lists all users and allows changing their role between user and admin.

### Self-Demotion Prevention

```php
if ($uid !== (int)$_SESSION['user_id']) {
    $db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$role, $uid]);
}
```

An admin cannot change their own role. This prevents accidentally locking yourself out of the admin panel. The check compares the submitted user ID against the logged-in user's ID.

---

## 23. admin/sidebar.php

**Purpose:** The navigation sidebar shown on all admin pages.

```php
$adminPage = basename($_SERVER['PHP_SELF'], '.php');
```

Same active page detection technique as the main navbar. Each link gets `class="... active"` if `$adminPage` matches.

This file is included inside the layout `div`, not as a header/footer, because the admin layout is different — it's a two-column layout with sidebar on the left and content on the right.

---

## 24. css/style.css

**Purpose:** Custom styles that sit on top of Bootstrap 5. Only adds what Bootstrap doesn't provide.

### CSS Variables

```css
:root {
    --sv-red:      #e50914;    /* main brand color, same red as Netflix */
    --sv-bg:       #0a0a0a;    /* near-black page background */
    --sv-bg-card:  #141414;    /* slightly lighter card backgrounds */
    --sv-gold:     #f5c518;    /* IMDb-style gold for star ratings */
}
```

Variables defined on `:root` are available throughout the entire stylesheet. Changing `--sv-red` here changes every button, accent, and highlight color at once.

### Bootstrap Dark Theme

```html
<html lang="en" data-bs-theme="dark">
```

Bootstrap 5.3+ has a built-in dark mode activated by this attribute. It automatically inverts form controls, tables, modals, and other components to dark colors. The custom `style.css` then fine-tunes things Bootstrap's dark mode doesn't cover.

### Hero Animation

```css
.sv-hero-bg {
    animation: heroZoom 14s ease-in-out infinite alternate;
}
@keyframes heroZoom {
    from { transform: scale(1.05); }
    to   { transform: scale(1.0); }
}
```

`alternate` makes the animation go forward then backward, creating a slow breathing zoom effect. `14s` is slow enough to not be distracting. The image starts slightly zoomed in (`scale(1.05)`) so when it shrinks to `scale(1.0)` the edges never show white space.

### Movie Card Hover

```css
.sv-card:hover {
    transform: scale(1.046) translateY(-4px);
    box-shadow: 0 12px 36px rgba(0,0,0,.7);
    z-index: 2;
}
.sv-card:hover .sv-card-overlay { opacity: 1; }
```

The overlay starts at `opacity: 0` and becomes visible on hover. `z-index: 2` makes the hovered card appear above its neighbors when it scales up.

### Plyr Theme

```css
:root {
    --plyr-color-main: #e50914;
    --plyr-video-control-background-hover: rgba(229,9,20,.8);
    --plyr-range-fill-background: #e50914;
}
```

Plyr reads these CSS variables to color its controls. This is how the player turns red without modifying Plyr's source code.

---

## 25. js/main.js

**Purpose:** All client-side JavaScript for the public-facing site.

### Navbar Scroll Effect

```javascript
const update = () => navbar.classList.toggle('scrolled', window.scrollY > 50);
window.addEventListener('scroll', update, { passive: true });
```

`classList.toggle(class, condition)` adds the class if the condition is true, removes it if false. `{ passive: true }` tells the browser this scroll listener will never call `preventDefault()`, allowing the browser to optimize scroll performance.

CSS then styles `.sv-navbar.scrolled` with a solid background, creating the transition from transparent to opaque as the user scrolls.

### toggleWatchlist()

```javascript
function toggleWatchlist(movieId, btn) {
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
            icon.className = 'fa-solid fa-check';
        } else if (data.status === 'removed') {
            btn.classList.remove('in-list');
            icon.className = 'fa-solid fa-plus';
        } else if (data.status === 'login') {
            window.location.href = 'login.php';
        }
    });
}
```

`fetch()` is the modern way to make HTTP requests in JavaScript. It returns a Promise. `.then()` chains handle the response asynchronously — the code continues running while waiting for the server, then the callback runs when the response arrives. `encodeURIComponent()` safely encodes the ID for inclusion in the request body.

### Star Rating

```javascript
stars.forEach((star, idx) => {
    star.addEventListener('mouseenter', () => {
        stars.forEach((s, i) => s.classList.toggle('active', i <= idx));
    });
    star.addEventListener('mouseleave', () => {
        stars.forEach((s, i) => s.classList.toggle('active', i < selected));
    });
    star.addEventListener('click', () => {
        selected = idx + 1;
        document.getElementById('ratingValue').value = selected;
    });
});
```

Three events per star:
- `mouseenter`: highlight all stars up to and including this one
- `mouseleave`: restore to the previously selected rating
- `click`: save the selection and update the hidden form input

The hidden `<input name="rating">` is what gets submitted with the form — the stars are purely visual.

### Form Validation

```javascript
form.addEventListener('submit', e => {
    let valid = true;
    // check required fields, email format, password match
    if (!valid) e.preventDefault();  // stop form from submitting
});
```

`e.preventDefault()` cancels the form submission if validation fails. This gives instant feedback without a server round trip. However the server also validates — client-side validation is for user experience, server-side validation is for security.

---

## 26. How Everything Connects

### Adding a Movie to Watchlist (Full Flow)

```
1. User clicks + button on movie card
2. onclick="toggleWatchlist(5, this)" fires
3. main.js makes POST request to ajax/watchlist.php with movie_id=5
4. ajax/watchlist.php checks session → user is logged in
5. Queries watchlist table → movie 5 not saved yet
6. Inserts row: (user_id=3, movie_id=5)
7. Returns {"status":"added"}
8. main.js receives response
9. Adds 'in-list' class to button (turns it red)
10. Changes icon from fa-plus to fa-check
11. User sees instant feedback, page never reloaded
```

### Watching a Movie (Full Flow)

```
1. User clicks Watch Now → movie.php?id=5
2. PHP gets id=5 from URL
3. Queries movies table for movie 5 → found
4. Queries watch_history → position 340 (previously watched)
5. Updates watch_history last_watched timestamp
6. Queries watchlist → movie is saved (inList = true)
7. Queries ratings → user gave 7/10
8. Fetches 6 related movies
9. PHP checks isLoggedIn() → true
10. Renders: info on top, full Plyr player on bottom
11. Plyr initializes on #mainVideo
12. player.on('ready') fires → seeks to second 340
13. User watches from where they left off
14. Every 10 seconds: fetch POST to ajax/progress.php
15. progress.php updates watch_history.watch_position
16. If user closes page at second 600 → next visit resumes at 600
```

### Page Load (Full Flow)

```
1. Browser requests browse.php?genre=action&sort=rating
2. Apache routes to PHP
3. require_once 'includes/db.php' → constants defined, getDB() available
4. require_once 'includes/auth.php' → session started, helper functions available
5. $genreSlug = 'action', $sort = 'rating' read from $_GET
6. SQL built: WHERE g.slug = ? ORDER BY m.rating DESC
7. COUNT query → 8 results
8. Main query with LIMIT 20 OFFSET 0 → 8 movies fetched
9. Genres fetched for filter buttons
10. Watchlist IDs fetched for logged-in user
11. include 'includes/header.php' → DOCTYPE, head, navbar HTML sent
12. Filter bar HTML output
13. 8 movie card divs output
14. No pagination (only 8 results, less than 20)
15. include 'includes/footer.php' → footer, Bootstrap JS, main.js tags output
16. PHP done, full HTML sent to browser
17. Browser parses HTML, loads CSS
18. Bootstrap, FA icons, Google Fonts loaded from CDNs
19. main.js runs → navbar scroll listener attached
20. Page fully interactive
```

---

## 27. Security Model

### SQL Injection Prevention

Every database query uses prepared statements with `?` placeholders:

```php
// SAFE — value goes through PDO's parameterized query
$stmt = $db->prepare("SELECT * FROM movies WHERE id = ?");
$stmt->execute([$id]);

// DANGEROUS — never do this
$stmt = $db->query("SELECT * FROM movies WHERE id = " . $_GET['id']);
```

In a parameterized query, the database treats the `?` value as pure data, never as SQL code. Even if someone puts `1 OR 1=1` as the ID, it's searched for literally, not interpreted as SQL.

### XSS Prevention

Every value displayed on screen goes through `sanitize()`:

```php
echo sanitize($movie['title']);
// turns <script>alert('xss')</script>
// into  &lt;script&gt;alert('xss')&lt;/script&gt;
// which displays as text, not executable code
```

### Password Security

Passwords are never stored as plain text:
```php
$hash = password_hash('admin123', PASSWORD_BCRYPT);
// produces something like: $2y$10$TKh8H1.PfQx37YgCzwiKb...
```

bcrypt automatically includes a random salt, making rainbow table attacks useless. Even if the database was stolen, the passwords cannot be recovered without brute-forcing each one individually.

### Admin Route Protection

```php
requireAdmin();  // at top of every admin file
```

Even if someone guesses the URL `admin/add_movie.php`, they are immediately redirected if not logged in as admin. The redirect happens before any HTML is output, so there is nothing to see.

---

## 28. Common Errors & Fixes

| Error | Cause | Fix |
|-------|-------|-----|
| `Cannot redeclare resolveVideoUrl()` | Function exists in both `db.php` and `movie.php` | Remove it from `includes/db.php` |
| `Call to undefined function resolveVideoUrl()` | Function missing from `movie.php` | Add the function to `movie.php` before `include 'includes/header.php'` |
| `Invalid email or password` on login | Wrong hash in database | Run `fix.php` to generate a fresh hash on that machine |
| `Not Found` 404 error | Files in wrong location | Should be `htdocs/streamvault/index.php`, not `htdocs/streamvault/streamvault/index.php` |
| Images not showing | Filename mismatch | File must be in `/assets/thumbs/` and filename must match exactly including extension |
| Local video not playing | File not in videos folder | Put `.mp4` file in `/videos/` and enter just the filename in admin |
| Blank page | PHP error with display disabled | Check `C:\xampp\php\php.ini` → set `display_errors = On` then restart Apache |
| Database connection failed | Wrong credentials in `db.php` | Open `includes/db.php` and verify `DB_USER` and `DB_PASS` match your MySQL setup |
| Admin edits not saving | ID lost on POST | Ensure `<input type="hidden" name="id">` is inside the form in `edit_movie.php` |
| Watchlist button not working | AJAX path wrong | Check `ajax/watchlist.php` exists and `SITE_URL` in `db.php` is correct |

---

*StreamVault — Documentation v2.0*
*Built for educational purposes.*
