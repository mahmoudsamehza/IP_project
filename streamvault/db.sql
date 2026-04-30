-- ============================================
-- StreamVault Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS streamvault CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE streamvault;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories/Genres table
CREATE TABLE IF NOT EXISTS genres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE
);

-- Movies/Series table
CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    thumbnail VARCHAR(255) DEFAULT NULL,
    video_url VARCHAR(500) NOT NULL,
    trailer_url VARCHAR(500) DEFAULT NULL,
    genre_id INT,
    type ENUM('movie', 'series') DEFAULT 'movie',
    release_year YEAR,
    duration INT COMMENT 'Duration in minutes',
    rating DECIMAL(3,1) DEFAULT 0.0,
    rating_count INT DEFAULT 0,
    featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE SET NULL
);

-- Watchlist table
CREATE TABLE IF NOT EXISTS watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_watchlist (user_id, movie_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Watch history / continue watching
CREATE TABLE IF NOT EXISTS watch_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    watch_position INT DEFAULT 0 COMMENT 'Position in seconds',
    last_watched TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_history (user_id, movie_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- Ratings table
CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    score TINYINT NOT NULL CHECK (score BETWEEN 1 AND 10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rating (user_id, movie_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
);

-- ============================================
-- Seed Data
-- ============================================

INSERT INTO genres (name, slug) VALUES
('Action', 'action'),
('Drama', 'drama'),
('Comedy', 'comedy'),
('Thriller', 'thriller'),
('Sci-Fi', 'sci-fi'),
('Horror', 'horror'),
('Romance', 'romance'),
('Documentary', 'documentary');

-- Admin user (password: admin123)
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@streamvault.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample movies (using public domain / sample video URLs)
INSERT INTO movies (title, description, thumbnail, video_url, genre_id, type, release_year, duration, rating, featured) VALUES
('Neon Drift', 'A cyberpunk thriller set in 2087 where a rogue AI controls the city grid. One hacker must pull the plug before dawn.', 'assets/thumbs/neon-drift.jpg', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4', 4, 'movie', 2024, 112, 8.2, 1),
('The Last Signal', 'Deep space. No crew. One distress beacon. A solitary astronaut races against oxygen depletion to uncover the truth.', 'assets/thumbs/last-signal.jpg', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4', 5, 'movie', 2023, 98, 7.9, 1),
('Hollow Crown', 'A medieval drama of betrayal and blood as three siblings battle for a dying empire. Based on the acclaimed novel.', 'assets/thumbs/hollow-crown.jpg', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4', 2, 'series', 2024, 55, 9.1, 1),
('Fracture Point', 'An elite detective with a shattered memory must solve her own disappearance — before someone else finishes the job.', 'assets/thumbs/fracture.jpg', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4', 4, 'movie', 2023, 105, 7.6, 0),
('Laughing Gas', 'A stand-up comedian accidentally becomes the face of a political movement. Chaos, brilliance, and terrible decisions follow.', 'assets/thumbs/laughing-gas.jpg', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4', 3, 'movie', 2024, 92, 7.3, 0),
('Void Protocol', 'Six strangers wake in an abandoned research facility. No exits. No memories. And something hunting them in the dark.', 'assets/thumbs/void-protocol.jpg', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyrides.mp4', 6, 'movie', 2023, 108, 8.0, 0),
('Parallel Lines', 'A love story told across three timelines. Same souls, different centuries, one impossible choice.', 'assets/thumbs/parallel-lines.jpg', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4', 7, 'movie', 2024, 127, 8.5, 0),
('Iron Meridian', 'Elite soldiers. Fractured loyalties. A mission that was never supposed to happen. Action that redefines the genre.', 'assets/thumbs/iron-meridian.jpg', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4', 1, 'movie', 2024, 134, 8.7, 0),
('Silent Archive', 'A documentary exploring lost civilizations discovered beneath the Sahara — and the governments scrambling to hide them.', 'assets/thumbs/silent-archive.jpg', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/SubaruOutbackOnStreetAndDirt.mp4', 8, 'documentary', 2023, 89, 8.1, 0),
('Crimson Skies', 'A sci-fi epic. Earth falls silent. The last colony ship makes one final transmission. What it receives changes everything.', 'assets/thumbs/crimson-skies.jpg', 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4', 5, 'series', 2024, 48, 9.3, 1);
