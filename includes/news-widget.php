<?php
/**
 * Виджет последних новостей
 * @param string $section - раздел (confectionery, fireshow, both)
 * @param int $limit - количество новостей (по умолчанию 3)
 */

$section = $section ?? 'both';
$limit = $limit ?? 3;

// Получаем новости для указанного раздела
$stmt = $db->prepare("
    SELECT * FROM news
    WHERE (section = ? OR section = 'both')
      AND is_active = 1
      AND published_at <= NOW()
    ORDER BY is_pinned DESC, published_at DESC
    LIMIT ?
");
$stmt->execute([$section, $limit]);
$news_items = $stmt->fetchAll();

// Если нет новостей, не показываем виджет
if (empty($news_items)) {
    return;
}
?>

<section class="news-widget">
    <div class="news-widget-header">
        <h2>📰 <?= t('latest_news') ?></h2>
        <?php
        $news_url = $section === 'confectionery' ? 'news.php' : ($section === 'fireshow' ? 'news.php' : '/news.php');
        ?>
        <a href="<?= $news_url ?>" class="view-all-link"><?= t('all_news') ?> →</a>
    </div>

    <div class="news-widget-grid">
        <?php foreach ($news_items as $item): ?>
            <div class="news-card">
                <?php if ($item['image_path']): ?>
                    <div class="news-card-image" style="background-image: url('../<?= escape($item['image_path']) ?>');"></div>
                <?php endif; ?>

                <div class="news-card-content">
                    <div class="news-card-meta">
                        <?php if ($item['is_pinned']): ?>
                            <span class="news-badge pinned">📌 <?= t('pinned') ?></span>
                        <?php endif; ?>

                        <?php if ($item['type'] === 'announcement'): ?>
                            <span class="news-badge announcement">📢</span>
                        <?php endif; ?>

                        <span class="news-date">
                            <?= formatDate($item['published_at'], 'd.m.Y') ?>
                        </span>
                    </div>

                    <h3 class="news-card-title"><?= escape($item['title']) ?></h3>

                    <p class="news-card-excerpt">
                        <?= escape(mb_substr($item['content'], 0, 120)) ?><?= mb_strlen($item['content']) > 120 ? '...' : '' ?>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<style>
.news-widget {
    padding: 3rem 0;
    background: rgba(255, 255, 255, 0.03);
    border-radius: 20px;
    margin: 2rem 0;
}

.news-widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 0 2rem;
}

.news-widget-header h2 {
    font-size: 2rem;
    margin: 0;
}

.view-all-link {
    color: var(--primary-color, #667eea);
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.1);
}

.view-all-link:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateX(5px);
}

.news-widget-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    padding: 0 2rem;
}

.news-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    cursor: pointer;
}

.news-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

.news-card-image {
    width: 100%;
    height: 200px;
    background-size: cover;
    background-position: center;
    background-color: #f0f0f0;
}

.news-card-content {
    padding: 1.5rem;
}

.news-card-meta {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.news-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 500;
}

.news-badge.pinned {
    background: #ff6b9d;
    color: white;
}

.news-badge.announcement {
    background: #667eea;
    color: white;
}

.news-date {
    color: #666;
    font-size: 0.85rem;
}

.news-card-title {
    font-size: 1.2rem;
    margin-bottom: 0.75rem;
    color: #2d3748;
    line-height: 1.4;
}

.news-card-excerpt {
    color: #4a5568;
    line-height: 1.6;
    font-size: 0.95rem;
}

/* Темная тема для фаершоу */
.fireshow-section .news-card {
    background: var(--dark-card, #2a2a3e);
}

.fireshow-section .news-card-title {
    color: #ff4500;
}

.fireshow-section .news-card-excerpt {
    color: #d0d0e0;
}

.fireshow-section .news-date {
    color: #999;
}

@media (max-width: 768px) {
    .news-widget-grid {
        grid-template-columns: 1fr;
        padding: 0 1rem;
    }

    .news-widget-header {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
        padding: 0 1rem;
    }

    .news-card-image {
        height: 150px;
    }
}
</style>
