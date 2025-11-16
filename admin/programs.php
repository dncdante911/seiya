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
        $name = trim($_POST['name'] ?? '');
        $slug = generateSlug($name);
        $description = trim($_POST['description'] ?? '');
        $duration = (int)($_POST['duration'] ?? 0);
        $min_artists = (int)($_POST['min_artists'] ?? 1);
        $max_artists = (int)($_POST['max_artists'] ?? 1);
        $price_per_artist = floatval($_POST['price_per_artist'] ?? 0);
        $featured = isset($_POST['featured']) ? 1 : 0;
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name && $duration > 0 && $price_per_artist > 0) {
            try {
                // Загрузка изображения
                $image_path = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadImage($_FILES['image'], '../uploads/fireshow/programs/');
                    if ($upload['success']) {
                        $image_path = str_replace('../', '', $upload['filepath']);
                    } else {
                        $error = $upload['error'];
                    }
                }

                // Загрузка видео
                $video_path = '';
                if (!$error && isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadVideo($_FILES['video'], '../uploads/fireshow/videos/');
                    if ($upload['success']) {
                        $video_path = str_replace('../', '', $upload['filepath']);
                    } else {
                        $error = $upload['error'];
                    }
                }

                if (!$error) {
                    $stmt = $db->prepare("INSERT INTO fireshow_programs (name, slug, description, duration, min_artists, max_artists, price_per_artist, image, video, featured, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $slug, $description, $duration, $min_artists, $max_artists, $price_per_artist, $image_path, $video_path, $featured, $active]);
                    $success = 'Программа успешно добавлена!';
                }
            } catch (Exception $e) {
                $error = 'Ошибка при добавлении программы: ' . $e->getMessage();
            }
        } else {
            $error = 'Заполните все обязательные поля!';
        }
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $slug = generateSlug($name);
        $description = trim($_POST['description'] ?? '');
        $duration = (int)($_POST['duration'] ?? 0);
        $min_artists = (int)($_POST['min_artists'] ?? 1);
        $max_artists = (int)($_POST['max_artists'] ?? 1);
        $price_per_artist = floatval($_POST['price_per_artist'] ?? 0);
        $featured = isset($_POST['featured']) ? 1 : 0;
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name && $duration > 0 && $price_per_artist > 0 && $id) {
            try {
                // Получаем текущие пути к файлам
                $stmt = $db->prepare("SELECT image, video FROM fireshow_programs WHERE id = ?");
                $stmt->execute([$id]);
                $current = $stmt->fetch();
                $image_path = $current['image'];
                $video_path = $current['video'];

                // Загрузка нового изображения
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadImage($_FILES['image'], '../uploads/fireshow/programs/');
                    if ($upload['success']) {
                        // Удаляем старое изображение
                        if ($image_path && file_exists('../' . $image_path)) {
                            unlink('../' . $image_path);
                        }
                        $image_path = str_replace('../', '', $upload['filepath']);
                    }
                }

                // Загрузка нового видео
                if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadVideo($_FILES['video'], '../uploads/fireshow/videos/');
                    if ($upload['success']) {
                        // Удаляем старое видео
                        if ($video_path && file_exists('../' . $video_path)) {
                            unlink('../' . $video_path);
                        }
                        $video_path = str_replace('../', '', $upload['filepath']);
                    }
                }

                // Обновляем программу
                $stmt = $db->prepare("UPDATE fireshow_programs SET name = ?, slug = ?, description = ?, duration = ?, min_artists = ?, max_artists = ?, price_per_artist = ?, image = ?, video = ?, featured = ?, active = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $duration, $min_artists, $max_artists, $price_per_artist, $image_path, $video_path, $featured, $active, $id]);

                $success = 'Программа обновлена!';
            } catch (Exception $e) {
                $error = 'Ошибка при обновлении программы: ' . $e->getMessage();
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
                $stmt = $db->prepare("SELECT image, video FROM fireshow_programs WHERE id = ?");
                $stmt->execute([$id]);
                $program = $stmt->fetch();

                // Удаляем программу
                $stmt = $db->prepare("DELETE FROM fireshow_programs WHERE id = ?");
                $stmt->execute([$id]);

                // Удаляем файлы
                if ($program['image'] && file_exists('../' . $program['image'])) {
                    unlink('../' . $program['image']);
                }
                if ($program['video'] && file_exists('../' . $program['video'])) {
                    unlink('../' . $program['video']);
                }

                $success = 'Программа удалена!';
            } catch (Exception $e) {
                $error = 'Ошибка при удалении программы: ' . $e->getMessage();
            }
        }
    }
}

