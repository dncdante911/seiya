-- База данных для сайта кондитерки и фаершоу
-- MariaDB 10.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Таблица пользователей (админы)
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager') DEFAULT 'manager',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставка дефолтного админа (пароль: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Категории товаров (кондитерка)
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Дефолтные категории
INSERT INTO `categories` (`name`, `slug`, `description`, `sort_order`) VALUES
('Торты', 'torty', 'Свадебные, праздничные и на заказ', 1),
('Пирожные', 'pirozhnyye', 'Эклеры, профитроли, тарталетки', 2),
('Капкейки', 'kapkeyki', 'Маленькие кексы с кремом', 3),
('Макаруны', 'makaruny', 'Французские миндальные печенья', 4);

-- Товары (кондитерка)
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `ingredients` text DEFAULT NULL,
  `weight` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Дополнительные изображения товаров
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ингредиенты для конструктора тортов
CREATE TABLE IF NOT EXISTS `cake_ingredients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` enum('base','filling','cream','decoration','topping') NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Дефолтные ингредиенты
INSERT INTO `cake_ingredients` (`type`, `name`, `description`, `price`) VALUES
('base', 'Бисквит классический', 'Нежный классический бисквит', 300.00),
('base', 'Бисквит шоколадный', 'Шоколадный бисквит', 350.00),
('base', 'Медовый корж', 'Медовые коржи для торта Медовик', 400.00),
('filling', 'Крем сливочный', 'Классический масляный крем', 200.00),
('filling', 'Крем заварной', 'Нежный заварной крем', 250.00),
('filling', 'Ганаш', 'Шоколадный ганаш', 300.00),
('cream', 'Белковый крем', 'Воздушный белковый крем', 200.00),
('cream', 'Сырный крем', 'Крем на основе сливочного сыра', 300.00),
('decoration', 'Фрукты', 'Свежие фрукты', 250.00),
('decoration', 'Ягоды', 'Свежие ягоды', 300.00),
('topping', 'Шоколадная глазурь', 'Глянцевая шоколадная глазурь', 200.00);

-- Программы фаершоу
CREATE TABLE IF NOT EXISTS `fireshow_programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Длительность в минутах',
  `price_per_artist` decimal(10,2) NOT NULL,
  `min_artists` int(11) DEFAULT 1,
  `max_artists` int(11) DEFAULT 10,
  `image` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Портфолио фаершоу
CREATE TABLE IF NOT EXISTS `fireshow_portfolio` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `media_type` enum('image','video') NOT NULL,
  `media_path` varchar(255) NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Заказы кондитерки
CREATE TABLE IF NOT EXISTS `orders_confectionery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_type` enum('product','custom') NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `delivery_date` date NOT NULL,
  `delivery_time` varchar(20) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `custom_description` text DEFAULT NULL,
  `constructor_data` text DEFAULT NULL COMMENT 'JSON данные конструктора',
  `total_price` decimal(10,2) DEFAULT 0.00,
  `comment` text DEFAULT NULL,
  `status` enum('new','confirmed','in_progress','completed','cancelled') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `orders_confectionery_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Заказы фаершоу
CREATE TABLE IF NOT EXISTS `orders_fireshow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` varchar(20) DEFAULT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `event_location` text DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `num_artists` int(11) DEFAULT 1,
  `duration` int(11) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `comment` text DEFAULT NULL,
  `status` enum('new','confirmed','in_progress','completed','cancelled') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `program_id` (`program_id`),
  CONSTRAINT `orders_fireshow_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `fireshow_programs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Чат с кондитером
CREATE TABLE IF NOT EXISTS `chat_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `session_token` varchar(64) NOT NULL,
  `status` enum('active','closed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_token` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Сообщения чата
CREATE TABLE IF NOT EXISTS `chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `sender_type` enum('customer','admin') NOT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','image') DEFAULT 'text',
  `image_path` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Настройки сайта
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Дефолтные настройки
INSERT INTO `settings` (`key_name`, `value`, `description`) VALUES
('site_name', 'Seiya - Кондитерська & Фаєршоу', 'Название сайта'),
('site_phone', '+380 (XX) XXX-XX-XX', 'Телефон для связи'),
('site_email', 'info@seiya.com.ua', 'Email для связи'),
('vk_link', '', 'Ссылка на VK'),
('instagram_link', '', 'Ссылка на Instagram'),
('telegram_link', '', 'Ссылка на Telegram'),
('whatsapp_link', '', 'Ссылка на WhatsApp');

COMMIT;
