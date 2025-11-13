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

// Типы ингредиентов с переводами
$ingredientTypes = [
    'base' => 'Основа торта',
    'filling' => 'Начинка',
    'cream' => 'Крем',
    'decoration' => 'Украшения',
    'topping' => 'Топпинг'
];

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? '';
        $price = floatval($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $image = '';

        // Проверка обязательных полей
        if (!$name) {
            $error = 'Название обязательно!';
        } elseif (!$type || !array_key_exists($type, $ingredientTypes)) {
            $error = 'Выберите корректный тип!';
        } elseif ($price <= 0) {
            $error = 'Цена должна быть больше 0!';
        } else {
            // Загрузка изображения
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadImage($_FILES['image'], '../uploads/ingredients/');
                if ($uploadResult['success']) {
                    $image = $uploadResult['filepath'];
                } else {
                    $error = $uploadResult['error'];
                }
            }

            // Если ошибок нет, добавляем ингредиент
            if (!$error) {
                $stmt = $db->prepare("INSERT INTO cake_ingredients (type, name, description, price, image, active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$type, $name, $description, $price, $image, $active]);
                $success = 'Ингредиент успешно добавлен!';
            }
        }
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? '';
        $price = floatval($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;

        // Проверка обязательных полей
        if (!$name) {
            $error = 'Название обязательно!';
        } elseif (!$type || !array_key_exists($type, $ingredientTypes)) {
            $error = 'Выберите корректный тип!';
        } elseif ($price <= 0) {
            $error = 'Цена должна быть больше 0!';
        } elseif (!$id) {
            $error = 'ID ингредиента не указан!';
        } else {
            // Получаем текущий ингредиент
            $stmt = $db->prepare("SELECT image FROM cake_ingredients WHERE id = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetch();

            $image = $current['image'];

            // Загрузка нового изображения
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadImage($_FILES['image'], '../uploads/ingredients/');
                if ($uploadResult['success']) {
                    // Удаляем старое изображение
                    if ($image && file_exists('../' . $image)) {
                        unlink('../' . $image);
                    }
                    $image = $uploadResult['filepath'];
                } else {
                    $error = $uploadResult['error'];
                }
            }

            // Если ошибок нет, обновляем ингредиент
            if (!$error) {
                $stmt = $db->prepare("UPDATE cake_ingredients SET type = ?, name = ?, description = ?, price = ?, image = ?, active = ? WHERE id = ?");
                $stmt->execute([$type, $name, $description, $price, $image, $active, $id]);
                $success = 'Ингредиент обновлен!';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            // Получаем путь к изображению
            $stmt = $db->prepare("SELECT image FROM cake_ingredients WHERE id = ?");
            $stmt->execute([$id]);
            $ingredient = $stmt->fetch();

            // Удаляем изображение с сервера
            if ($ingredient && $ingredient['image'] && file_exists('../' . $ingredient['image'])) {
                unlink('../' . $ingredient['image']);
            }

            // Удаляем ингредиент из базы
            $stmt = $db->prepare("DELETE FROM cake_ingredients WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Ингредиент удален!';
        }
    }
}

// Получаем все ингредиенты, сгруппированные по типу
$ingredients = $db->query("SELECT * FROM cake_ingredients ORDER BY type, name")->fetchAll();

// Группируем ингредиенты по типу
$groupedIngredients = [];
foreach ($ingredients as $ing) {
    $groupedIngredients[$ing['type']][] = $ing;
}

// Подсчет ингредиентов по типам
$typeCounts = [];
foreach ($ingredientTypes as $typeKey => $typeName) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM cake_ingredients WHERE type = ?");
    $stmt->execute([$typeKey]);
    $typeCounts[$typeKey] = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ингредиенты - Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header">
                <h1>Ингредиенты для конструктора тортов</h1>
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

                <!-- Форма добавления -->
                <div class="section-box">
                    <h2>Добавить ингредиент</h2>
                    <form method="POST" enctype="multipart/form-data" class="admin-form">
                        <input type="hidden" name="action" value="add">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Название *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Тип *</label>
                                <select name="type" class="form-control" required>
                                    <option value="">Выберите тип</option>
                                    <?php foreach ($ingredientTypes as $key => $label): ?>
                                        <option value="<?= $key ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Цена (грн) *</label>
                                <input type="number" name="price" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Изображение</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small style="color: #666;">Форматы: JPG, PNG, GIF, WEBP. Макс. размер: 5MB</small>
                            </div>

                            <div class="form-group">
                                <label>Описание</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="active" checked> Активен
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success">Добавить</button>
                    </form>
                </div>

                <!-- Статистика по типам -->
                <div class="section-box">
                    <h2>Статистика по типам</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <?php foreach ($ingredientTypes as $typeKey => $typeName): ?>
                            <div style="padding: 1rem; background: #f5f5f5; border-radius: 8px; text-align: center;">
                                <div style="font-size: 2rem; font-weight: bold; color: #9b59b6;"><?= $typeCounts[$typeKey] ?></div>
                                <div style="color: #666;"><?= $typeName ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Список ингредиентов по типам -->
                <?php foreach ($ingredientTypes as $typeKey => $typeName): ?>
                    <?php if (isset($groupedIngredients[$typeKey]) && !empty($groupedIngredients[$typeKey])): ?>
                        <div class="section-box">
                            <h2><?= $typeName ?> (<?= count($groupedIngredients[$typeKey]) ?>)</h2>

                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Изображение</th>
                                        <th>Название</th>
                                        <th>Цена</th>
                                        <th>Описание</th>
                                        <th>Статус</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groupedIngredients[$typeKey] as $ing): ?>
                                        <tr>
                                            <td><?= $ing['id'] ?></td>
                                            <td>
                                                <?php if ($ing['image']): ?>
                                                    <img src="../<?= escape($ing['image']) ?>" alt="<?= escape($ing['name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                                <?php else: ?>
                                                    <div style="width: 50px; height: 50px; background: #e0e0e0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🍰</div>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?= escape($ing['name']) ?></strong></td>
                                            <td><?= formatPrice($ing['price']) ?></td>
                                            <td><?= escape(mb_substr($ing['description'] ?? '', 0, 50)) ?></td>
                                            <td>
                                                <?php if ($ing['active']): ?>
                                                    <span class="badge badge-success">Активен</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Неактивен</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button onclick="editIngredient(<?= $ing['id'] ?>)" class="btn btn-primary btn-sm">Редактировать</button>
                                                <button onclick="deleteIngredient(<?= $ing['id'] ?>, '<?= escape($ing['name']) ?>')" class="btn btn-danger btn-sm">Удалить</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <?php if (empty($ingredients)): ?>
                    <div class="section-box">
                        <p>Ингредиентов пока нет</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <h2>Редактировать ингредиент</h2>
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label>Название *</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Тип *</label>
                    <select name="type" id="edit_type" class="form-control" required>
                        <?php foreach ($ingredientTypes as $key => $label): ?>
                            <option value="<?= $key ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Цена (грн) *</label>
                    <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0.01" required>
                </div>

                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Текущее изображение:</label>
                    <div id="current_image_preview" style="margin: 0.5rem 0;"></div>
                    <label>Новое изображение (оставьте пустым, чтобы не менять):</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="active" id="edit_active"> Активен
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
        function editIngredient(id) {
            fetch(`../api/ingredients.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const ing = data.ingredient;
                        document.getElementById('edit_id').value = ing.id;
                        document.getElementById('edit_name').value = ing.name;
                        document.getElementById('edit_type').value = ing.type;
                        document.getElementById('edit_price').value = ing.price;
                        document.getElementById('edit_description').value = ing.description || '';
                        document.getElementById('edit_active').checked = ing.active == 1;

                        // Показываем текущее изображение
                        const imagePreview = document.getElementById('current_image_preview');
                        if (ing.image) {
                            imagePreview.innerHTML = `<img src="../${ing.image}" alt="${ing.name}" style="max-width: 200px; max-height: 200px; border-radius: 8px;">`;
                        } else {
                            imagePreview.innerHTML = '<p style="color: #999;">Изображение отсутствует</p>';
                        }

                        document.getElementById('editModal').classList.add('active');
                    }
                });
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function deleteIngredient(id, name) {
            if (confirm(`Удалить ингредиент "${name}"?\n\nВнимание: изображение также будет удалено с сервера!`)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Закрытие модального окна при клике вне его
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
