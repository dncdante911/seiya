<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$db = getDB();

// Получаем категории для фильтра
$stmt = $db->query("SELECT * FROM categories WHERE active = 1 ORDER BY sort_order");
$categories = $stmt->fetchAll();

// Фильтрация по категории
$category_filter = isset($_GET['category']) ? $_GET['category'] : null;
$category_id = null;

if ($category_filter) {
    $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
    $stmt->execute([$category_filter]);
    $cat = $stmt->fetch();
    if ($cat) {
        $category_id = $cat['id'];
    }
}

// Получаем товары
$sql = "SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.active = 1";

if ($category_id) {
    $sql .= " AND p.category_id = " . intval($category_id);
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $db->query($sql);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог товаров - Кондитерка</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/confectionery.css">
</head>
<body>
    <!-- Навигация -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../" class="navbar-logo">🎂 Кондитерка</a>
            <ul class="navbar-menu">
                <li><a href="index.php">Главная</a></li>
                <li><a href="catalog.php">Каталог</a></li>
                <li><a href="constructor.php">Конструктор тортов</a></li>
                <li><a href="chat.php">Чат с кондитером</a></li>
                <li><a href="../">← На главную</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1 class="section-title">Каталог товаров</h1>

        <!-- Фильтры -->
        <div class="catalog-header">
            <div class="filter-buttons">
                <a href="catalog.php" class="filter-btn <?= !$category_filter ? 'active' : '' ?>">
                    Все товары
                </a>
                <?php foreach ($categories as $category): ?>
                    <a href="catalog.php?category=<?= $category['slug'] ?>"
                       class="filter-btn <?= $category_filter === $category['slug'] ? 'active' : '' ?>">
                        <?= escape($category['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Товары -->
        <?php if (empty($products)): ?>
            <div class="alert alert-info">
                <p>Товары в данной категории пока отсутствуют.</p>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <?php if ($product['image']): ?>
                            <img src="../<?= escape($product['image']) ?>"
                                 alt="<?= escape($product['name']) ?>"
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image no-image">🎂</div>
                        <?php endif; ?>

                        <div class="product-info">
                            <span class="product-category"><?= escape($product['category_name']) ?></span>
                            <h3 class="product-title"><?= escape($product['name']) ?></h3>

                            <?php if ($product['weight']): ?>
                                <div class="meta-item">
                                    <span>⚖️ <?= escape($product['weight']) ?></span>
                                </div>
                            <?php endif; ?>

                            <p class="product-description">
                                <?= escape(mb_substr($product['description'], 0, 150)) ?>...
                            </p>

                            <div class="product-footer">
                                <span class="product-price"><?= formatPrice($product['price']) ?></span>
                                <a href="product.php?slug=<?= $product['slug'] ?>" class="btn btn-primary">
                                    Подробнее
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

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
</body>
</html>
