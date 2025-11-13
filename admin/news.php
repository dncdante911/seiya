<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/security.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$success = '';
$error = '';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $section = $_POST['section'] ?? 'both';
        $type = $_POST['type'] ?? 'news';
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $published_at = $_POST['published_at'] ?? date('Y-m-d H:i:s');
        $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($title && $content) {
            try {
                // Загрузка изображения
                $image_path = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadImage($_FILES['image'], '../uploads/news/');
                    if ($upload['success']) {
                        $image_path = str_replace('../', '', $upload['filepath']);
                    } else {
                        $error = $upload['error'];
                    }
                }

                if (!$error) {
                    $stmt = $db->prepare("
                        INSERT INTO news (section, type, title, content, image_path, is_pinned, is_active, published_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$section, $type, $title, $content, $image_path, $is_pinned, $is_active, $published_at]);
                    $success = 'Новость успешно добавлена!';
                }
            } catch (Exception $e) {
                $error = 'Ошибка: ' . $e->getMessage();
            }
        } else {
            $error = 'Заполните все обязательные поля!';
        }
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $section = $_POST['section'] ?? 'both';
        $type = $_POST['type'] ?? 'news';
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $published_at = $_POST['published_at'] ?? date('Y-m-d H:i:s');
        $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($title && $content && $id) {
            try {
                // Получаем текущее изображение
                $stmt = $db->prepare("SELECT image_path FROM news WHERE id = ?");
                $stmt->execute([$id]);
                $current = $stmt->fetch();
                $image_path = $current['image_path'];

                // Загрузка нового изображения
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadImage($_FILES['image'], '../uploads/news/');
                    if ($upload['success']) {
                        // Удаляем старое изображение
                        if ($image_path && file_exists("../$image_path")) {
                            unlink("../$image_path");
                        }
                        $image_path = str_replace('../', '', $upload['filepath']);
                    }
                }

                $stmt = $db->prepare("
                    UPDATE news SET section = ?, type = ?, title = ?, content = ?, image_path = ?,
                           is_pinned = ?, is_active = ?, published_at = ?
                    WHERE id = ?
                ");
                $stmt->execute([$section, $type, $title, $content, $image_path, $is_pinned, $is_active, $published_at, $id]);
                $success = 'Новость успешно обновлена!';
            } catch (Exception $e) {
                $error = 'Ошибка: ' . $e->getMessage();
            }
        } else {
            $error = 'Заполните все обязательные поля!';
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            try {
                // Получаем путь к изображению
                $stmt = $db->prepare("SELECT image_path FROM news WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();

                // Удаляем файл изображения
                if ($item['image_path'] && file_exists("../" . $item['image_path'])) {
                    unlink("../" . $item['image_path']);
                }

                // Удаляем запись
                $stmt = $db->prepare("DELETE FROM news WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Новость успешно удалена!';
            } catch (Exception $e) {
                $error = 'Ошибка: ' . $e->getMessage();
            }
        }
    }
}

