-- Holographic Podcast & Live Radio Platform
-- Database Schema
-- Version 1.0

-- Main Tables:
-- users: Stores user account information.
-- episodes: Stores podcast episode details.
-- playlists: Stores user-created playlists.
-- playlist_episodes: Maps episodes to playlists.
-- favorites: Tracks users' favorite episodes.
-- comments: Stores user comments on episodes.
-- reactions: Stores user reactions to episodes.
-- history: Logs user listening history.
-- subscriptions: Manages user subscriptions.
-- donations: Tracks donations made by users.
-- password_resets: Stores tokens for password resets.
-- email_verifications: Stores tokens for email verifications.
-- badges: Defines available badges for gamification.
-- user_badges: Assigns badges to users.
-- challenges: Defines listening challenges.
-- user_challenges: Tracks user progress on challenges.
-- leaderboard: Stores user rankings.
-- ads: Manages advertisements.
-- notifications: Stores user notifications.
-- settings: Stores application-wide settings.

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Table structure for table `episodes`
--

CREATE TABLE `episodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `file_path` varchar(255) NOT NULL,
  `duration` int(11) NOT NULL, -- in seconds
  `author_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
  `release_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
