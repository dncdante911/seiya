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
            <div class="news-card" onclick="openNewsModal(<?= $item['id'] ?>)" data-news-id="<?= $item['id'] ?>"
                 data-news-title="<?= escape($item['title']) ?>"
                 data-news-content="<?= escape($item['content']) ?>"
                 data-news-image="<?= escape($item['image_path']) ?>"
                 data-news-date="<?= formatDate($item['published_at'], 'd.m.Y') ?>"
                 data-news-pinned="<?= $item['is_pinned'] ?>"
                 data-news-type="<?= escape($item['type']) ?>">
                <?php if ($item['image_path']): ?>
                    <div class="news-card-image" style="background-image: url('/<?= escape($item['image_path']) ?>');"></div>
                <?php else: ?>
                    <div class="news-card-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
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
    opacity: 1;
    pointer-events: auto;
}

.news-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
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

/* Убраны стили темной темы для фаершоу - используются обычные белые карточки */

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

/* Модальное окно */
.news-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    z-index: 10000;
    overflow-y: auto;
    padding: 2rem;
}

.news-modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.news-modal-content {
    background: white;
    border-radius: 20px;
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.news-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.1);
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    z-index: 10;
}

.news-modal-close:hover {
    background: rgba(0, 0, 0, 0.2);
    transform: rotate(90deg);
}

.news-modal-image {
    width: 100%;
    height: 300px;
    background-size: cover;
    background-position: center;
    border-radius: 20px 20px 0 0;
}

.news-modal-body {
    padding: 2rem;
}

.news-modal-meta {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.news-modal-title {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: #2d3748;
    line-height: 1.3;
}

.news-modal-content-text {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #4a5568;
    white-space: pre-wrap;
}

@media (max-width: 768px) {
    .news-modal {
        padding: 1rem;
    }

    .news-modal-content {
        max-height: 95vh;
    }

    .news-modal-image {
        height: 200px;
    }

    .news-modal-body {
        padding: 1.5rem;
    }

    .news-modal-title {
        font-size: 1.5rem;
    }

    .news-modal-content-text {
        font-size: 1rem;
    }
}
</style>

<!-- Модальное окно для новостей -->
<div id="newsModal" class="news-modal" onclick="closeNewsModal(event)">
    <div class="news-modal-content" onclick="event.stopPropagation()">
        <button class="news-modal-close" onclick="closeNewsModal()">×</button>
        <div id="newsModalImage" class="news-modal-image"></div>
        <div class="news-modal-body">
            <div id="newsModalMeta" class="news-modal-meta"></div>
            <h2 id="newsModalTitle" class="news-modal-title"></h2>
            <div id="newsModalContent" class="news-modal-content-text"></div>
        </div>
    </div>
</div>

<script>
function openNewsModal(newsId) {
    const card = document.querySelector(`[data-news-id="${newsId}"]`);
    if (!card) return;

    const modal = document.getElementById('newsModal');
    const title = card.getAttribute('data-news-title');
    const content = card.getAttribute('data-news-content');
    const image = card.getAttribute('data-news-image');
    const date = card.getAttribute('data-news-date');
    const pinned = card.getAttribute('data-news-pinned');
    const type = card.getAttribute('data-news-type');

    // Заполняем модальное окно
    document.getElementById('newsModalTitle').textContent = title;
    document.getElementById('newsModalContent').textContent = content;

    // Изображение
    const imageEl = document.getElementById('newsModalImage');
    if (image && image.trim() !== '') {
        imageEl.style.backgroundImage = `url('/${image}')`;
        imageEl.style.display = 'block';
    } else {
        imageEl.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
        imageEl.style.display = 'block';
    }

    // Метаданные
    let metaHTML = '';
    if (pinned === '1') {
        metaHTML += '<span class="news-badge pinned">📌 <?= t('pinned') ?></span>';
    }
    if (type === 'announcement') {
        metaHTML += '<span class="news-badge announcement">📢</span>';
    }
    metaHTML += `<span class="news-date">${date}</span>`;
    document.getElementById('newsModalMeta').innerHTML = metaHTML;

    // Показываем модальное окно
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeNewsModal(event) {
    if (event && event.target.classList.contains('news-modal-content')) {
        return;
    }
    const modal = document.getElementById('newsModal');
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// Закрытие по Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeNewsModal();
    }
});
</script>
