<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$db = getDB();

// Получаем все ингредиенты по типам
$types = ['base', 'filling', 'cream', 'decoration', 'topping'];
$ingredients = [];

foreach ($types as $type) {
    $stmt = $db->prepare("SELECT * FROM cake_ingredients WHERE type = ? AND active = 1 ORDER BY name");
    $stmt->execute([$type]);
    $ingredients[$type] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Конструктор тортов - Кондитерка</title>
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

    <div class="constructor-container">
        <h1 class="section-title">Конструктор тортов 🎂</h1>
        <p style="text-align: center; margin-bottom: 2rem; color: #666;">
            Создайте торт своей мечты! Выберите основу, начинку, крем и украшения.
        </p>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <div>
                <!-- Шаг 1: Основа -->
                <div class="constructor-step">
                    <h3>1. Выберите основу торта</h3>
                    <div class="ingredients-grid">
                        <?php foreach ($ingredients['base'] as $item): ?>
                            <div class="ingredient-item" data-type="base" data-id="<?= $item['id'] ?>" data-name="<?= escape($item['name']) ?>" data-price="<?= $item['price'] ?>" onclick="selectIngredient(this, 'base')">
                                <div class="ingredient-icon">🍰</div>
                                <div class="ingredient-name"><?= escape($item['name']) ?></div>
                                <div class="ingredient-price"><?= formatPrice($item['price']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Шаг 2: Начинка -->
                <div class="constructor-step">
                    <h3>2. Выберите начинку</h3>
                    <div class="ingredients-grid">
                        <?php foreach ($ingredients['filling'] as $item): ?>
                            <div class="ingredient-item" data-type="filling" data-id="<?= $item['id'] ?>" data-name="<?= escape($item['name']) ?>" data-price="<?= $item['price'] ?>" onclick="selectIngredient(this, 'filling')">
                                <div class="ingredient-icon">🍓</div>
                                <div class="ingredient-name"><?= escape($item['name']) ?></div>
                                <div class="ingredient-price"><?= formatPrice($item['price']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Шаг 3: Крем -->
                <div class="constructor-step">
                    <h3>3. Выберите крем</h3>
                    <div class="ingredients-grid">
                        <?php foreach ($ingredients['cream'] as $item): ?>
                            <div class="ingredient-item" data-type="cream" data-id="<?= $item['id'] ?>" data-name="<?= escape($item['name']) ?>" data-price="<?= $item['price'] ?>" onclick="selectIngredient(this, 'cream')">
                                <div class="ingredient-icon">🍦</div>
                                <div class="ingredient-name"><?= escape($item['name']) ?></div>
                                <div class="ingredient-price"><?= formatPrice($item['price']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Шаг 4: Украшения -->
                <div class="constructor-step">
                    <h3>4. Выберите украшения</h3>
                    <div class="ingredients-grid">
                        <?php foreach ($ingredients['decoration'] as $item): ?>
                            <div class="ingredient-item" data-type="decoration" data-id="<?= $item['id'] ?>" data-name="<?= escape($item['name']) ?>" data-price="<?= $item['price'] ?>" onclick="selectIngredient(this, 'decoration')">
                                <div class="ingredient-icon">🌸</div>
                                <div class="ingredient-name"><?= escape($item['name']) ?></div>
                                <div class="ingredient-price"><?= formatPrice($item['price']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Шаг 5: Топпинг -->
                <div class="constructor-step">
                    <h3>5. Выберите топпинг (необязательно)</h3>
                    <div class="ingredients-grid">
                        <?php foreach ($ingredients['topping'] as $item): ?>
                            <div class="ingredient-item" data-type="topping" data-id="<?= $item['id'] ?>" data-name="<?= escape($item['name']) ?>" data-price="<?= $item['price'] ?>" onclick="selectIngredient(this, 'topping')">
                                <div class="ingredient-icon">🍫</div>
                                <div class="ingredient-name"><?= escape($item['name']) ?></div>
                                <div class="ingredient-price"><?= formatPrice($item['price']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Итоговая сводка -->
            <div>
                <div class="constructor-summary">
                    <h3>Ваш торт</h3>
                    <div id="summary">
                        <div class="summary-item">
                            <span>Основа:</span>
                            <span id="sum-base">Не выбрано</span>
                        </div>
                        <div class="summary-item">
                            <span>Начинка:</span>
                            <span id="sum-filling">Не выбрано</span>
                        </div>
                        <div class="summary-item">
                            <span>Крем:</span>
                            <span id="sum-cream">Не выбрано</span>
                        </div>
                        <div class="summary-item">
                            <span>Украшения:</span>
                            <span id="sum-decoration">Не выбрано</span>
                        </div>
                        <div class="summary-item">
                            <span>Топпинг:</span>
                            <span id="sum-topping">Не выбрано</span>
                        </div>
                    </div>
                    <div class="summary-total">
                        Итого: <span id="total-price">0 грн</span>
                    </div>
                    <button class="btn btn-primary" style="width: 100%; margin-top: 1rem;" onclick="openOrderModal()">
                        Заказать торт
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно заказа -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeOrderModal()">&times;</button>
            <h2>Оформление заказа</h2>
            <form id="constructorOrderForm" onsubmit="submitConstructorOrder(event)">
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
                    <textarea name="comment" class="form-control" placeholder="Дополнительные пожелания..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Отправить заказ</button>
            </form>
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
        const selected = {
            base: null,
            filling: null,
            cream: null,
            decoration: null,
            topping: null
        };

        function selectIngredient(element, type) {
            // Убираем выделение с предыдущего элемента этого типа
            document.querySelectorAll(`.ingredient-item[data-type="${type}"]`).forEach(item => {
                item.classList.remove('selected');
            });

            // Если клик по уже выбранному элементу - отменяем выбор
            if (selected[type] && selected[type].id === parseInt(element.dataset.id)) {
                selected[type] = null;
            } else {
                // Выделяем новый элемент
                element.classList.add('selected');
                selected[type] = {
                    id: parseInt(element.dataset.id),
                    name: element.dataset.name,
                    price: parseFloat(element.dataset.price)
                };
            }

            updateSummary();
        }

        function updateSummary() {
            let total = 0;

            Object.keys(selected).forEach(type => {
                const sumElement = document.getElementById(`sum-${type}`);
                if (selected[type]) {
                    sumElement.textContent = selected[type].name;
                    total += selected[type].price;
                } else {
                    sumElement.textContent = 'Не выбрано';
                }
            });

            document.getElementById('total-price').textContent = total.toLocaleString('ru-RU') + ' грн';
        }

        function openOrderModal() {
            if (!selected.base || !selected.filling || !selected.cream) {
                alert('Пожалуйста, выберите хотя бы основу, начинку и крем');
                return;
            }
            document.getElementById('orderModal').classList.add('active');
        }

        function closeOrderModal() {
            document.getElementById('orderModal').classList.remove('active');
        }

        async function submitConstructorOrder(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('order_type', 'custom');
            formData.append('constructor_data', JSON.stringify(selected));

            let totalPrice = 0;
            Object.values(selected).forEach(item => {
                if (item) totalPrice += item.price;
            });
            formData.append('total_price', totalPrice);

            try {
                const response = await fetch('../api/orders.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Заказ успешно оформлен! Мы свяжемся с вами в ближайшее время.');
                    closeOrderModal();
                    e.target.reset();
                    // Сброс выбора
                    Object.keys(selected).forEach(key => selected[key] = null);
                    document.querySelectorAll('.ingredient-item').forEach(item => {
                        item.classList.remove('selected');
                    });
                    updateSummary();
                } else {
                    alert('Ошибка: ' + result.error);
                }
            } catch (error) {
                alert('Произошла ошибка при отправке заказа');
            }
        }

        // Закрытие модального окна по клику вне его
        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target === modal) {
                closeOrderModal();
            }
        }
    </script>
</body>
</html>
