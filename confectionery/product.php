<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$db = getDB();
$slug = $_GET['slug'] ?? '';

if (!$slug) {
    redirect('catalog.php');
}

// Получаем товар
$stmt = $db->prepare("SELECT p.*, c.name as category_name
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE p.slug = ? AND p.active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    redirect('catalog.php');
}

// Получаем дополнительные изображения
$stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
$stmt->execute([$product['id']]);
$images = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($product['name']) ?> - Кондитерка</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/confectionery.css">
</head>
<body>
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
        <div class="product-detail">
            <!-- Галерея -->
            <div class="product-gallery">
                <?php if ($product['image']): ?>
                    <img src="../<?= escape($product['image']) ?>" alt="<?= escape($product['name']) ?>" class="main-image" id="mainImage">
                <?php else: ?>
                    <div class="main-image no-image">🎂</div>
                <?php endif; ?>

                <?php if (!empty($images)): ?>
                    <div class="thumbnail-images">
                        <?php if ($product['image']): ?>
                            <img src="../<?= escape($product['image']) ?>" class="thumbnail active" onclick="changeMainImage(this)">
                        <?php endif; ?>
                        <?php foreach ($images as $img): ?>
                            <img src="../<?= escape($img['image_path']) ?>" class="thumbnail" onclick="changeMainImage(this)">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($product['video']): ?>
                    <video controls style="width: 100%; border-radius: 15px; margin-top: 1rem;">
                        <source src="../<?= escape($product['video']) ?>" type="video/mp4">
                    </video>
                <?php endif; ?>
            </div>

            <!-- Информация -->
            <div class="product-details">
                <span class="product-category"><?= escape($product['category_name']) ?></span>
                <h1><?= escape($product['name']) ?></h1>

                <div class="product-meta">
                    <?php if ($product['weight']): ?>
                        <div class="meta-item">⚖️ Вес: <?= escape($product['weight']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="product-price-large"><?= formatPrice($product['price']) ?></div>

                <div class="product-description-full">
                    <?= nl2br(escape($product['description'])) ?>
                </div>

                <?php if ($product['ingredients']): ?>
                    <div class="ingredients-section">
                        <h3>Состав:</h3>
                        <ul class="ingredients-list">
                            <?php
                            $ingredients = explode(',', $product['ingredients']);
                            foreach ($ingredients as $ingredient):
                            ?>
                                <li><?= escape(trim($ingredient)) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="order-section">
                    <h3>Заказать товар</h3>
                    <form id="orderForm" onsubmit="submitOrder(event)">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                        <div class="form-group">
                            <label>Ваше имя *</label>
                            <input type="text" name="customer_name" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Телефон *</label>
                            <input type="tel" name="customer_phone" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="customer_email" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Дата доставки *</label>
                            <input type="date" name="delivery_date" class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label>Комментарий</label>
                            <textarea name="comment" class="form-control"></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width: 100%;">Оформить заказ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 Кондитерка. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script>
        function changeMainImage(thumbnail) {
            document.getElementById('mainImage').src = thumbnail.src;
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            thumbnail.classList.add('active');
        }

        async function submitOrder(e) {
            e.preventDefault();
            const formData = new FormData(e.target);

            try {
                const response = await fetch('../api/orders.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Заказ успешно оформлен! Мы свяжемся с вами в ближайшее время.');
                    e.target.reset();
                } else {
                    alert('Ошибка: ' + result.error);
                }
            } catch (error) {
                alert('Произошла ошибка при отправке заказа');
            }
        }
    </script>
</body>
</html>
