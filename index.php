<?php
require_once __DIR__ . '/config/lang.php';
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('site_name') ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <div class="landing-page">
        <header class="landing-header fade-in">
            <!-- Переключатель языков -->
            <div class="language-switcher" style="position: absolute; top: 20px; right: 20px; z-index: 100;">
                <a href="?lang=uk" class="lang-btn <?= getCurrentLang() === 'uk' ? 'active' : '' ?>">🇺🇦 UA</a>
                <a href="?lang=ru" class="lang-btn <?= getCurrentLang() === 'ru' ? 'active' : '' ?>">🇷🇺 RU</a>
            </div>

            <h1>🎂🔥 <?= t('site_name') ?></h1>
            <p><?= t('choose_section') ?></p>
        </header>

        <div class="sections-wrapper">
            <!-- Раздел Кондитерка -->
            <a href="confectionery/" class="section-half confectionery-section">
                <div class="section-content fade-in">
                    <!-- Декоративные элементы -->
                    <div class="decorative-element" style="top: 10%; left: 10%; font-size: 3rem; position: absolute; opacity: 0.15;">🧁</div>
                    <div class="decorative-element" style="bottom: 10%; right: 10%; font-size: 2.5rem; position: absolute; opacity: 0.15;">🍰</div>

                    <div class="section-icon">🎂</div>
                    <h2><?= t('confectionery') ?></h2>
                    <p><?= t('confectionery_desc') ?></p>
                    <div class="section-button">
                        <span><?= t('confectionery_btn') ?></span>
                    </div>
                </div>
            </a>

            <!-- Раздел Фаершоу -->
            <a href="fireshow/" class="section-half fireshow-section">
                <div class="section-content fade-in">
                    <!-- Декоративные элементы -->
                    <div class="decorative-element" style="top: 15%; right: 15%; font-size: 3rem; position: absolute; opacity: 0.15;">✨</div>
                    <div class="decorative-element" style="bottom: 15%; left: 15%; font-size: 2.5rem; position: absolute; opacity: 0.15;">🎭</div>

                    <div class="section-icon">🔥</div>
                    <h2><?= t('fireshow') ?></h2>
                    <p><?= t('fireshow_desc') ?></p>
                    <div class="section-button">
                        <span><?= t('fireshow_btn') ?></span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Добавляем эффект параллакса при движении мыши
        document.querySelectorAll('.section-half').forEach(section => {
            section.addEventListener('mousemove', (e) => {
                const rect = section.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                const centerX = rect.width / 2;
                const centerY = rect.height / 2;

                const percentX = (x - centerX) / centerX;
                const percentY = (y - centerY) / centerY;

                const icon = section.querySelector('.section-icon');
                if (icon) {
                    icon.style.transform = `translate(${percentX * 20}px, ${percentY * 20}px)`;
                }

                // Параллакс для декоративных элементов
                const decorElements = section.querySelectorAll('.decorative-element');
                decorElements.forEach((el, index) => {
                    const speed = (index + 1) * 10;
                    el.style.transform = `translate(${percentX * speed}px, ${percentY * speed}px)`;
                });
            });

            section.addEventListener('mouseleave', () => {
                const icon = section.querySelector('.section-icon');
                if (icon) {
                    icon.style.transform = 'translate(0, 0)';
                }

                const decorElements = section.querySelectorAll('.decorative-element');
                decorElements.forEach(el => {
                    el.style.transform = 'translate(0, 0)';
                });
            });
        });

        // Анимация декоративных элементов
        document.querySelectorAll('.decorative-element').forEach(el => {
            const duration = 3 + Math.random() * 2;
            el.style.animation = `float ${duration}s ease-in-out infinite`;
        });
    </script>
</body>
</html>