// Получаем все программы
$programs = $db->query("SELECT * FROM fireshow_programs ORDER BY featured DESC, created_at DESC")->fetchAll();

// Подсчитываем статистику
$total_programs = count($programs);
$active_programs = count(array_filter($programs, fn($p) => $p['active'] == 1));
$featured_programs = count(array_filter($programs, fn($p) => $p['featured'] == 1));
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Программы фаершоу - Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header">
                <h1>Программы фаершоу</h1>
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
                                <?= $total_programs ?>
                            </div>
                            <div style="color: #666; margin-top: 0.5rem;">
                                Всего программ
                            </div>
                        </div>
                        <div style="padding: 1rem; background: #f5f5f5; border-radius: 8px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; color: #4caf50;">
                                <?= $active_programs ?>
                            </div>
                            <div style="color: #666; margin-top: 0.5rem;">
                                Активных
                            </div>
                        </div>
                        <div style="padding: 1rem; background: #f5f5f5; border-radius: 8px; text-align: center;">
                            <div style="font-size: 2rem; font-weight: bold; color: #ffc107;">
                                <?= $featured_programs ?>
                            </div>
                            <div style="color: #666; margin-top: 0.5rem;">
                                Рекомендуемых
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Форма добавления -->
                <div class="section-box">
                    <h2>Добавить программу</h2>
                    <form method="POST" enctype="multipart/form-data" class="admin-form">
                        <input type="hidden" name="action" value="add">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Название программы *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Длительность (минуты) *</label>
                                <input type="number" name="duration" class="form-control" min="1" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Описание</label>
                            <textarea name="description" class="form-control" rows="4"></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Мин. артистов *</label>
                                <input type="number" name="min_artists" class="form-control" min="1" value="1" required>
                            </div>

                            <div class="form-group">
                                <label>Макс. артистов *</label>
                                <input type="number" name="max_artists" class="form-control" min="1" value="1" required>
                            </div>

                            <div class="form-group">
                                <label>Цена за артиста (грн) *</label>
                                <input type="number" name="price_per_artist" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Изображение программы</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small>Рекомендуемый размер: 1200x800px. Макс. размер: 5MB</small>
                        </div>

                        <div class="form-group">
                            <label>Видео программы</label>
                            <input type="file" name="video" class="form-control" accept=".mp4,.mov,.avi,.wmv,.flv,.webm,.ogg,.ogv,.mkv,.3gp,.m4v,.mpeg,.mpg,video/*">
                            <small>Допустимые форматы: MP4, MKV, AVI, MOV, WMV, FLV, WebM, OGG и другие. Макс. размер: 2GB</small>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="featured"> Рекомендуемая программа
                            </label>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="active" checked> Активна
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success">Добавить программу</button>
                    </form>
                </div>

                <!-- Список программ -->
                <div class="section-box">
                    <h2>Список программ (<?= count($programs) ?>)</h2>

                    <?php if (empty($programs)): ?>
                        <p>Программ пока нет</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Фото</th>
                                    <th>Название</th>
                                    <th>Длительность</th>
                                    <th>Артисты</th>
                                    <th>Цена/артист</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($programs as $program): ?>
                                    <tr>
                                        <td><?= $program['id'] ?></td>
                                        <td>
                                            <?php if ($program['image']): ?>
                                                <img src="../<?= escape($program['image']) ?>"
                                                     alt="<?= escape($program['name']) ?>"
                                                     style="width: 80px; height: 60px; object-fit: cover; border-radius: 8px;">
                                            <?php else: ?>
                                                <div style="width: 80px; height: 60px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    🔥
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= escape($program['name']) ?></strong>
                                            <?php if ($program['featured']): ?>
                                                <span class="badge" style="background: #ffc107; color: #000;">⭐ Рекомендуем</span>
                                            <?php endif; ?>
                                            <br>
                                            <small style="color: #666;"><?= escape(mb_substr($program['description'] ?? '', 0, 60)) ?></small>
                                        </td>
                                        <td><strong><?= $program['duration'] ?> мин</strong></td>
                                        <td><?= $program['min_artists'] ?>-<?= $program['max_artists'] ?></td>
                                        <td><strong><?= formatPrice($program['price_per_artist']) ?></strong></td>
                                        <td>
                                            <?php if ($program['active']): ?>
                                                <span class="badge badge-success">Активна</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Неактивна</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button onclick="editProgram(<?= $program['id'] ?>)" class="btn btn-primary btn-sm">Редактировать</button>
                                            <button onclick="deleteProgram(<?= $program['id'] ?>, '<?= escape($program['name']) ?>')" class="btn btn-danger btn-sm">Удалить</button>
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
            <h2>Редактировать программу</h2>
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-row">
                    <div class="form-group">
                        <label>Название программы *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Длительность (минуты) *</label>
                        <input type="number" name="duration" id="edit_duration" class="form-control" min="1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="4"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Мин. артистов *</label>
                        <input type="number" name="min_artists" id="edit_min_artists" class="form-control" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Макс. артистов *</label>
                        <input type="number" name="max_artists" id="edit_max_artists" class="form-control" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Цена за артиста (грн) *</label>
                        <input type="number" name="price_per_artist" id="edit_price_per_artist" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Текущее изображение</label>
                    <div id="edit_current_image"></div>
                </div>

                <div class="form-group">
                    <label>Заменить изображение</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <div class="form-group">
                    <label>Текущее видео</label>
                    <div id="edit_current_video"></div>
                </div>

                <div class="form-group">
                    <label>Заменить видео</label>
                    <input type="file" name="video" class="form-control" accept=".mp4,.mov,.avi,.wmv,.flv,.webm,.ogg,.ogv,.mkv,.3gp,.m4v,.mpeg,.mpg,video/*">
                    <small>Допустимые форматы: MP4, MKV, AVI, MOV, WMV, FLV, WebM, OGG и другие. Макс. размер: 2GB</small>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="featured" id="edit_featured"> Рекомендуемая программа
                    </label>
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
        function editProgram(id) {
            fetch(`../api/programs.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const p = data.program;
                        document.getElementById('edit_id').value = p.id;
                        document.getElementById('edit_name').value = p.name;
                        document.getElementById('edit_duration').value = p.duration;
                        document.getElementById('edit_description').value = p.description || '';
                        document.getElementById('edit_min_artists').value = p.min_artists;
                        document.getElementById('edit_max_artists').value = p.max_artists;
                        document.getElementById('edit_price_per_artist').value = p.price_per_artist;
                        document.getElementById('edit_featured').checked = p.featured == 1;
                        document.getElementById('edit_active').checked = p.active == 1;

                        // Показываем текущее изображение
                        const currentImg = document.getElementById('edit_current_image');
                        if (p.image) {
                            currentImg.innerHTML = `<img src="../${p.image}" style="max-width: 300px; border-radius: 8px;">`;
                        } else {
                            currentImg.innerHTML = '<p style="color: #999;">Нет изображения</p>';
                        }

                        // Показываем текущее видео
                        const currentVideo = document.getElementById('edit_current_video');
                        if (p.video) {
                            currentVideo.innerHTML = `
                                <video controls style="max-width: 400px; border-radius: 8px;">
                                    <source src="../${p.video}" type="video/mp4">
                                </video>
                            `;
                        } else {
                            currentVideo.innerHTML = '<p style="color: #999;">Нет видео</p>';
                        }

                        document.getElementById('editModal').classList.add('active');
                    } else {
                        alert('Ошибка: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Ошибка при загрузке данных программы');
                });
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function deleteProgram(id, name) {
            if (confirm(`Удалить программу "${name}"?\n\nВсе файлы программы также будут удалены.`)) {
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
