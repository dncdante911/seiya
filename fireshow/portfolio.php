<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM fireshow_portfolio WHERE active = 1 ORDER BY sort_order, created_at DESC");
$portfolio = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Портфолио - Фаершоу</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/fireshow.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../" class="navbar-logo">🔥 Фаершоу</a>
            <div class="navbar-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="navbar-menu" id="navbarMenu">
                <li><a href="index.php">Главная</a></li>
                <li><a href="programs.php">Программы</a></li>
                <li><a href="portfolio.php">Портфолио</a></li>
                <li><a href="order.php">Заказать шоу</a></li>
                <li><a href="../">← На главную</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1 class="section-title">Наше портфолио</h1>
        <p style="text-align: center; margin-bottom: 3rem; color: #666;">
            Фото и видео наших лучших выступлений
        </p>

        <?php if (empty($portfolio)): ?>
            <div class="alert alert-info">
                <p>Портфолио пока пусто. Скоро здесь появятся наши работы!</p>
            </div>
        <?php else: ?>
            <div class="portfolio-grid">
                <?php foreach ($portfolio as $item): ?>
                    <div class="portfolio-card">
                        <?php if ($item['media_type'] === 'image'): ?>
                            <img src="../<?= escape($item['thumbnail'] ?: $item['media_path']) ?>"
                                 alt="<?= escape($item['title']) ?>"
                                 class="portfolio-image"
                                 onclick="openModal('<?= escape($item['media_path']) ?>', 'image')">
                        <?php else: ?>
                            <video class="portfolio-image" controls>
                                <source src="../<?= escape($item['media_path']) ?>" type="video/mp4">
                            </video>
                        <?php endif; ?>

                        <div class="portfolio-info">
                            <h3><?= escape($item['title']) ?></h3>
                            <?php if ($item['description']): ?>
                                <p><?= escape($item['description']) ?></p>
                            <?php endif; ?>
                            <?php if ($item['event_date']): ?>
                                <p class="portfolio-date">📅 <?= formatDate($item['event_date']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Модальное окно для просмотра изображений -->
    <div id="imageModal" class="modal" onclick="closeModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <img id="modalImage" src="" alt="" style="max-width: 100%; max-height: 80vh;">
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
        function toggleMenu() {
            const menu = document.getElementById('navbarMenu');
            const toggle = document.querySelector('.navbar-toggle');
            menu.classList.toggle('active');
            toggle.classList.toggle('active');
        }

        // Закрыть меню при клике на пункт
        document.querySelectorAll('.navbar-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('navbarMenu').classList.remove('active');
                document.querySelector('.navbar-toggle').classList.remove('active');
            });
        });

        function openModal(imageSrc, type) {
            if (type === 'image') {
                document.getElementById('modalImage').src = '../' + imageSrc;
                document.getElementById('imageModal').classList.add('active');
            }
        }

        function closeModal() {
            document.getElementById('imageModal').classList.remove('active');
        }
    </script>
</body>
</html>
