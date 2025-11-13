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
    <style>
        .portfolio-card {
            position: relative;
            overflow: hidden;
        }

        .portfolio-card video {
            width: 100%;
            height: 300px;
            object-fit: cover;
            cursor: pointer;
        }

        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.4);
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .portfolio-card:hover .video-overlay {
            opacity: 1;
        }

        .play-button {
            width: 70px;
            height: 70px;
            background: rgba(255, 69, 0, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            box-shadow: 0 4px 20px rgba(255, 69, 0, 0.5);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 4px 20px rgba(255, 69, 0, 0.5); }
            50% { transform: scale(1.1); box-shadow: 0 8px 30px rgba(255, 69, 0, 0.8); }
        }

        /* Модальное окно для видео */
        #videoModal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            align-items: center;
            justify-content: center;
        }

        #videoModal.active {
            display: flex;
        }

        .video-modal-content {
            max-width: 90%;
            max-height: 90%;
            position: relative;
        }

        .video-modal-content video {
            width: 100%;
            max-height: 80vh;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
        }

        .modal-close-btn {
            position: absolute;
            top: -50px;
            right: 0;
            background: rgba(255, 69, 0, 0.9);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-close-btn:hover {
            transform: scale(1.1) rotate(90deg);
            background: rgba(255, 99, 71, 1);
        }

        /* Модальное окно для фото */
        #imageModal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            align-items: center;
            justify-content: center;
        }

        #imageModal.active {
            display: flex;
        }

        .image-modal-content {
            max-width: 90%;
            max-height: 90%;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
            animation: zoomIn 0.3s ease;
        }

        @keyframes zoomIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
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
                                 onclick="openImageModal('../<?= escape($item['media_path']) ?>')">
                        <?php else: ?>
                            <div style="position: relative; cursor: pointer;"
                                 onclick="openVideoModal('../<?= escape($item['media_path']) ?>')">
                                <video class="portfolio-image" preload="metadata">
                                    <source src="../<?= escape($item['media_path']) ?>#t=0.5" type="video/mp4">
                                </video>
                                <div class="video-overlay">
                                    <div class="play-button">▶</div>
                                </div>
                            </div>
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
    <div id="imageModal" onclick="closeImageModal()">
        <button class="modal-close-btn" onclick="closeImageModal()">&times;</button>
        <img class="image-modal-content" id="modalImage" src="" alt="" onclick="event.stopPropagation()">
    </div>

    <!-- Модальное окно для видео -->
    <div id="videoModal" onclick="closeVideoModal()">
        <div class="video-modal-content" onclick="event.stopPropagation()">
            <button class="modal-close-btn" onclick="closeVideoModal()">&times;</button>
            <video id="modalVideo" controls autoplay>
                <source src="" type="video/mp4">
            </video>
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

        // Открыть модальное окно с изображением
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const img = document.getElementById('modalImage');
            img.src = imageSrc;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        // Закрыть модальное окно с изображением
        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Открыть модальное окно с видео
        function openVideoModal(videoSrc) {
            const modal = document.getElementById('videoModal');
            const video = document.getElementById('modalVideo');
            const source = video.querySelector('source');

            source.src = videoSrc;
            video.load();
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Автовоспроизведение
            setTimeout(() => video.play(), 100);
        }

        // Закрыть модальное окно с видео
        function closeVideoModal() {
            const modal = document.getElementById('videoModal');
            const video = document.getElementById('modalVideo');

            video.pause();
            video.currentTime = 0;
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Закрытие по клавише Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeImageModal();
                closeVideoModal();
            }
        });
    </script>
</body>
</html>
