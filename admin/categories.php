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

        if ($name) {
            $stmt = $db->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $description]);
            $success = 'Категория успешно добавлена!';
        } else {
            $error = 'Название категории обязательно!';
        }
    }

    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $slug = generateSlug($name);
        $description = trim($_POST['description'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name && $id) {
            $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, active = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $description, $active, $id]);
            $success = 'Категория обновлена!';
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            // Проверяем, есть ли товары в категории
            $count = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $count->execute([$id]);
            if ($count->fetchColumn() > 0) {
                $error = 'Невозможно удалить категорию с товарами!';
            } else {
                $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Категория удалена!';
            }
        }
    }
}

// Получаем все категории
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Категории - Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header">
                <h1>Категории товаров</h1>
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
                    <h2>Добавить категорию</h2>
                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="add">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Название *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Описание</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success">Добавить</button>
                    </form>
                </div>

                <!-- Список категорий -->
                <div class="section-box">
                    <h2>Список категорий (<?= count($categories) ?>)</h2>

                    <?php if (empty($categories)): ?>
                        <p>Категорий пока нет</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Slug</th>
                                    <th>Описание</th>
                                    <th>Товаров</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $cat):
                                    $product_count = $db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                                    $product_count->execute([$cat['id']]);
                                    $count = $product_count->fetchColumn();
                                ?>
                                    <tr>
                                        <td><?= $cat['id'] ?></td>
                                        <td><strong><?= escape($cat['name']) ?></strong></td>
                                        <td><code><?= escape($cat['slug']) ?></code></td>
                                        <td><?= escape(mb_substr($cat['description'] ?? '', 0, 50)) ?></td>
                                        <td><?= $count ?></td>
                                        <td>
                                            <?php if ($cat['active']): ?>
                                                <span class="badge badge-success">Активна</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Неактивна</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button onclick="editCategory(<?= $cat['id'] ?>)" class="btn btn-primary btn-sm">Редактировать</button>
                                            <button onclick="deleteCategory(<?= $cat['id'] ?>, '<?= escape($cat['name']) ?>')" class="btn btn-danger btn-sm">Удалить</button>
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
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <h2>Редактировать категорию</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label>Название *</label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
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
        function editCategory(id) {
            fetch(`../api/categories.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('edit_id').value = data.category.id;
                        document.getElementById('edit_name').value = data.category.name;
                        document.getElementById('edit_description').value = data.category.description || '';
                        document.getElementById('edit_active').checked = data.category.active == 1;
                        document.getElementById('editModal').classList.add('active');
                    }
                });
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function deleteCategory(id, name) {
            if (confirm(`Удалить категорию "${name}"?`)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html>
