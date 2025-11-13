<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/language.php';

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
<html lang="<?= getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('confectionery_page_title') ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/confectionery.css">
</head>
<body>
    <!-- Переключатель языков -->
    <?php include __DIR__ . '/../includes/language-switcher.php'; ?>

    <!-- Навигация -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../" class="navbar-logo"><?= t('confectionery_logo') ?></a>
            <div class="navbar-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="navbar-menu" id="navbarMenu">
                <li><a href="index.php"><?= t('home') ?></a></li>
                <li><a href="catalog.php"><?= t('catalog') ?></a></li>
                <li><a href="constructor.php"><?= t('cake_constructor') ?></a></li>
                <li><a href="chat.php"><?= t('chat_with_confectioner') ?></a></li>
                <li><a href="../"><?= t('back_to_main') ?></a></li>
            </ul>
        </div>
    </nav>

    <!-- Баннер -->
    <section class="hero-banner confectionery-banner">
        <div class="hero-content">
            <h1><?= t('confectionery_hero_title') ?></h1>
            <p><?= t('confectionery_hero_description') ?></p>
            <div class="hero-buttons">
                <a href="catalog.php" class="btn btn-primary"><?= t('view_catalog') ?></a>
                <a href="constructor.php" class="btn btn-secondary"><?= t('create_your_cake') ?></a>
            </div>
        </div>
    </section>

    <!-- Категории -->
    <section class="container">
        <h2 class="section-title"><?= t('our_categories') ?></h2>
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
        <h2 class="section-title"><?= t('featured_products') ?></h2>
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
                            <a href="product.php?slug=<?= $product['slug'] ?>" class="btn btn-primary"><?= t('more') ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Виджет новостей -->
    <div class="container">
        <?php
        $section = 'confectionery';
        $limit = 3;
        include __DIR__ . '/../includes/news-widget.php';
        ?>
    </div>

    <!-- Преимущества -->
    <section class="container">
        <h2 class="section-title"><?= t('why_choose_us') ?></h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">✨</div>
                <h3><?= t('quality_products') ?></h3>
                <p><?= t('quality_products_desc') ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎨</div>
                <h3><?= t('individual_design') ?></h3>
                <p><?= t('individual_design_desc') ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🚚</div>
                <h3><?= t('delivery_on_time') ?></h3>
                <p><?= t('delivery_on_time_desc') ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3><?= t('consultations') ?></h3>
                <p><?= t('consultations_desc') ?></p>
            </div>
        </div>
    </section>

    <!-- CTA секция -->
    <section class="cta-section">
        <div class="container">
            <h2><?= t('want_unique_cake') ?></h2>
            <p><?= t('use_constructor_or_chat') ?></p>
            <div class="cta-buttons">
                <a href="constructor.php" class="btn btn-primary"><?= t('cake_constructor') ?></a>
                <a href="chat.php" class="btn btn-secondary"><?= t('chat_with_confectioner') ?></a>
            </div>
        </div>
    </section>

    <!-- Футер -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><?= t('contacts') ?></h3>
                    <p><?= t('phone') ?>: <?= escape(getSetting('site_phone')) ?></p>
                    <p><?= t('email') ?>: <?= escape(getSetting('site_email')) ?></p>
                </div>
                <div class="footer-section">
                    <h3><?= t('navigation') ?></h3>
                    <ul>
                        <li><a href="catalog.php"><?= t('catalog') ?></a></li>
                        <li><a href="constructor.php"><?= t('cake_constructor') ?></a></li>
                        <li><a href="chat.php"><?= t('chat') ?></a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3><?= t('social_networks') ?></h3>
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
                <p>&copy; 2025 <?= t('confectionery') ?>. <?= t('all_rights_reserved') ?>.</p>
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
