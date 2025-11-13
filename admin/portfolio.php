<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

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
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $event_date = $_POST['event_date'] ?? null;
        $media_type = $_POST['media_type'] ?? 'image';
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($title && $media_type) {
            try {
                $media_path = '';
                $thumbnail = '';

                // Загрузка медиа файла
                if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
                    if ($media_type === 'image') {
                        $upload = uploadImage($_FILES['media'], '../uploads/fireshow/portfolio/');
                        if ($upload['success']) {
                            $media_path = str_replace('../', '', $upload['filepath']);
                        } else {
                            $error = $upload['error'];
                        }
                    } elseif ($media_type === 'video') {
                        $upload = uploadVideo($_FILES['media'], '../uploads/fireshow/videos/');
                        if ($upload['success']) {
                            $media_path = str_replace('../', '', $upload['filepath']);
                        } else {
                            $error = $upload['error'];
                        }
                    }
                }

                // Загрузка превью для видео
                if (!$error && $media_type === 'video' && isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadImage($_FILES['thumbnail'], '../uploads/fireshow/portfolio/');
                    if ($upload['success']) {
                        $thumbnail = str_replace('../', '', $upload['filepath']);
                    }
                }

                if (!$error) {
                    $stmt = $db->prepare("INSERT INTO fireshow_portfolio (title, description, event_date, media_type, media_path, thumbnail, sort_order, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $event_date, $media_type, $media_path, $thumbnail, $sort_order, $active]);
                    $success = 'Работа успешно добавлена!';
                }
            } catch (Exception $e) {
                $error = 'Ошибка при добавлении работы: ' . $e->getMessage();
            }
        } else {
            $error = 'Заполните все обязательные поля!';
        }
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $event_date = $_POST['event_date'] ?? null;
        $media_type = $_POST['media_type'] ?? 'image';
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($title && $media_type && $id) {
            try {
                // Получаем текущие пути к файлам
                $stmt = $db->prepare("SELECT media_path, thumbnail FROM fireshow_portfolio WHERE id = ?");
                $stmt->execute([$id]);
                $current = $stmt->fetch();
                $media_path = $current['media_path'];
                $thumbnail = $current['thumbnail'];

                // Загрузка нового медиа файла
                if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
                    if ($media_type === 'image') {
                        $upload = uploadImage($_FILES['media'], '../uploads/fireshow/portfolio/');
                        if ($upload['success']) {
                            // Удаляем старый файл
                            if ($media_path && file_exists('../' . $media_path)) {
                                unlink('../' . $media_path);
                            }
                            $media_path = str_replace('../', '', $upload['filepath']);
                        }
                    } elseif ($media_type === 'video') {
                        $upload = uploadVideo($_FILES['media'], '../uploads/fireshow/videos/');
                        if ($upload['success']) {
                            // Удаляем старый файл
                            if ($media_path && file_exists('../' . $media_path)) {
                                unlink('../' . $media_path);
                            }
                            $media_path = str_replace('../', '', $upload['filepath']);
                        }
                    }
                }

                // Загрузка нового превью для видео
                if ($media_type === 'video' && isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadImage($_FILES['thumbnail'], '../uploads/fireshow/portfolio/');
                    if ($upload['success']) {
                        // Удаляем старое превью
                        if ($thumbnail && file_exists('../' . $thumbnail)) {
                            unlink('../' . $thumbnail);
                        }
                        $thumbnail = str_replace('../', '', $upload['filepath']);
                    }
                }

                // Обновляем работу
                $stmt = $db->prepare("UPDATE fireshow_portfolio SET title = ?, description = ?, event_date = ?, media_type = ?, media_path = ?, thumbnail = ?, sort_order = ?, active = ? WHERE id = ?");
                $stmt->execute([$title, $description, $event_date, $media_type, $media_path, $thumbnail, $sort_order, $active, $id]);

                $success = 'Работа обновлена!';
            } catch (Exception $e) {
                $error = 'Ошибка при обновлении работы: ' . $e->getMessage();
            }
        } else {
            $error = 'Заполните все обязательные поля!';
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            try {
                // Получаем файлы для удаления
                $stmt = $db->prepare("SELECT media_path, thumbnail FROM fireshow_portfolio WHERE id = ?");
                $stmt->execute([$id]);
                $item = $stmt->fetch();

                // Удаляем работу
                $stmt = $db->prepare("DELETE FROM fireshow_portfolio WHERE id = ?");
                $stmt->execute([$id]);

                // Удаляем файлы
                if ($item['media_path'] && file_exists('../' . $item['media_path'])) {
                    unlink('../' . $item['media_path']);
                }
                if ($item['thumbnail'] && file_exists('../' . $item['thumbnail'])) {
                    unlink('../' . $item['thumbnail']);
                }

                $success = 'Работа удалена!';
            } catch (Exception $e) {
                $error = 'Ошибка при удалении работы: ' . $e->getMessage();
            }
        }
    }
}

// Получаем все работы
$portfolio = $db->query("SELECT * FROM fireshow_portfolio ORDER BY sort_order ASC, created_at DESC")->fetchAll();

