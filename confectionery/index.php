<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Получаем категории
$db = getDB();
$stmt = $db->query("SELECT * FROM categories WHERE active = 1 ORDER BY sort_order");
$categories = $stmt->fetchAll();

// Получаем избранные товары
$stmt = $db->query("SELECT p.*, c.name as category_name
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE p.active = 1 AND p.featured = 1
                    ORDER BY p.created_at DESC
                    LIMIT 6");
$featured_products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кондитерка - Авторские торты и сладости</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/confectionery.css">
</head>
<body>
    <!-- Навигация -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../" class="navbar-logo">🎂 Кондитерка</a>
            <div class="navbar-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="navbar-menu" id="navbarMenu">
                <li><a href="index.php">Главная</a></li>
                <li><a href="catalog.php">Каталог</a></li>
                <li><a href="constructor.php">Конструктор тортов</a></li>
                <li><a href="chat.php">Чат с кондитером</a></li>
                <li><a href="../">← На главную</a></li>
            </ul>
        </div>
    </nav>

    <!-- Баннер -->
    <section class="hero-banner confectionery-banner">
        <div class="hero-content">
            <h1>Авторские торты и сладости</h1>
            <p>Создаём сладкие шедевры с любовью и вниманием к деталям</p>
            <div class="hero-buttons">
                <a href="catalog.php" class="btn btn-primary">Смотреть каталог</a>
                <a href="constructor.php" class="btn btn-secondary">Создать свой торт</a>
            </div>
        </div>
    </section>

    <!-- Категории -->
    <section class="container">
        <h2 class="section-title">Наши категории</h2>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
                <a href="catalog.php?category=<?= $category['slug'] ?>" class="category-card">
                    <div class="category-icon">🧁</div>
                    <h3><?= escape($category['name']) ?></h3>
                    <p><?= escape($category['description']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Избранные товары -->
    <?php if (!empty($featured_products)): ?>
    <section class="container">
        <h2 class="section-title">Популярные товары</h2>
        <div class="products-grid">
            <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <?php if ($product['image']): ?>
                        <img src="../<?= escape($product['image']) ?>" alt="<?= escape($product['name']) ?>" class="product-image">
                    <?php else: ?>
                        <div class="product-image no-image">🎂</div>
                    <?php endif; ?>
                    <div class="product-info">
                        <h3 class="product-title"><?= escape($product['name']) ?></h3>
                        <div class="product-category"><?= escape($product['category_name']) ?></div>
                        <p class="product-description"><?= escape(mb_substr($product['description'], 0, 100)) ?>...</p>
                        <div class="product-footer">
                            <span class="product-price"><?= formatPrice($product['price']) ?></span>
                            <a href="product.php?slug=<?= $product['slug'] ?>" class="btn btn-primary">Подробнее</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Преимущества -->
    <section class="container">
        <h2 class="section-title">Почему выбирают нас</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">✨</div>
                <h3>Качественные продукты</h3>
                <p>Используем только свежие и натуральные ингредиенты</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎨</div>
                <h3>Индивидуальный дизайн</h3>
                <p>Создадим торт по вашему эскизу или фотографии</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🚚</div>
                <h3>Доставка в срок</h3>
                <p>Привезём свежий торт точно в назначенное время</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3>Консультации</h3>
                <p>Поможем выбрать идеальный вариант для вашего праздника</p>
            </div>
        </div>
    </section>

    <!-- CTA секция -->
    <section class="cta-section">
        <div class="container">
            <h2>Хотите создать уникальный торт?</h2>
            <p>Воспользуйтесь нашим конструктором или свяжитесь с кондитером</p>
            <div class="cta-buttons">
                <a href="constructor.php" class="btn btn-primary">Конструктор тортов</a>
                <a href="chat.php" class="btn btn-secondary">Чат с кондитером</a>
            </div>
        </div>
    </section>

    <!-- Футер -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Контакты</h3>
                    <p>Телефон: <?= escape(getSetting('site_phone')) ?></p>
                    <p>Email: <?= escape(getSetting('site_email')) ?></p>
                </div>
                <div class="footer-section">
                    <h3>Навигация</h3>
                    <ul>
                        <li><a href="catalog.php">Каталог</a></li>
                        <li><a href="constructor.php">Конструктор</a></li>
                        <li><a href="chat.php">Чат</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Мы в соцсетях</h3>
                    <div class="social-links">
                        <?php if (getSetting('vk_link')): ?>
                            <a href="<?= escape(getSetting('vk_link')) ?>" target="_blank">VK</a>
                        <?php endif; ?>
                        <?php if (getSetting('instagram_link')): ?>
                            <a href="<?= escape(getSetting('instagram_link')) ?>" target="_blank">Instagram</a>
                        <?php endif; ?>
                        <?php if (getSetting('telegram_link')): ?>
                            <a href="<?= escape(getSetting('telegram_link')) ?>" target="_blank">Telegram</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Кондитерка. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('navbarMenu');
            const toggle = document.querySelector('.navbar-toggle');
            menu.classList.toggle('active');
            toggle.classList.toggle('active');
        }

        document.querySelectorAll('.navbar-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('navbarMenu').classList.remove('active');
                document.querySelector('.navbar-toggle').classList.remove('active');
            });
        });
    </script>
</body>
</html>
