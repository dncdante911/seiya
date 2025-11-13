-- =====================================================
-- Таблица новостей и объявлений
-- =====================================================
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section ENUM('confectionery', 'fireshow', 'both') NOT NULL DEFAULT 'both',
    type ENUM('news', 'announcement') NOT NULL DEFAULT 'news',
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    is_pinned TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    published_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_section (section),
    INDEX idx_type (type),
    INDEX idx_is_active (is_active),
    INDEX idx_published (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Таблица контактной информации
-- =====================================================
CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL DEFAULT 'Seiya - Кондитерські вироби та вогняне шоу',
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    address TEXT DEFAULT NULL,
    work_hours TEXT DEFAULT NULL,

    -- Социальные сети
    instagram VARCHAR(255) DEFAULT NULL,
    facebook VARCHAR(255) DEFAULT NULL,
    telegram VARCHAR(255) DEFAULT NULL,
    viber VARCHAR(50) DEFAULT NULL,
    whatsapp VARCHAR(50) DEFAULT NULL,

    -- Координаты для карты
    map_latitude DECIMAL(10, 8) DEFAULT NULL,
    map_longitude DECIMAL(11, 8) DEFAULT NULL,
    map_zoom INT DEFAULT 14,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставляем дефолтные контакты
INSERT INTO contacts (company_name, phone, email, address, work_hours) VALUES
('Seiya - Кондитерські вироби та вогняне шоу', '+380 XX XXX XX XX', 'info@seiya.com.ua', 'м. Київ, Україна', 'Пн-Пт: 9:00-18:00, Сб-Нд: за домовленістю')
ON DUPLICATE KEY UPDATE id=id;

-- =====================================================
-- Таблица "О нас"
-- =====================================================
CREATE TABLE IF NOT EXISTS about_us (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section ENUM('confectionery', 'fireshow') NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    content TEXT NOT NULL,
    mission TEXT DEFAULT NULL,
    vision TEXT DEFAULT NULL,

    -- Преимущества (JSON массив)
    advantages JSON DEFAULT NULL,

    -- Изображения
    main_image VARCHAR(255) DEFAULT NULL,
    gallery_images JSON DEFAULT NULL,

    -- Статистика (опционально)
    stats_years_experience INT DEFAULT NULL,
    stats_happy_clients INT DEFAULT NULL,
    stats_events INT DEFAULT NULL,
    stats_products INT DEFAULT NULL,

    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_section (section)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Вставляем дефолтные данные "О нас" для кондитерки
INSERT INTO about_us (section, title, subtitle, content, mission, advantages, stats_years_experience, stats_happy_clients) VALUES
(
    'confectionery',
    'Про нашу кондитерську',
    'Смачні та красиві вироби на будь-який смак',
    'Ми створюємо унікальні кондитерські вироби, які приносять радість та насолоду. Кожен наш торт, тістечко або десерт - це маленький шедевр, створений з любов\'ю та професіоналізмом.',
    'Наша місія - робити кожен момент вашого життя солодшим та незабутнім',
    JSON_ARRAY(
        JSON_OBJECT('icon', '✨', 'title', 'Якісні інгредієнти', 'text', 'Використовуємо тільки натуральні та свіжі продукти'),
        JSON_OBJECT('icon', '🎨', 'title', 'Унікальний дизайн', 'text', 'Кожен виріб створюється індивідуально'),
        JSON_OBJECT('icon', '🚚', 'title', 'Швидка доставка', 'text', 'Доставляємо свіжі вироби точно в строк'),
        JSON_OBJECT('icon', '💝', 'title', 'Доступні ціни', 'text', 'Висока якість за розумною ціною')
    ),
    5,
    500
)
ON DUPLICATE KEY UPDATE section=section;

-- Вставляем дефолтные данные "О нас" для фаершоу
INSERT INTO about_us (section, title, subtitle, content, mission, advantages, stats_years_experience, stats_events) VALUES
(
    'fireshow',
    'Про наше вогняне шоу',
    'Незабутні емоції та захоплююче видовище',
    'Ми - команда професійних артистів, які створюють незабутні вогняні шоу. Наші виступи - це поєднання мистецтва, небезпеки та краси, яке залишає незабутні враження.',
    'Наша місія - дарувати емоції та робити ваші події яскравими та незабутніми',
    JSON_ARRAY(
        JSON_OBJECT('icon', '🔥', 'title', 'Професійні артисти', 'text', 'Команда досвідчених виконавців'),
        JSON_OBJECT('icon', '🎭', 'title', 'Унікальні програми', 'text', 'Створюємо шоу під ваш захід'),
        JSON_OBJECT('icon', '🛡️', 'title', 'Безпека', 'text', 'Дотримуємось всіх норм безпеки'),
        JSON_OBJECT('icon', '🌟', 'title', 'Гарантія вражень', 'text', 'Ваші гості будуть в захваті')
    ),
    7,
    300
)
ON DUPLICATE KEY UPDATE section=section;

-- Примеры новостей
INSERT INTO news (section, type, title, content, published_at, is_active) VALUES
(
    'confectionery',
    'announcement',
    'Акція! Знижка 15% на весільні торти',
    'До кінця місяця діє спеціальна акція - знижка 15% на всі весільні торти! Замовляйте зараз та отримайте неймовірний торт за вигідною ціною.',
    NOW(),
    1
),
(
    'fireshow',
    'news',
    'Нова програма: Вогняна фієрія',
    'Представляємо нову захоплюючу програму "Вогняна фієрія" - 25 хвилин неперевершеного шоу з вогняними танцями, акробатикою та піротехнікою!',
    NOW(),
    1
),
(
    'both',
    'announcement',
    'Приймаємо замовлення на Новий Рік!',
    'Вже зараз можна замовити новорічні торти та святкове вогняне шоу! Не відкладайте на останній момент - місць обмежено.',
    NOW(),
    1
);
