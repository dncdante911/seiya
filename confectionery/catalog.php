<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$db = getDB();

// Получаем категории для фильтра
$stmt = $db->query("SELECT * FROM categories WHERE active = 1 ORDER BY sort_order");
$categories = $stmt->fetchAll();

// Получаем параметры фильтрации
$category_filter = $_GET['category'] ?? null;
$search_query = trim($_GET['search'] ?? '');
$sort_by = $_GET['sort'] ?? 'newest';
$category_id = null;

if ($category_filter) {
    $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
    $stmt->execute([$category_filter]);
    $cat = $stmt->fetch();
    if ($cat) {
        $category_id = $cat['id'];
    }
}

// Получаем товары с фильтрацией
$sql = "SELECT p.*, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.active = 1";

if ($category_id) {
    $sql .= " AND p.category_id = " . intval($category_id);
}

if ($search_query) {
    $sql .= " AND (p.name LIKE '%" . $db->quote($search_query) . "%'
              OR p.description LIKE '%" . $db->quote($search_query) . "%')";
}

// Сортировка
switch ($sort_by) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'name':
        $sql .= " ORDER BY p.name ASC";
        break;
    default: // newest
        $sql .= " ORDER BY p.created_at DESC";
}

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
    <style>
        .catalog-controls {
            display: flex;
            gap: 1rem;
            margin: 2rem 0;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 0.8rem 2.5rem 0.8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-box input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 107, 157, 0.1);
        }

        .search-box button {
            position: absolute;
            right: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            background: var(--primary-color);
            border: none;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .search-box button:hover {
            transform: translateY(-50%) scale(1.1);
        }

        .sort-select {
            padding: 0.8rem 1.2rem;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sort-select:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .results-count {
            color: #666;
            font-size: 0.95rem;
            margin-left: auto;
        }

        .product-card {
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            cursor: pointer;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .catalog-controls {
                flex-direction: column;
            }

            .search-box {
                width: 100%;
            }

            .results-count {
                margin-left: 0;
                width: 100%;
                text-align: center;
            }
        }
    </style>
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

    <div class="container">
        <h1 class="section-title">Каталог товаров</h1>

        <!-- Поиск и сортировка -->
        <div class="catalog-controls">
            <form class="search-box" method="GET" action="catalog.php">
                <?php if ($category_filter): ?>
                    <input type="hidden" name="category" value="<?= escape($category_filter) ?>">
                <?php endif; ?>
                <input type="text"
                       name="search"
                       placeholder="Поиск товаров..."
                       value="<?= escape($search_query) ?>">
                <button type="submit">🔍</button>
            </form>

            <select class="sort-select" onchange="window.location.href=this.value">
                <option value="?<?= http_build_query(array_merge($_GET, ['sort' => 'newest'])) ?>"
                        <?= $sort_by === 'newest' ? 'selected' : '' ?>>
                    Сначала новые
                </option>
                <option value="?<?= http_build_query(array_merge($_GET, ['sort' => 'price_asc'])) ?>"
                        <?= $sort_by === 'price_asc' ? 'selected' : '' ?>>
                    Цена: по возрастанию
                </option>
                <option value="?<?= http_build_query(array_merge($_GET, ['sort' => 'price_desc'])) ?>"
                        <?= $sort_by === 'price_desc' ? 'selected' : '' ?>>
                    Цена: по убыванию
                </option>
                <option value="?<?= http_build_query(array_merge($_GET, ['sort' => 'name'])) ?>"
                        <?= $sort_by === 'name' ? 'selected' : '' ?>>
                    По названию
                </option>
            </select>

            <div class="results-count">
                Найдено товаров: <strong><?= count($products) ?></strong>
            </div>
        </div>

        <!-- Фильтры по категориям -->
        <div class="catalog-header">
            <div class="filter-buttons">
                <a href="catalog.php?<?= $search_query ? 'search=' . urlencode($search_query) : '' ?>"
                   class="filter-btn <?= !$category_filter ? 'active' : '' ?>">
                    Все товары
                </a>
                <?php foreach ($categories as $category): ?>
                    <a href="catalog.php?category=<?= $category['slug'] ?><?= $search_query ? '&search=' . urlencode($search_query) : '' ?>"
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

    <!-- Модальное окно для просмотра изображений -->
    <div id="imageModal" class="modal" onclick="closeImageModal()">
        <span class="modal-close">&times;</span>
        <img id="modalImage" class="modal-content" onclick="event.stopPropagation()">
    </div>

    <script src="../assets/js/confectionery.js"></script>
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

        // Открытие изображений в модальном окне
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'flex';
            modalImg.src = imageSrc;
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Добавляем обработчики клика на изображения
        document.querySelectorAll('.product-image').forEach(img => {
            if (img.tagName === 'IMG') {
                img.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openImageModal(this.src);
                });
            }
        });

        // Закрытие по Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeImageModal();
            }
        });
    </script>

    <style>
        /* Модальное окно */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            animation: zoomIn 0.3s ease;
        }

        @keyframes zoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 40px;
            color: white;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10000;
        }

        .modal-close:hover {
            transform: scale(1.2) rotate(90deg);
        }
    </style>
</body>
</html>
