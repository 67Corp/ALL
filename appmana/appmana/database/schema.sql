-- ============================================================
-- AppMana - Schéma MySQL complet
-- Charset: utf8mb4 | Engine: InnoDB
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id`            INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    `username`      VARCHAR(60)     NOT NULL UNIQUE,
    `email`         VARCHAR(120)    NOT NULL UNIQUE,
    `password_hash` VARCHAR(255)    NOT NULL,
    `display_name`  VARCHAR(100)    NOT NULL,
    `role`          ENUM('student','admin') NOT NULL DEFAULT 'student',
    `status`        ENUM('pending','active','rejected') NOT NULL DEFAULT 'pending',
    `avatar_path`   VARCHAR(255)    DEFAULT NULL,
    `bio`           TEXT            DEFAULT NULL,
    `invited_by`    INT UNSIGNED    DEFAULT NULL,
    `created_at`    DATETIME        DEFAULT CURRENT_TIMESTAMP,
    `last_login_at` DATETIME        DEFAULT NULL,
    FOREIGN KEY (`invited_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_status` (`status`),
    INDEX `idx_role`   (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin par défaut (mot de passe: Admin1234!)
INSERT IGNORE INTO `users` (`username`, `email`, `password_hash`, `display_name`, `role`, `status`)
VALUES ('admin', 'admin@appmana.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'admin', 'active');

-- ============================================================
-- TABLE: themes
-- ============================================================
CREATE TABLE IF NOT EXISTS `themes` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name`       VARCHAR(100) NOT NULL UNIQUE,
    `slug`       VARCHAR(100) NOT NULL UNIQUE,
    `color_hex`  VARCHAR(7)   DEFAULT '#e94560',
    `icon`       VARCHAR(50)  DEFAULT 'folder',
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `themes` (`name`, `slug`, `color_hex`, `icon`) VALUES
    ('Droit',        'droit',        '#e94560', 'balance-scale'),
    ('Gestion',      'gestion',      '#4ecdc4', 'chart-bar'),
    ('Management',   'management',   '#45b7d1', 'users'),
    ('Informatique', 'informatique', '#96ceb4', 'laptop-code'),
    ('Autre',        'autre',        '#f9ca24', 'folder');

-- ============================================================
-- TABLE: courses
-- ============================================================
CREATE TABLE IF NOT EXISTS `courses` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`     INT UNSIGNED NOT NULL,
    `theme_id`    INT UNSIGNED NOT NULL,
    `title`       VARCHAR(255) NOT NULL,
    `description` TEXT         DEFAULT NULL,
    `is_public`   TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)  ON DELETE CASCADE,
    FOREIGN KEY (`theme_id`) REFERENCES `themes`(`id`) ON DELETE RESTRICT,
    INDEX `idx_user_id`  (`user_id`),
    INDEX `idx_theme_id` (`theme_id`),
    FULLTEXT `idx_ft_courses` (`title`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: summaries
-- ============================================================
CREATE TABLE IF NOT EXISTS `summaries` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `course_id`   INT UNSIGNED  NOT NULL,
    `user_id`     INT UNSIGNED  NOT NULL,
    `title`       VARCHAR(255)  DEFAULT NULL,
    `source_url`  VARCHAR(2048) DEFAULT NULL,
    `raw_content` LONGTEXT      NOT NULL,
    `saved_parts` LONGTEXT      DEFAULT NULL,
    `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
    INDEX `idx_course_id` (`course_id`),
    FULLTEXT `idx_ft_summaries` (`title`, `raw_content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: url_cache
-- ============================================================
CREATE TABLE IF NOT EXISTS `url_cache` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `url_hash`     CHAR(64)      NOT NULL UNIQUE,
    `url`          VARCHAR(2048) NOT NULL,
    `summary_text` LONGTEXT      NOT NULL,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    `expires_at`   DATETIME NOT NULL,
    INDEX `idx_url_hash`   (`url_hash`),
    INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: quizzes
-- ============================================================
CREATE TABLE IF NOT EXISTS `quizzes` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `course_id`    INT UNSIGNED NOT NULL,
    `created_by`   INT UNSIGNED NOT NULL,
    `title`        VARCHAR(255) NOT NULL,
    `num_questions` TINYINT UNSIGNED DEFAULT 10,
    `created_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`)  REFERENCES `courses`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)   ON DELETE CASCADE,
    INDEX `idx_course_id` (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: questions
-- ============================================================
CREATE TABLE IF NOT EXISTS `questions` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `quiz_id`       INT UNSIGNED NOT NULL,
    `question_text` TEXT         NOT NULL,
    `options_json`  TEXT         NOT NULL,
    `correct_index` TINYINT UNSIGNED NOT NULL,
    `explanation`   TEXT         DEFAULT NULL,
    `difficulty`    ENUM('facile','moyen','difficile') DEFAULT 'moyen',
    `position`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (`quiz_id`) REFERENCES `quizzes`(`id`) ON DELETE CASCADE,
    INDEX `idx_quiz_id` (`quiz_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: quiz_sessions
-- ============================================================
CREATE TABLE IF NOT EXISTS `quiz_sessions` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `quiz_id`      INT UNSIGNED NOT NULL,
    `user_id`      INT UNSIGNED NOT NULL,
    `challenge_id` INT UNSIGNED DEFAULT NULL,
    `started_at`   DATETIME DEFAULT CURRENT_TIMESTAMP,
    `finished_at`  DATETIME DEFAULT NULL,
    `score`        TINYINT UNSIGNED DEFAULT NULL,
    `answers_json` TEXT     DEFAULT NULL,
    FOREIGN KEY (`quiz_id`)  REFERENCES `quizzes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`)  REFERENCES `users`(`id`)   ON DELETE CASCADE,
    INDEX `idx_user_quiz`  (`user_id`, `quiz_id`),
    INDEX `idx_challenge`  (`challenge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: challenges
-- ============================================================
CREATE TABLE IF NOT EXISTS `challenges` (
    `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `quiz_id`       INT UNSIGNED NOT NULL,
    `challenger_id` INT UNSIGNED NOT NULL,
    `challenged_id` INT UNSIGNED NOT NULL,
    `status`        ENUM('pending','accepted','declined','completed') NOT NULL DEFAULT 'pending',
    `winner_id`     INT UNSIGNED DEFAULT NULL,
    `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
    `completed_at`  DATETIME DEFAULT NULL,
    FOREIGN KEY (`quiz_id`)        REFERENCES `quizzes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`challenger_id`)  REFERENCES `users`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`challenged_id`)  REFERENCES `users`(`id`)   ON DELETE CASCADE,
    FOREIGN KEY (`winner_id`)      REFERENCES `users`(`id`)   ON DELETE SET NULL,
    INDEX `idx_challenged` (`challenged_id`),
    INDEX `idx_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `quiz_sessions`
    ADD CONSTRAINT `fk_qs_challenge`
    FOREIGN KEY (`challenge_id`) REFERENCES `challenges`(`id`) ON DELETE SET NULL;

-- ============================================================
-- TABLE: search_history
-- ============================================================
CREATE TABLE IF NOT EXISTS `search_history` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `query`      VARCHAR(500) NOT NULL,
    `result_src` ENUM('db','gemini','both') DEFAULT 'db',
    `searched_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_searched` (`user_id`, `searched_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT UNSIGNED NOT NULL,
    `type`       VARCHAR(50)  NOT NULL,
    `message`    TEXT         NOT NULL,
    `payload`    TEXT         DEFAULT NULL,
    `is_read`    TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_unread` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
