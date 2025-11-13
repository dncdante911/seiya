<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$db = getDB();

// Получаем избранные программы
$stmt = $db->query("SELECT * FROM fireshow_programs WHERE active = 1 AND featured = 1 LIMIT 3");
$featured_programs = $stmt->fetchAll();

// Получаем портфолио
$stmt = $db->query("SELECT * FROM fireshow_portfolio WHERE active = 1 ORDER BY sort_order LIMIT 6");
$portfolio = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Фаершоу - Огненные шоу</title>
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

    <section class="hero-banner fireshow-banner">
        <div class="hero-content">
            <h1>Огненные шоу для вашего праздника</h1>
            <p>Профессиональные выступления, которые запомнятся надолго</p>
            <div class="hero-buttons">
                <a href="portfolio.php" class="btn btn-primary">Смотреть портфолио</a>
                <a href="order.php" class="btn btn-secondary">Заказать шоу</a>
            </div>
        </div>
    </section>

    <?php if (!empty($portfolio)): ?>
    <section class="container">
        <h2 class="section-title">Наши выступления</h2>
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
            <a href="portfolio.php" class="btn btn-primary">Смотреть все</a>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($featured_programs)): ?>
    <section class="container">
        <h2 class="section-title">Популярные программы</h2>
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
                                <span>⏱️ <?= $program['duration'] ?> мин</span>
                            <?php endif; ?>
                            <span>👥 <?= $program['min_artists'] ?>-<?= $program['max_artists'] ?> артистов</span>
                        </div>
                        <div class="program-footer">
                            <span class="program-price">от <?= formatPrice($program['price_per_artist']) ?></span>
                            <a href="programs.php?slug=<?= $program['slug'] ?>" class="btn btn-primary">Подробнее</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="container">
        <h2 class="section-title">Почему выбирают нас</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🔥</div>
                <h3>Профессионализм</h3>
                <p>Опытные артисты с многолетним стажем</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🎭</div>
                <h3>Индивидуальный подход</h3>
                <p>Разработаем программу под ваше мероприятие</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">✨</div>
                <h3>Зрелищность</h3>
                <p>Яркие номера, которые запомнятся гостям</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <h3>Безопасность</h3>
                <p>Соблюдаем все меры предосторожности</p>
            </div>
        </div>
    </section>

    <section class="cta-section fireshow-cta">
        <div class="container">
            <h2>Готовы заказать огненное шоу?</h2>
            <p>Свяжитесь с нами для обсуждения деталей вашего мероприятия</p>
            <div class="cta-buttons">
                <a href="order.php" class="btn btn-primary">Заказать шоу</a>
                <a href="programs.php" class="btn btn-secondary">Смотреть программы</a>
            </div>
        </div>
    </section>

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
                        <li><a href="programs.php">Программы</a></li>
                        <li><a href="portfolio.php">Портфолио</a></li>
                        <li><a href="order.php">Заказать</a></li>
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
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Фаершоу. Все права защищены.</p>
            </div>
        </div>
    </footer>
</body>
</html>
