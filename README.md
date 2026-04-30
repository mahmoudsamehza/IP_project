# StreamVault


------------------------------------------------------------------------

## Tech Stack

  Layer          Technology
  -------------- ---------------------------------------------------------
  Frontend       HTML5, Bootstrap 5, Custom CSS
  Icons          Font Awesome 6
  Fonts          Bebas Neue, DM Sans (Google Fonts)
  Video Player   Plyr.js 3.7.8
  Backend        PHP 8.0+ (no frameworks)
  Database       MySQL / MariaDB
  Security       PDO prepared statements, bcrypt passwords, session auth

------------------------------------------------------------------------

## Requirements

-   PHP 8.0 or higher
-   MySQL 5.7+ or MariaDB 10.3+
-   Apache or Nginx (XAMPP recommended for local setup)

------------------------------------------------------------------------

## Installation

### Step 1 --- Place files

Unzip and place the `streamvault` folder inside your web root:

    C:\xampp\htdocs\streamvault\

### Step 2 --- Create the database

1.  Open `http://localhost/phpmyadmin`
2.  Click **New** in the left sidebar
3.  Name it `streamvault` and click **Create**
4.  Click the **Import** tab
5.  Choose `db.sql` from the project folder
6.  Click **Go**

### Step 3 --- Configure the database connection

Open `includes/db.php` and update these values:

``` php
define('DB_HOST', 'localhost');
define('DB_NAME', 'streamvault');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('SITE_URL', 'http://localhost/streamvault');
```

### Step 4 --- Visit the site

    http://localhost/streamvault

------------------------------------------------------------------------

## Default Admin Account

  Field      Value
  ---------- -----------------------
  Email      admin@streamvault.com
  Password   admin123

**Admin panel:** `http://localhost/streamvault/admin/`

------------------------------------------------------------------------

## File Structure

    streamvault/
    │
    ├── index.php              # Homepage — hero banner + carousel rows
    ├── browse.php             # Browse all titles with filters and pagination
    ├── search.php             # Search by title or description
    ├── movie.php              # Movie detail page — info, trailer, Plyr video player
    ├── login.php              # Sign in page
    ├── register.php           # Create account page
    ├── logout.php             # Session destroy
    ├── profile.php            # User profile, watchlist, history, ratings
    │
    ├── includes/
    │   ├── db.php             # PDO database connection + site constants
    │   ├── auth.php           # Login, register, session, sanitize helpers
    │   ├── header.php         # Global navbar + HTML head (Bootstrap, FA, Plyr)
    │   └── footer.php         # Global footer + Bootstrap JS
    │
    ├── admin/
    │   ├── index.php          # Dashboard with stats overview
    │   ├── movies.php         # All titles table
    │   ├── add_movie.php      # Add new title form
    │   ├── edit_movie.php     # Edit existing title form
    │   ├── delete_movie.php   # Delete handler
    │   ├── users.php          # User role management
    │   └── sidebar.php        # Admin navigation sidebar
    │
    ├── ajax/
    │   ├── watchlist.php      # Toggle watchlist (POST → JSON response)
    │   └── progress.php       # Save video watch position (POST → JSON)
    │
    ├── css/
    │   └── style.css          # Custom styles on top of Bootstrap 5
    │
    ├── js/
    │   └── main.js            # Navbar scroll, AJAX watchlist, star rating, form validation
    │
    ├── assets/
    │   └── thumbs/            # Movie poster/thumbnail images go here
    │
    ├── videos/                # Local video files go here (optional)
    │
    └── db.sql                 # Full database schema + seed data

------------------------------------------------------------------------

## Adding Content

### Via Admin Panel

Go to `http://localhost/streamvault/admin/add_movie.php`

Fill in: - **Title** and **Description** - **Full Video** --- URL or
local filename - **Trailer** --- URL or local filename (shown to
guests) - **Poster** --- image filename - **Genre**, **Year**,
**Duration** - Check **Feature on homepage** to show in the hero banner

### Video Sources

  Format       Example
  ------------ -------------------------------------
  Remote URL   `https://example.com/movie.mp4`
  Local file   `mymovie.mp4` → place in `/videos/`

### Poster Images

Place image files in `/assets/thumbs/` and enter just the filename in
the admin form. Example: save `inception.jpg` to
`/assets/thumbs/inception.jpg`, then type `inception.jpg` in the
Thumbnail field.

------------------------------------------------------------------------

## Features

### Users

-   Register and log in with secure bcrypt password hashing
-   Session-based authentication
-   Personal watchlist (add/remove with no page reload)
-   Watch history with resume position
-   1--10 star rating system per title
-   Profile page showing watchlist, history, and ratings

### Content

-   Movies and series with genre, year, duration, rating
-   Hero banner on homepage with featured title
-   Horizontal carousel rows (Recently Added, Top Rated, Series)
-   Browse page with genre filter, type filter, sort, and pagination
-   Search by title or description
-   Related titles on every movie page

### Video Player (Plyr.js)

-   Custom red-themed controls
-   Playback speed control (0.5x -- 2x)
-   Right-click disabled (no easy download)
-   Auto-resumes from last watched position
-   Trailer player for guests on movie page

### Guest Experience

-   Guests can browse all titles
-   Movie page shows poster, description, trailer
-   Full video is locked behind a blurred overlay with sign-in prompt
-   After signing in, redirected back to the same movie

### Admin Panel

-   Dashboard with total titles, users, watchlist saves, ratings
-   Add, edit, and delete titles
-   Promote or demote users between user and admin roles
-   Live poster preview when entering filename in forms

------------------------------------------------------------------------

## Security

-   All database queries use PDO prepared statements --- no SQL
    injection
-   All output passed through `htmlspecialchars` --- no XSS
-   Passwords hashed with `PASSWORD_BCRYPT`
-   Admin routes protected with `requireAdmin()` --- redirects
    non-admins
-   Video right-click disabled on player

------------------------------------------------------------------------

## Troubleshooting

  -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
  Problem                                       Fix
  --------------------------------------------- ---------------------------------------------------------------------------------------------------------------------------------
  Blank page or 404                             Make sure files are in `htdocs/streamvault/` not `htdocs/streamvault/streamvault/`

  Database error                                Check credentials in `includes/db.php` and make sure `db.sql` was imported

  Invalid email or password                     Run the reset SQL in phpMyAdmin:
                                                `UPDATE users SET password='$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UyVC' WHERE email='admin@streamvault.com';`

  Images not showing                            Make sure image file is in `/assets/thumbs/` and filename matches exactly

  Local video not playing                       Make sure video file is in `/videos/` and you entered just the filename (e.g. `movie.mp4`)

  Cannot redeclare error                        Your `includes/db.php` has an old `resolveVideoUrl()` function --- delete it from `db.php`
  -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

------------------------------------------------------------------------
