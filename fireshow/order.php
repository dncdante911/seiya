<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM fireshow_programs WHERE active = 1 ORDER BY name");
$programs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказать фаершоу</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/fireshow.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../" class="navbar-logo">🔥 Фаершоу</a>
            <ul class="navbar-menu">
                <li><a href="index.php">Главная</a></li>
                <li><a href="programs.php">Программы</a></li>
                <li><a href="portfolio.php">Портфолио</a></li>
                <li><a href="order.php">Заказать шоу</a></li>
                <li><a href="../">← На главную</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1 class="section-title">Заказать фаершоу</h1>

        <div class="order-form-container">
            <form id="fireshowOrderForm" onsubmit="submitFireshowOrder(event)">
                <!-- Выбор программы -->
                <div class="form-section">
                    <h3>1. Выберите программу</h3>
                    <div class="program-selector">
                        <?php foreach ($programs as $program): ?>
                            <div class="program-option" data-id="<?= $program['id'] ?>"
                                 data-price="<?= $program['price_per_artist'] ?>"
                                 data-min="<?= $program['min_artists'] ?>"
                                 data-max="<?= $program['max_artists'] ?>"
                                 onclick="selectProgram(this)">
                                <h4><?= escape($program['name']) ?></h4>
                                <p><?= formatPrice($program['price_per_artist']) ?> / артист</p>
                                <?php if ($program['duration']): ?>
                                    <p>⏱️ <?= $program['duration'] ?> мин</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="program_id" id="programId" required>
                </div>

                <!-- Количество артистов -->
                <div class="form-section">
                    <h3>2. Количество артистов</h3>
                    <div class="form-group">
                        <label>Выберите количество артистов *</label>
                        <select name="num_artists" id="numArtists" class="form-control" required onchange="calculateTotal()">
                            <option value="">Сначала выберите программу</option>
                        </select>
                    </div>
                    <div style="font-size: 1.2rem; margin-top: 1rem;">
                        <strong>Итоговая стоимость: <span id="totalPrice">0 грн</span></strong>
                    </div>
                </div>

                <!-- Данные заказчика -->
                <div class="form-section">
                    <h3>3. Ваши данные</h3>
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
                </div>

                <!-- Детали мероприятия -->
                <div class="form-section">
                    <h3>4. Детали мероприятия</h3>
                    <div class="form-group">
                        <label>Дата мероприятия *</label>
                        <input type="date" name="event_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Время</label>
                        <input type="time" name="event_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Тип мероприятия</label>
                        <select name="event_type" class="form-control">
                            <option value="">Выберите...</option>
                            <option value="Свадьба">Свадьба</option>
                            <option value="День рождения">День рождения</option>
                            <option value="Корпоратив">Корпоратив</option>
                            <option value="Фестиваль">Фестиваль</option>
                            <option value="Другое">Другое</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Место проведения *</label>
                        <textarea name="event_location" class="form-control" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Комментарий</label>
                        <textarea name="comment" class="form-control" placeholder="Дополнительные пожелания..."></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Отправить заказ</button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 Фаершоу. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <script>
        let selectedProgram = null;

        function selectProgram(element) {
            document.querySelectorAll('.program-option').forEach(opt => {
                opt.classList.remove('selected');
            });

            element.classList.add('selected');
            selectedProgram = {
                id: element.dataset.id,
                price: parseFloat(element.dataset.price),
                min: parseInt(element.dataset.min),
                max: parseInt(element.dataset.max)
            };

            document.getElementById('programId').value = selectedProgram.id;

            // Обновляем список количества артистов
            const numArtistsSelect = document.getElementById('numArtists');
            numArtistsSelect.innerHTML = '';

            for (let i = selectedProgram.min; i <= selectedProgram.max; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i + ' артист' + (i > 1 ? (i < 5 ? 'а' : 'ов') : '');
                numArtistsSelect.appendChild(option);
            }

            calculateTotal();
        }

        function calculateTotal() {
            if (!selectedProgram) return;

            const numArtists = parseInt(document.getElementById('numArtists').value) || selectedProgram.min;
            const total = selectedProgram.price * numArtists;

            document.getElementById('totalPrice').textContent = total.toLocaleString('ru-RU') + ' грн';
        }

        async function submitFireshowOrder(e) {
            e.preventDefault();

            if (!selectedProgram) {
                alert('Пожалуйста, выберите программу');
                return;
            }

            const formData = new FormData(e.target);
            const numArtists = parseInt(formData.get('num_artists'));
            const totalPrice = selectedProgram.price * numArtists;
            formData.append('total_price', totalPrice);

            try {
                const response = await fetch('../api/orders_fireshow.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert('Заказ успешно оформлен! Мы свяжемся с вами в ближайшее время.');
                    e.target.reset();
                    selectedProgram = null;
                    document.querySelectorAll('.program-option').forEach(opt => {
                        opt.classList.remove('selected');
                    });
                    document.getElementById('totalPrice').textContent = '0 грн';
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
