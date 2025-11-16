<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/language.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM fireshow_portfolio WHERE active = 1 ORDER BY sort_order, created_at DESC");
$portfolio = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('portfolio') ?> - <?= t('fireshow') ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/fireshow.css">

    <!-- Video.js -->
    <link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
    <script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>

    <style>
        .portfolio-header {
            text-align: center;
            padding: 2rem 0 3rem;
        }

        .portfolio-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .portfolio-header p {
            font-size: 1.1rem;
            color: #666;
        }

        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .portfolio-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .portfolio-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        .portfolio-media {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
            background: #000;
            cursor: pointer;
        }

        .portfolio-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .portfolio-card:hover .portfolio-media img {
            transform: scale(1.05);
        }

        .portfolio-media video {
            width: 100%;
            height: 100%;
            object-fit: cover;
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
            transition: all 0.3s ease;
        }

        .portfolio-card:hover .video-overlay {
            background: rgba(0, 0, 0, 0.6);
        }

        .play-button {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #ff6b00, #ff4500);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            box-shadow: 0 4px 20px rgba(255, 69, 0, 0.5);
            transition: all 0.3s ease;
        }

        .portfolio-card:hover .play-button {
            transform: scale(1.1);
            box-shadow: 0 8px 30px rgba(255, 69, 0, 0.8);
        }

        .portfolio-info {
            padding: 1.5rem;
        }

        .portfolio-info h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        .portfolio-info p {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 0.75rem;
        }

        .portfolio-date {
            color: #ff6b00 !important;
            font-weight: 500;
            font-size: 0.9rem;
        }

        /* Модальное окно для изображений */
        .modal {
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

        .modal.active {
            display: flex;
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ff6b00, #ff4500);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10001;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .modal-close:hover {
            transform: rotate(90deg) scale(1.1);
            box-shadow: 0 0 20px rgba(255, 69, 0, 0.8);
        }

        .image-modal-content {
            max-width: 90%;
            max-height: 90%;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
            animation: zoomIn 0.3s ease;
        }

        @keyframes zoomIn {
            from {
                transform: scale(0.8);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Модальное окно для видео */
        .video-modal-wrapper {
            position: relative;
            width: 90%;
            max-width: 1400px;
            animation: zoomIn 0.3s ease;
        }

        .video-modal-content {
            width: 100%;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
        }

        .video-modal-content video,
        .video-modal-content .video-js {
            width: 100%;
            height: auto;
            display: block;
            background: #000;
        }

        /* Video.js кастомизация */
        .video-js {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .video-js .vjs-big-play-button {
            background: linear-gradient(135deg, #ff6b00, #ff4500);
            border: none;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            line-height: 80px;
            font-size: 3rem;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.3s ease;
        }

        .video-js:hover .vjs-big-play-button {
            background: linear-gradient(135deg, #ff8c00, #ff6347);
            transform: translate(-50%, -50%) scale(1.1);
        }

        .video-js .vjs-control-bar {
            background: linear-gradient(to top, rgba(0,0,0,0.8), rgba(0,0,0,0.4));
            height: 4em;
        }

        .video-js .vjs-play-progress,
        .video-js .vjs-volume-level {
            background-color: #ff6b00;
        }

        .video-js .vjs-slider {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .video-js .vjs-load-progress {
            background: rgba(255, 255, 255, 0.2);
        }

        .video-js .vjs-control:focus:before,
        .video-js .vjs-control:hover:before,
        .video-js .vjs-control:focus {
            text-shadow: 0 0 1em #ff6b00, 0 0 2em #ff6b00;
        }

        .video-js .vjs-button > .vjs-icon-placeholder:before {
            font-size: 1.8em;
            line-height: 2.2;
        }

        .vjs-modal-dialog .vjs-modal-dialog-content {
            padding-top: 40px;
        }

        @media (max-width: 768px) {
            .portfolio-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .portfolio-header h1 {
                font-size: 2rem;
            }

            .portfolio-media {
                height: 200px;
            }

            .modal-close {
                top: 10px;
                right: 10px;
                width: 40px;
                height: 40px;
                font-size: 24px;
            }

            .video-modal-wrapper {
                width: 95%;
            }
        }

        /* Пустое состояние */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .empty-state h2 {
            font-size: 1.5rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #666;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../" class="navbar-logo"><?= t('fireshow_logo') ?></a>
            <div class="navbar-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="navbar-menu" id="navbarMenu">
                <li><a href="index.php"><?= t('home') ?></a></li>
                <li><a href="programs.php"><?= t('programs') ?></a></li>
                <li><a href="portfolio.php"><?= t('portfolio') ?></a></li>
                <li><a href="order.php"><?= t('order_show') ?></a></li>
                <li><a href="../"><?= t('back_to_main') ?></a></li>
                <li class="navbar-lang">
                    <?php include __DIR__ . '/../includes/language-switcher.php'; ?>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="portfolio-header">
            <h1><?= t('our_portfolio') ?></h1>
            <p><?= t('portfolio_description') ?></p>
        </div>

        <?php if (empty($portfolio)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎬</div>
                <h2><?= t('no_portfolio_yet') ?></h2>
            </div>
        <?php else: ?>
            <div class="portfolio-grid">
                <?php foreach ($portfolio as $item): ?>
                    <div class="portfolio-card">
                        <div class="portfolio-media">
                            <?php if ($item['media_type'] === 'image'): ?>
                                <img src="../<?= escape($item['media_path']) ?>"
                                     alt="<?= escape($item['title']) ?>"
                                     onclick="openImageModal('../<?= escape($item['media_path']) ?>')">
                            <?php else: ?>
                                <div onclick="openVideoModal('../<?= escape($item['media_path']) ?>')">
                                    <?php if ($item['thumbnail']): ?>
                                        <img src="../<?= escape($item['thumbnail']) ?>"
                                             alt="<?= escape($item['title']) ?>">
                                    <?php else: ?>
                                        <video preload="metadata">
                                            <source src="../<?= escape($item['media_path']) ?>#t=0.5" type="video/mp4">
                                        </video>
                                    <?php endif; ?>
                                    <div class="video-overlay">
                                        <div class="play-button">▶</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

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

    <!-- Модальное окно для изображений -->
    <div id="imageModal" class="modal" onclick="closeImageModal()">
        <button class="modal-close" onclick="closeImageModal(); event.stopPropagation()">×</button>
        <img class="image-modal-content" id="modalImage" src="" alt="" onclick="event.stopPropagation()">
    </div>

    <!-- Модальное окно для видео -->
    <div id="videoModal" class="modal" onclick="closeVideoModal()">
        <button class="modal-close" onclick="closeVideoModal(); event.stopPropagation()">×</button>
        <div class="video-modal-wrapper" onclick="event.stopPropagation()">
            <div class="video-modal-content">
                <video
                    id="modalVideo"
                    class="video-js vjs-big-play-centered vjs-16-9"
                    controls
                    preload="auto">
                    <source src="" type="video/mp4">
                    <p class="vjs-no-js">
                        Для просмотра видео включите JavaScript или используйте браузер с поддержкой HTML5.
                    </p>
                </video>
            </div>
        </div>
    </div>

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
                        <li><a href="programs.php"><?= t('programs') ?></a></li>
                        <li><a href="portfolio.php"><?= t('portfolio') ?></a></li>
                        <li><a href="order.php"><?= t('order') ?></a></li>
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
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 <?= t('fireshow') ?>. <?= t('all_rights_reserved') ?>.</p>
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

        // Глобальная переменная для Video.js плеера
        let videoPlayer = null;

        // Инициализация Video.js
        document.addEventListener('DOMContentLoaded', function() {
            videoPlayer = videojs('modalVideo', {
                controls: true,
                autoplay: false,
                preload: 'auto',
                fluid: true,
                aspectRatio: '16:9',
                language: '<?= getCurrentLanguage() ?>',
                playbackRates: [0.5, 1, 1.5, 2],
                controlBar: {
                    children: [
                        'playToggle',
                        'volumePanel',
                        'currentTimeDisplay',
                        'timeDivider',
                        'durationDisplay',
                        'progressControl',
                        'playbackRateMenuButton',
                        'pictureInPictureToggle',
                        'fullscreenToggle'
                    ]
                }
            });

            // Стилизация плеера
            videoPlayer.addClass('vjs-theme-fantasy');
        });

        // Открыть модальное окно с видео
        function openVideoModal(videoSrc) {
            const modal = document.getElementById('videoModal');

            // Определяем тип видео по расширению
            const extension = videoSrc.split('.').pop().toLowerCase();
            let mimeType = 'video/mp4';

            if (extension === 'webm') mimeType = 'video/webm';
            else if (extension === 'ogg' || extension === 'ogv') mimeType = 'video/ogg';
            else if (extension === 'mkv') mimeType = 'video/x-matroska';
            else if (extension === 'avi') mimeType = 'video/x-msvideo';
            else if (extension === 'mov') mimeType = 'video/quicktime';

            // Устанавливаем источник видео
            videoPlayer.src({
                type: mimeType,
                src: videoSrc
            });

            // Показываем модальное окно
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Автовоспроизведение
            setTimeout(() => {
                videoPlayer.play().catch(err => {
                    console.log('Автовоспроизведение заблокировано браузером:', err);
                });
            }, 200);
        }

        // Закрыть модальное окно с видео
        function closeVideoModal() {
            const modal = document.getElementById('videoModal');

            // Останавливаем и сбрасываем видео
            if (videoPlayer) {
                videoPlayer.pause();
                videoPlayer.currentTime(0);
            }

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