// Получаем список новостей
$filter_section = $_GET['filter_section'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';

$sql = "SELECT * FROM news WHERE 1=1";
if ($filter_section) {
    $sql .= " AND section = " . $db->quote($filter_section);
}
if ($filter_type) {
    $sql .= " AND type = " . $db->quote($filter_type);
}
$sql .= " ORDER BY is_pinned DESC, published_at DESC";

$news_list = $db->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление новостями и объявлениями - Админка</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header">
                <h1>📰 Новости и объявления</h1>
                <div class="admin-user">
                    <button class="btn btn-primary" onclick="showAddModal()">+ Добавить</button>
                </div>
            </header>

            <main style="padding: 2rem;">

            <?php if ($success): ?>
                <div class="alert alert-success"><?= escape($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= escape($error) ?></div>
            <?php endif; ?>

            <!-- Фильтры -->
            <div class="filter-bar">
                <form method="GET" class="filter-form">
                    <select name="filter_section" onchange="this.form.submit()">
                        <option value="">Все разделы</option>
                        <option value="confectionery" <?= $filter_section === 'confectionery' ? 'selected' : '' ?>>Кондитерка</option>
                        <option value="fireshow" <?= $filter_section === 'fireshow' ? 'selected' : '' ?>>Фаершоу</option>
                        <option value="both" <?= $filter_section === 'both' ? 'selected' : '' ?>>Оба раздела</option>
                    </select>

                    <select name="filter_type" onchange="this.form.submit()">
                        <option value="">Все типы</option>
                        <option value="news" <?= $filter_type === 'news' ? 'selected' : '' ?>>Новости</option>
                        <option value="announcement" <?= $filter_type === 'announcement' ? 'selected' : '' ?>>Объявления</option>
                    </select>

                    <?php if ($filter_section || $filter_type): ?>
                        <a href="news.php" class="btn btn-secondary">Сбросить</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Список новостей -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Изображение</th>
                            <th>Заголовок</th>
                            <th>Раздел</th>
                            <th>Тип</th>
                            <th>Дата публикации</th>
                            <th>Закреплено</th>
                            <th>Статус</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($news_list)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Нет новостей</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($news_list as $item): ?>
                                <tr>
                                    <td><?= $item['id'] ?></td>
                                    <td>
                                        <?php if ($item['image_path']): ?>
                                            <img src="../<?= escape($item['image_path']) ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                        <?php else: ?>
                                            <span style="color: #999;">Нет изображения</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= escape($item['title']) ?></strong></td>
                                    <td>
                                        <?php
                                        $sections = [
                                            'confectionery' => '🍰 Кондитерка',
                                            'fireshow' => '🔥 Фаершоу',
                                            'both' => '🎭 Оба'
                                        ];
                                        echo $sections[$item['section']] ?? $item['section'];
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $types = [
                                            'news' => '📰 Новость',
                                            'announcement' => '📢 Объявление'
                                        ];
                                        echo $types[$item['type']] ?? $item['type'];
                                        ?>
                                    </td>
                                    <td><?= formatDateTime($item['published_at']) ?></td>
                                    <td>
                                        <?php if ($item['is_pinned']): ?>
                                            <span class="badge badge-warning">📌 Да</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Нет</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($item['is_active']): ?>
                                            <span class="badge badge-success">Активна</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Скрыта</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-cell">
                                        <button class="btn btn-sm btn-primary" onclick='editNews(<?= json_encode($item, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Редактировать</button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteNews(<?= $item['id'] ?>)">Удалить</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
        </div> <!-- /.admin-content -->
    </div> <!-- /.admin-wrapper -->

    <!-- Модальное окно добавления -->
    <div id="addModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2>Добавить новость/объявление</h2>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Раздел *</label>
                        <select name="section" class="form-control" required>
                            <option value="both">Оба раздела</option>
                            <option value="confectionery">Кондитерка</option>
                            <option value="fireshow">Фаершоу</option>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Тип *</label>
                        <select name="type" class="form-control" required>
                            <option value="news">Новость</option>
                            <option value="announcement">Объявление</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Заголовок *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Содержание *</label>
                    <textarea name="content" class="form-control" rows="8" required></textarea>
                </div>

                <div class="form-group">
                    <label>Изображение</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <div class="form-group">
                    <label>Дата публикации *</label>
                    <input type="datetime-local" name="published_at" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_pinned" value="1">
                        Закрепить (показывать первой)
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Активна (видна на сайте)
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Добавить</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addModal')">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно редактирования -->
    <div id="editModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h2>Редактировать новость/объявление</h2>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <label>Раздел *</label>
                        <select name="section" id="edit_section" class="form-control" required>
                            <option value="both">Оба раздела</option>
                            <option value="confectionery">Кондитерка</option>
                            <option value="fireshow">Фаершоу</option>
                        </select>
                    </div>

                    <div class="form-group" style="flex: 1;">
                        <label>Тип *</label>
                        <select name="type" id="edit_type" class="form-control" required>
                            <option value="news">Новость</option>
                            <option value="announcement">Объявление</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Заголовок *</label>
                    <input type="text" name="title" id="edit_title" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Содержание *</label>
                    <textarea name="content" id="edit_content" class="form-control" rows="8" required></textarea>
                </div>

                <div class="form-group">
                    <label>Текущее изображение</label>
                    <div id="edit_current_image"></div>
                </div>

                <div class="form-group">
                    <label>Новое изображение (оставьте пустым, чтобы не менять)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <div class="form-group">
                    <label>Дата публикации *</label>
                    <input type="datetime-local" name="published_at" id="edit_published_at" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_pinned" id="edit_is_pinned" value="1">
                        Закрепить (показывать первой)
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1">
                        Активна (видна на сайте)
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Отмена</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function editNews(item) {
            document.getElementById('edit_id').value = item.id;
            document.getElementById('edit_section').value = item.section;
            document.getElementById('edit_type').value = item.type;
            document.getElementById('edit_title').value = item.title;
            document.getElementById('edit_content').value = item.content;
            document.getElementById('edit_is_pinned').checked = item.is_pinned == 1;
            document.getElementById('edit_is_active').checked = item.is_active == 1;

            // Форматируем дату для datetime-local
            const date = new Date(item.published_at);
            const formattedDate = date.toISOString().slice(0, 16);
            document.getElementById('edit_published_at').value = formattedDate;

            // Показываем текущее изображение
            const imageDiv = document.getElementById('edit_current_image');
            if (item.image_path) {
                imageDiv.innerHTML = `<img src="../${item.image_path}" style="max-width: 200px; border-radius: 8px;">`;
            } else {
                imageDiv.innerHTML = '<p style="color: #999;">Нет изображения</p>';
            }

            document.getElementById('editModal').classList.add('active');
        }

        function deleteNews(id) {
            if (confirm('Вы уверены, что хотите удалить эту новость?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Закрытие модальных окон по клику вне их
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
