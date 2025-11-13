<?php
require_once __DIR__ . '/config/language.php';
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('main_title') ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <div class="landing-page">
        <header class="landing-header fade-in">
            <!-- Переключатель языков -->
            <?php include __DIR__ . '/includes/language-switcher.php'; ?>

            <h1>🎂🔥 Seiya</h1>
            <p><?= t('main_subtitle') ?></p>
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
                    <p><?= t('confectionery_description') ?></p>
                    <div class="section-button">
                        <span><?= t('more') ?> →</span>
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
                    <p><?= t('fireshow_description') ?></p>
                    <div class="section-button">
                        <span><?= t('more') ?> →</span>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Check if device is mobile
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;

        // Добавляем эффект параллакса при движении мыши (только на десктопе)
        if (!isMobile) {
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
        }

        // Анимация декоративных элементов
        document.querySelectorAll('.decorative-element').forEach(el => {
            const duration = 3 + Math.random() * 2;
            el.style.animation = `float ${duration}s ease-in-out infinite`;
        });

        // Improve touch interactions on mobile
        if (isMobile) {
            document.querySelectorAll('.section-half').forEach(section => {
                section.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                }, {passive: true});

                section.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                }, {passive: true});
            });

            // Ensure language switcher is touchable
            document.querySelectorAll('.lang-btn').forEach(btn => {
                btn.addEventListener('touchstart', function(e) {
                    e.stopPropagation();
                }, {passive: true});
            });
        }
    </script>
</body>
</html>
