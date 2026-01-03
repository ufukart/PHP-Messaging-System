-- Private Messaging System Database Schema

-- Kullanıcılar Tablosu
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mesajlar Tablosu
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_to` INT(11) UNSIGNED NOT NULL,
  `user_from` INT(11) UNSIGNED NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `respond` INT(11) UNSIGNED DEFAULT 0,
  `opened` TINYINT(1) DEFAULT 0,
  `sender_delete` ENUM('y', 'n') DEFAULT 'n',
  `receiver_delete` ENUM('y', 'n') DEFAULT 'n',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_to` (`user_to`),
  KEY `idx_user_from` (`user_from`),
  KEY `idx_respond` (`respond`),
  KEY `idx_opened` (`opened`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_conversation` (`user_to`, `user_from`, `respond`),
  CONSTRAINT `fk_messages_user_to` FOREIGN KEY (`user_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_user_from` FOREIGN KEY (`user_from`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test verileri (opsiyonel)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password`) VALUES
('Ahmet', 'Yılmaz', 'ahmet@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Mehmet', 'Demir', 'mehmet@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Ayşe', 'Kaya', 'ayse@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