// Подсчитываем статистику
$total_items = count($portfolio);
$active_items = count(array_filter($portfolio, fn($p) => $p['active'] == 1));
$images = count(array_filter($portfolio, fn($p) => $p['media_type'] === 'image'));
$videos = count(array_filter($portfolio, fn($p) => $p['media_type'] === 'video'));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Портфолио фаершоу - Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header">
                <h1>Портфолио фаершоу</h1>
                <div class="admin-user">
                    👤 <?= escape($_SESSION['admin_username']) ?>
                    <a href="logout.php" class="btn btn-danger" style="margin-left: 1rem;">Выход</a>
                </div>
            </header>

            <div class="container">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>

                <!-- Статистика -->
                <div class="section-box">
                    <h2>Статистика</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                        <div style="padding: 1rem; background: #f5f5f5; border-radius: 8px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; color: #ff6b00;">
                                <?= $total_items ?>
                            </div>
                            <div style="color: #666; margin-top: 0.5rem;">
                                Всего работ
                            </div>
                        </div>
                        <div style="padding: 1rem; background: #f5f5f5; border-radius: 8px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; color: #4caf50;">
                                <?= $active_items ?>
                            </div>
                            <div style="color: #666; margin-top: 0.5rem;">
                                Активных
                            </div>
                        </div>
                        <div style="padding: 1rem; background: #f5f5f5; border-radius: 8px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; color: #2196f3;">
                                <?= $images ?>
                            </div>
                            <div style="color: #666; margin-top: 0.5rem;">
                                Изображений
                            </div>
                        </div>
                        <div style="padding: 1rem; background: #f5f5f5; border-radius: 8px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; color: #e91e63;">
                                <?= $videos ?>
                            </div>
                            <div style="color: #666; margin-top: 0.5rem;">
                                Видео
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Форма добавления -->
                <div class="section-box">
                    <h2>Добавить работу</h2>
                    <form method="POST" enctype="multipart/form-data" class="admin-form" id="addForm">
                        <input type="hidden" name="action" value="add">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Название *</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Тип медиа *</label>
                                <select name="media_type" class="form-control" id="add_media_type" onchange="toggleMediaType('add')" required>
                                    <option value="image">Изображение</option>
                                    <option value="video">Видео</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Дата мероприятия</label>
                                <input type="date" name="event_date" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>Порядок сортировки</label>
                                <input type="number" name="sort_order" class="form-control" value="0" min="0">
                                <small>Меньшее число = выше в списке</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Описание</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label id="add_media_label">Файл изображения *</label>
                            <input type="file" name="media" class="form-control" id="add_media" accept="image/*" required>
                            <small id="add_media_hint">Рекомендуемый размер: 1200x800px. Макс. размер: 5MB</small>
                        </div>

                        <div class="form-group" id="add_thumbnail_group" style="display: none;">
                            <label>Превью для видео</label>
                            <input type="file" name="thumbnail" class="form-control" accept="image/*">
                            <small>Изображение, которое будет показано до загрузки видео</small>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="active" checked> Активна
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success">Добавить работу</button>
                    </form>
                </div>

                <!-- Список работ -->
                <div class="section-box">
                    <h2>Список работ (<?= count($portfolio) ?>)</h2>

                    <?php if (empty($portfolio)): ?>
                        <p>Работ пока нет</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Превью</th>
                                    <th>Название</th>
                                    <th>Тип</th>
                                    <th>Дата события</th>
                                    <th>Сортировка</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($portfolio as $item): ?>
                                    <tr>
                                        <td><?= $item['id'] ?></td>
                                        <td>
                                            <?php if ($item['media_type'] === 'image' && $item['media_path']): ?>
                                                <img src="../<?= escape($item['media_path']) ?>"
                                                     alt="<?= escape($item['title']) ?>"
                                                     style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px;">
                                            <?php elseif ($item['media_type'] === 'video' && $item['thumbnail']): ?>
                                                <div style="position: relative; width: 80px; height: 60px;">
                                                    <img src="../<?= escape($item['thumbnail']) ?>"
                                                         alt="<?= escape($item['title']) ?>"
                                                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.6); border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px;">
                                                        ▶
                                                    </div>
                                                </div>
                                            <?php elseif ($item['media_type'] === 'video'): ?>
                                                <div style="width: 80px; height: 60px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    🎬
                                                </div>
                                            <?php else: ?>
                                                <div style="width: 80px; height: 60px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    📸
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= escape($item['title']) ?></strong><br>
                                            <small style="color: #666;"><?= escape(mb_substr($item['description'] ?? '', 0, 60)) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($item['media_type'] === 'image'): ?>
                                                <span class="badge" style="background: #2196f3;">📸 Изображение</span>
                                            <?php else: ?>
                                                <span class="badge" style="background: #e91e63;">🎬 Видео</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $item['event_date'] ? formatDate($item['event_date']) : '-' ?>
                                        </td>
                                        <td><?= $item['sort_order'] ?></td>
                                        <td>
                                            <?php if ($item['active']): ?>
                                                <span class="badge badge-success">Активна</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Неактивна</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button onclick="editPortfolio(<?= $item['id'] ?>)" class="btn btn-primary btn-sm">Редактировать</button>
                                            <button onclick="deletePortfolio(<?= $item['id'] ?>, '<?= escape($item['title']) ?>')" class="btn btn-danger btn-sm">Удалить</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования -->
    <div id="editModal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <h2>Редактировать работу</h2>
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-row">
                    <div class="form-group">
                        <label>Название *</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Тип медиа *</label>
                        <select name="media_type" class="form-control" id="edit_media_type" onchange="toggleMediaType('edit')" required>
                            <option value="image">Изображение</option>
                            <option value="video">Видео</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Дата мероприятия</label>
                        <input type="date" name="event_date" id="edit_event_date" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Порядок сортировки</label>
                        <input type="number" name="sort_order" id="edit_sort_order" class="form-control" min="0">
                    </div>
                </div>

                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Текущий медиа файл</label>
                    <div id="edit_current_media"></div>
                </div>

                <div class="form-group">
                    <label id="edit_media_label">Заменить файл</label>
                    <input type="file" name="media" class="form-control" id="edit_media" accept="image/*">
                    <small id="edit_media_hint">Рекомендуемый размер: 1200x800px. Макс. размер: 5MB</small>
                </div>

                <div class="form-group" id="edit_thumbnail_current" style="display: none;">
                    <label>Текущее превью</label>
                    <div id="edit_current_thumbnail"></div>
                </div>

                <div class="form-group" id="edit_thumbnail_group" style="display: none;">
                    <label>Заменить превью для видео</label>
                    <input type="file" name="thumbnail" class="form-control" accept="image/*">
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="active" id="edit_active"> Активна
                    </label>
                </div>

                <button type="submit" class="btn btn-success">Сохранить</button>
            </form>
        </div>
    </div>

    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script>
        function toggleMediaType(prefix) {
            const mediaType = document.getElementById(`${prefix}_media_type`).value;
            const mediaInput = document.getElementById(`${prefix}_media`);
            const mediaLabel = document.getElementById(`${prefix}_media_label`);
            const mediaHint = document.getElementById(`${prefix}_media_hint`);
            const thumbnailGroup = document.getElementById(`${prefix}_thumbnail_group`);

            if (mediaType === 'video') {
                mediaInput.accept = 'video/*';
                mediaLabel.textContent = 'Файл видео *';
                mediaHint.textContent = 'Допустимые форматы: MP4, WebM, OGG. Макс. размер: 50MB';
                thumbnailGroup.style.display = 'block';
            } else {
                mediaInput.accept = 'image/*';
                mediaLabel.textContent = 'Файл изображения *';
                mediaHint.textContent = 'Рекомендуемый размер: 1200x800px. Макс. размер: 5MB';
                thumbnailGroup.style.display = 'none';
            }
        }

        function editPortfolio(id) {
            fetch(`../api/portfolio.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const p = data.item;
                        document.getElementById('edit_id').value = p.id;
                        document.getElementById('edit_title').value = p.title;
                        document.getElementById('edit_description').value = p.description || '';
                        document.getElementById('edit_event_date').value = p.event_date || '';
                        document.getElementById('edit_media_type').value = p.media_type;
                        document.getElementById('edit_sort_order').value = p.sort_order;
                        document.getElementById('edit_active').checked = p.active == 1;

                        // Переключаем тип медиа
                        toggleMediaType('edit');

                        // Показываем текущий медиа файл
                        const currentMedia = document.getElementById('edit_current_media');
                        if (p.media_type === 'image' && p.media_path) {
                            currentMedia.innerHTML = `<img src="../${p.media_path}" style="max-width: 400px; border-radius: 8px;">`;
                        } else if (p.media_type === 'video' && p.media_path) {
                            currentMedia.innerHTML = `
                                <video controls style="max-width: 400px; border-radius: 8px;">
                                    <source src="../${p.media_path}" type="video/mp4">
                                </video>
                            `;
                        } else {
                            currentMedia.innerHTML = '<p style="color: #999;">Нет файла</p>';
                        }

                        // Показываем текущее превью для видео
                        const thumbnailCurrent = document.getElementById('edit_thumbnail_current');
                        const currentThumbnail = document.getElementById('edit_current_thumbnail');
                        if (p.media_type === 'video') {
                            if (p.thumbnail) {
                                thumbnailCurrent.style.display = 'block';
                                currentThumbnail.innerHTML = `<img src="../${p.thumbnail}" style="max-width: 200px; border-radius: 8px;">`;
                            } else {
                                thumbnailCurrent.style.display = 'block';
                                currentThumbnail.innerHTML = '<p style="color: #999;">Нет превью</p>';
                            }
                        } else {
                            thumbnailCurrent.style.display = 'none';
                        }

                        document.getElementById('editModal').classList.add('active');
                    } else {
                        alert('Ошибка: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Ошибка при загрузке данных работы');
                });
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function deletePortfolio(id, title) {
            if (confirm(`Удалить работу "${title}"?\n\nВсе файлы также будут удалены.`)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Закрытие модального окна по клику вне его
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Автоматически скрывать алерты через 5 секунд
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>
