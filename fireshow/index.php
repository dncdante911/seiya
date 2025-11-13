<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/language.php';

$db = getDB();

// Получаем избранные программы
$stmt = $db->query("SELECT * FROM fireshow_programs WHERE active = 1 AND featured = 1 LIMIT 3");
$featured_programs = $stmt->fetchAll();

// Получаем портфолио
$stmt = $db->query("SELECT * FROM fireshow_portfolio WHERE active = 1 ORDER BY sort_order LIMIT 6");
$portfolio = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLanguage() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('fireshow_page_title') ?></title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/fireshow.css">
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

    <section class="hero-banner fireshow-banner">
        <div class="hero-content">
            <h1><?= t('fireshow_hero_title') ?></h1>
            <p><?= t('fireshow_hero_description') ?></p>
            <div class="hero-buttons">
                <a href="portfolio.php" class="btn btn-primary"><?= t('view_portfolio') ?></a>
                <a href="order.php" class="btn btn-secondary"><?= t('order_show') ?></a>
            </div>
        </div>
    </section>

    <?php if (!empty($portfolio)): ?>
    <section class="container">
        <h2 class="section-title"><?= t('our_performances') ?></h2>
        <div class="portfolio-grid">
            <?php foreach ($portfolio as $item): ?>
                <div class="portfolio-card">
                    <?php if ($item['media_type'] === 'image'): ?>
                        <img src="../<?= escape($item['thumbnail'] ?: $item['media_path']) ?>" alt="<?= escape($item['title']) ?>" class="portfolio-image">
                    <?php else: ?>
                        <video class="portfolio-image" controls>
                            <source src="../<?= escape($item['media_path']) ?>" type="video/mp4">
                        </video>
                    <?php endif; ?>
                    <div class="portfolio-info">
                        <h3><?= escape($item['title']) ?></h3>
                        <?php if ($item['event_date']): ?>
                            <p class="portfolio-date">📅 <?= formatDate($item['event_date']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="portfolio.php" class="btn btn-primary"><?= t('view_all') ?></a>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($featured_programs)): ?>
    <section class="container">
        <h2 class="section-title"><?= t('popular_programs') ?></h2>
        <div class="programs-grid">
            <?php foreach ($featured_programs as $program): ?>
                <div class="program-card">
                    <?php if ($program['image']): ?>
                        <img src="../<?= escape($program['image']) ?>" alt="<?= escape($program['name']) ?>" class="program-image">
                    <?php else: ?>
                        <div class="program-image no-image">🔥</div>
                    <?php endif; ?>
                    <div class="program-info">
                        <h3><?= escape($program['name']) ?></h3>
                        <p><?= escape(mb_substr($program['description'], 0, 150)) ?>...</p>
                        <div class="program-meta">
                            <?php if ($program['duration']): ?>
                                <span>⏱️ <?= $program['duration'] ?> <?= t('min') ?></span>
                            <?php endif; ?>
                            <span>👥 <?= $program['min_artists'] ?>-<?= $program['max_artists'] ?> <?= t('artists') ?></span>
                        </div>
                        <div class="program-footer">
                            <span class="program-price"><?= t('from') ?> <?= formatPrice($program['price_per_artist']) ?></span>
                            <a href="programs.php?slug=<?= $program['slug'] ?>" class="btn btn-primary"><?= t('more') ?></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Виджет новостей -->
    <div class="container">
        <?php
        $section = 'fireshow';
        $limit = 3;
        include __DIR__ . '/../includes/news-widget.php';
        ?>
    </div>

    <section class="container">
        <h2 class="section-title"><?= t('why_choose_us') ?></h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🔥</div>
                <h3><?= t('professionalism') ?></h3>
                <p><?= t('professionalism_desc') ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎭</div>
                <h3><?= t('individual_approach') ?></h3>
                <p><?= t('individual_approach_desc') ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">✨</div>
                <h3><?= t('spectacularity') ?></h3>
                <p><?= t('spectacularity_desc') ?></p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <h3><?= t('safety') ?></h3>
                <p><?= t('safety_desc') ?></p>
            </div>
        </div>
    </section>

    <section class="cta-section fireshow-cta">
        <div class="container">
            <h2><?= t('ready_to_book') ?></h2>
            <p><?= t('contact_us_for_details') ?></p>
            <div class="cta-buttons">
                <a href="order.php" class="btn btn-primary"><?= t('order_show') ?></a>
                <a href="programs.php" class="btn btn-secondary"><?= t('view_programs') ?></a>
            </div>
        </div>
    </section>

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
    </script>
</body>
</html>
