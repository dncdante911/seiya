<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

$db = getDB();

// Получаем новости для фаершоу
$stmt = $db->query("
    SELECT * FROM news
    WHERE (section = 'fireshow' OR section = 'both')
      AND is_active = 1
      AND published_at <= NOW()
    ORDER BY is_pinned DESC, published_at DESC
");
$news_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Новини та оголошення - Вогняне шоу</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/fireshow.css">
</head>
<body>
    <!-- Навигация -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-logo">🔥 Fireshow</a>
            <div class="navbar-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="navbar-menu" id="navbarMenu">
                <li><a href="index.php">Головна</a></li>
                <li><a href="programs.php">Програми</a></li>
                <li><a href="portfolio.php">Портфоліо</a></li>
                <li><a href="news.php" class="active">Новини</a></li>
                <li><a href="about.php">Про нас</a></li>
                <li><a href="../contacts.php">Контакти</a></li>
                <li><a href="../">← Назад</a></li>
            </ul>
        </div>
    </nav>

    <div class="container" style="padding: 4rem 2rem;">
        <h1 style="text-align: center; margin-bottom: 3rem; font-size: 2.5rem; color: #ff4500;">🔥 Новини та оголошення</h1>

        <?php if (empty($news_list)): ?>
            <div style="text-align: center; padding: 4rem 2rem; color: #d0d0e0;">
                <p style="font-size: 1.2rem;">Поки що немає новин</p>
            </div>
        <?php else: ?>
            <div style="display: grid; gap: 2rem; max-width: 900px; margin: 0 auto;">
                <?php foreach ($news_list as $item): ?>
                    <article style="background: var(--dark-card); border-radius: 15px; overflow: hidden; box-shadow: 0 4px 20px rgba(255,69,0,0.2); transition: transform 0.3s ease; border: 1px solid rgba(255,69,0,0.3);">
                        <?php if ($item['image_path']): ?>
                            <img src="../<?= escape($item['image_path']) ?>" alt="<?= escape($item['title']) ?>" style="width: 100%; height: 300px; object-fit: cover;">
                        <?php endif; ?>

                        <div style="padding: 2rem;">
                            <div style="display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                                <?php if ($item['is_pinned']): ?>
                                    <span style="padding: 0.5rem 1rem; background: var(--fire-primary); color: white; border-radius: 20px; font-size: 0.9rem;">📌 Закріплено</span>
                                <?php endif; ?>

                                <?php if ($item['type'] === 'announcement'): ?>
                                    <span style="padding: 0.5rem 1rem; background: var(--fire-secondary); color: white; border-radius: 20px; font-size: 0.9rem;">📢 Оголошення</span>
                                <?php else: ?>
                                    <span style="padding: 0.5rem 1rem; background: var(--fire-orange); color: white; border-radius: 20px; font-size: 0.9rem;">📰 Новина</span>
                                <?php endif; ?>

                                <span style="padding: 0.5rem 1rem; background: rgba(255,255,255,0.1); color: #d0d0e0; border-radius: 20px; font-size: 0.9rem;">
                                    📅 <?= formatDate($item['published_at'], 'd.m.Y') ?>
                                </span>
                            </div>

                            <h2 style="font-size: 1.8rem; margin-bottom: 1rem; color: var(--fire-primary);">
                                <?= escape($item['title']) ?>
                            </h2>

                            <div style="color: #d0d0e0; line-height: 1.8; white-space: pre-line;">
                                <?= nl2br(escape($item['content'])) ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Seiya Fireshow. Всі права захищені.</p>
        </div>
    </footer>

    <script src="../assets/js/fireshow.js"></script>
    <script>
        function toggleMenu() {
            const menu = document.getElementById('navbarMenu');
            const toggle = document.querySelector('.navbar-toggle');
            menu.classList.toggle('active');
            toggle.classList.toggle('active');
        }

        document.querySelectorAll('.navbar-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('navbarMenu').classList.remove('active');
                document.querySelector('.navbar-toggle').classList.remove('active');
            });
        });
    </script>
</body>
</html>
