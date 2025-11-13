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
    <title>Программы - Фаершоу</title>
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
        <h1 class="section-title">Наши программы</h1>
        <p style="text-align: center; margin-bottom: 3rem; color: #666;">
            Выберите подходящую программу для вашего мероприятия
        </p>

        <?php if (empty($programs)): ?>
            <div class="alert alert-info">
                <p>Программы пока не добавлены. Свяжитесь с нами для уточнения деталей!</p>
            </div>
        <?php else: ?>
            <div class="programs-grid">
                <?php foreach ($programs as $program): ?>
                    <div class="program-card">
                        <?php if ($program['image']): ?>
                            <img src="../<?= escape($program['image']) ?>"
                                 alt="<?= escape($program['name']) ?>"
                                 class="program-image">
                        <?php else: ?>
                            <div class="program-image no-image">🔥</div>
                        <?php endif; ?>

                        <div class="program-info">
                            <h3><?= escape($program['name']) ?></h3>
                            <p><?= nl2br(escape($program['description'])) ?></p>

                            <div class="program-meta">
                                <?php if ($program['duration']): ?>
                                    <span>⏱️ <?= $program['duration'] ?> минут</span>
                                <?php endif; ?>
                                <span>👥 <?= $program['min_artists'] ?>-<?= $program['max_artists'] ?> артистов</span>
                            </div>

                            <?php if ($program['video']): ?>
                                <video controls style="width: 100%; border-radius: 10px; margin: 1rem 0;">
                                    <source src="../<?= escape($program['video']) ?>" type="video/mp4">
                                </video>
                            <?php endif; ?>

                            <div class="program-footer">
                                <div>
                                    <div class="program-price">от <?= formatPrice($program['price_per_artist']) ?></div>
                                    <small style="color: #666;">за артиста</small>
                                </div>
                                <a href="order.php" class="btn btn-primary">Заказать</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <section class="cta-section fireshow-cta">
        <div class="container">
            <h2>Не нашли подходящую программу?</h2>
            <p>Мы можем разработать индивидуальное шоу специально для вас!</p>
            <div class="cta-buttons">
                <a href="order.php" class="btn btn-primary">Оставить заявку</a>
            </div>
        </div>
    </section>

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
    </script>
</body>
</html>
