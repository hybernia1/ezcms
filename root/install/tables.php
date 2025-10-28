<?php
declare(strict_types=1);

return [
    'users' => <<<SQL
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(191) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `role` VARCHAR(50) NOT NULL DEFAULT 'user',
            `status` VARCHAR(20) NOT NULL DEFAULT 'active',
            `last_login_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    SQL,
    'user_meta' => <<<SQL
        CREATE TABLE IF NOT EXISTS `user_meta` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL,
            `meta_key` VARCHAR(150) NOT NULL,
            `meta_value` TEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NULL,
            INDEX (`user_id`),
            CONSTRAINT `fk_user_meta_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    SQL,
    'settings' => <<<SQL
        CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `key` VARCHAR(120) NOT NULL UNIQUE,
            `value` TEXT NULL,
            `updated_at` DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    SQL,
    'pages' => <<<SQL
        CREATE TABLE IF NOT EXISTS `pages` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(190) NOT NULL,
            `slug` VARCHAR(190) NOT NULL UNIQUE,
            `content` LONGTEXT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
            `published_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    SQL,
];
