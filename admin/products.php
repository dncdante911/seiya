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
        $price = floatval($_POST['price'] ?? 0);
        $weight = trim($_POST['weight'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name && $price > 0 && $category_id > 0) {
            try {
                // Загрузка основного изображения
                $image_path = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadImage($_FILES['image'], '../uploads/products/');
                    if ($upload['success']) {
                        $image_path = str_replace('../', '', $upload['filepath']);
                    } else {
                        $error = $upload['error'];
                    }
                }

                if (!$error) {
                    // Добавляем товар
                    $stmt = $db->prepare("INSERT INTO products (name, slug, description, price, weight, category_id, image, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $slug, $description, $price, $weight, $category_id, $image_path, $active]);
                    $product_id = $db->lastInsertId();

                    // Загрузка дополнительных изображений
                    if (isset($_FILES['additional_images'])) {
                        $files = $_FILES['additional_images'];
                        $sort_order = 1;

                        for ($i = 0; $i < count($files['name']); $i++) {
                            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                                $file = [
                                    'name' => $files['name'][$i],
                                    'type' => $files['type'][$i],
                                    'tmp_name' => $files['tmp_name'][$i],
                                    'error' => $files['error'][$i],
                                    'size' => $files['size'][$i]
                                ];

                                $upload = uploadImage($file, '../uploads/products/');
                                if ($upload['success']) {
                                    $img_path = str_replace('../', '', $upload['filepath']);
                                    $stmt = $db->prepare("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)");
                                    $stmt->execute([$product_id, $img_path, $sort_order++]);
                                }
                            }
                        }
                    }

                    $success = 'Товар успешно добавлен!';
                }
            } catch (Exception $e) {
                $error = 'Ошибка при добавлении товара: ' . $e->getMessage();
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
        $price = floatval($_POST['price'] ?? 0);
        $weight = trim($_POST['weight'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name && $price > 0 && $category_id > 0 && $id) {
            try {
                // Получаем текущий путь к изображению
                $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $current = $stmt->fetch();
                $image_path = $current['image'];

                // Загрузка нового основного изображения
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload = uploadImage($_FILES['image'], '../uploads/products/');
                    if ($upload['success']) {
                        // Удаляем старое изображение
                        if ($image_path && file_exists('../' . $image_path)) {
                            unlink('../' . $image_path);
                        }
                        $image_path = str_replace('../', '', $upload['filepath']);
                    }
                }

                // Обновляем товар
                $stmt = $db->prepare("UPDATE products SET name = ?, slug = ?, description = ?, price = ?, weight = ?, category_id = ?, image = ?, active = ? WHERE id = ?");
                $stmt->execute([$name, $slug, $description, $price, $weight, $category_id, $image_path, $active, $id]);

                // Загрузка дополнительных изображений
                if (isset($_FILES['additional_images'])) {
                    $files = $_FILES['additional_images'];

                    // Получаем текущий максимальный sort_order
                    $stmt = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) as max_order FROM product_images WHERE product_id = ?");
                    $stmt->execute([$id]);
                    $sort_order = $stmt->fetchColumn() + 1;

                    for ($i = 0; $i < count($files['name']); $i++) {
                        if ($files['error'][$i] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $files['name'][$i],
                                'type' => $files['type'][$i],
                                'tmp_name' => $files['tmp_name'][$i],
                                'error' => $files['error'][$i],
                                'size' => $files['size'][$i]
                            ];

                            $upload = uploadImage($file, '../uploads/products/');
                            if ($upload['success']) {
                                $img_path = str_replace('../', '', $upload['filepath']);
                                $stmt = $db->prepare("INSERT INTO product_images (product_id, image_path, sort_order) VALUES (?, ?, ?)");
                                $stmt->execute([$id, $img_path, $sort_order++]);
                            }
                        }
                    }
                }

                $success = 'Товар обновлен!';
            } catch (Exception $e) {
                $error = 'Ошибка при обновлении товара: ' . $e->getMessage();
            }
        } else {
            $error = 'Заполните все обязательные поля!';
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            try {
                // Получаем изображения для удаления
                $stmt = $db->prepare("SELECT image FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $product = $stmt->fetch();

                $stmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
                $stmt->execute([$id]);
                $images = $stmt->fetchAll();

                // Удаляем товар (изображения удалятся автоматически по CASCADE)
                $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$id]);

                // Удаляем файлы изображений
                if ($product['image'] && file_exists('../' . $product['image'])) {
                    unlink('../' . $product['image']);
                }

                foreach ($images as $img) {
                    if ($img['image_path'] && file_exists('../' . $img['image_path'])) {
                        unlink('../' . $img['image_path']);
                    }
                }

                $success = 'Товар удален!';
            } catch (Exception $e) {
                $error = 'Ошибка при удалении товара: ' . $e->getMessage();
            }
        }
    }

    if ($action === 'delete_image') {
        $image_id = (int)$_POST['image_id'];
        if ($image_id) {
            try {
                // Получаем путь к изображению
                $stmt = $db->prepare("SELECT image_path FROM product_images WHERE id = ?");
                $stmt->execute([$image_id]);
                $image = $stmt->fetch();

                if ($image) {
                    // Удаляем запись
                    $stmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
                    $stmt->execute([$image_id]);

                    // Удаляем файл
                    if ($image['image_path'] && file_exists('../' . $image['image_path'])) {
                        unlink('../' . $image['image_path']);
                    }

                    $success = 'Изображение удалено!';
                }
            } catch (Exception $e) {
                $error = 'Ошибка при удалении изображения: ' . $e->getMessage();
            }
        }
    }
}

// Получаем все категории для селекта
$categories = $db->query("SELECT * FROM categories WHERE active = 1 ORDER BY name")->fetchAll();

// Получаем все товары с информацией о категориях
$products = $db->query("
    SELECT p.*, c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.created_at DESC
")->fetchAll();

// Подсчитываем товары по категориям
$category_counts = [];
$stmt = $db->query("SELECT category_id, COUNT(*) as count FROM products GROUP BY category_id");
while ($row = $stmt->fetch()) {
    $category_counts[$row['category_id']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Товары - Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header">
                <h1>Товары кондитерки</h1>
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

                <!-- Статистика по категориям -->
                <div class="section-box">
                    <h2>Товары по категориям</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                        <?php foreach ($categories as $cat): ?>
                            <div style="padding: 1rem; background: #f5f5f5; border-radius: 8px; text-align: center;">
                                <div style="font-size: 2rem; font-weight: bold; color: #e91e63;">
                                    <?= $category_counts[$cat['id']] ?? 0 ?>
                                </div>
                                <div style="color: #666; margin-top: 0.5rem;">
                                    <?= escape($cat['name']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Форма добавления -->
                <div class="section-box">
                    <h2>Добавить товар</h2>
                    <form method="POST" enctype="multipart/form-data" class="admin-form">
                        <input type="hidden" name="action" value="add">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Название *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label>Категория *</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Выберите категорию</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= escape($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Цена (грн) *</label>
                                <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                            </div>

                            <div class="form-group">
                                <label>Вес / Размер</label>
                                <input type="text" name="weight" class="form-control" placeholder="например: 1 кг, 500 г">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Описание</label>
                            <textarea name="description" class="form-control" rows="4"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Основное изображение</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small>Рекомендуемый размер: 800x800px. Макс. размер: 5MB</small>
                        </div>

                        <div class="form-group">
                            <label>Дополнительные изображения</label>
                            <input type="file" name="additional_images[]" class="form-control" accept="image/*" multiple>
                            <small>Можно загрузить несколько изображений</small>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="active" checked> Активен
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success">Добавить товар</button>
                    </form>
                </div>

                <!-- Список товаров -->
                <div class="section-box">
                    <h2>Список товаров (<?= count($products) ?>)</h2>

                    <?php if (empty($products)): ?>
                        <p>Товаров пока нет</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Фото</th>
                                    <th>Название</th>
                                    <th>Категория</th>
                                    <th>Цена</th>
                                    <th>Вес</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?= $product['id'] ?></td>
                                        <td>
                                            <?php if ($product['image']): ?>
                                                <img src="../<?= escape($product['image']) ?>"
                                                     alt="<?= escape($product['name']) ?>"
                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                            <?php else: ?>
                                                <div style="width: 60px; height: 60px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    📷
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= escape($product['name']) ?></strong><br>
                                            <small style="color: #666;"><?= escape(mb_substr($product['description'] ?? '', 0, 60)) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: #2196f3;">
                                                <?= escape($product['category_name'] ?? 'Без категории') ?>
                                            </span>
                                        </td>
                                        <td><strong><?= formatPrice($product['price']) ?></strong></td>
                                        <td><?= escape($product['weight'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($product['active']): ?>
                                                <span class="badge badge-success">Активен</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Неактивен</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button onclick="editProduct(<?= $product['id'] ?>)" class="btn btn-primary btn-sm">Редактировать</button>
                                            <button onclick="deleteProduct(<?= $product['id'] ?>, '<?= escape($product['name']) ?>')" class="btn btn-danger btn-sm">Удалить</button>
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
            <h2>Редактировать товар</h2>
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-row">
                    <div class="form-group">
                        <label>Название *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Категория *</label>
                        <select name="category_id" id="edit_category_id" class="form-control" required>
                            <option value="">Выберите категорию</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= escape($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Цена (грн) *</label>
                        <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Вес / Размер</label>
                        <input type="text" name="weight" id="edit_weight" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" id="edit_description" class="form-control" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label>Текущее основное изображение</label>
                    <div id="edit_current_image"></div>
                </div>

                <div class="form-group">
                    <label>Заменить основное изображение</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>

                <div class="form-group">
                    <label>Дополнительные изображения</label>
                    <div id="edit_additional_images" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;"></div>
                    <input type="file" name="additional_images[]" class="form-control" accept="image/*" multiple>
                    <small>Можно добавить еще изображения</small>
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

    <form method="POST" id="deleteImageForm" style="display: none;">
        <input type="hidden" name="action" value="delete_image">
        <input type="hidden" name="image_id" id="delete_image_id">
    </form>

    <script>
        function editProduct(id) {
            fetch(`../api/products.php?id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const p = data.product;
                        document.getElementById('edit_id').value = p.id;
                        document.getElementById('edit_name').value = p.name;
                        document.getElementById('edit_category_id').value = p.category_id;
                        document.getElementById('edit_price').value = p.price;
                        document.getElementById('edit_weight').value = p.weight || '';
                        document.getElementById('edit_description').value = p.description || '';
                        document.getElementById('edit_active').checked = p.active == 1;

                        // Показываем текущее изображение
                        const currentImg = document.getElementById('edit_current_image');
                        if (p.image) {
                            currentImg.innerHTML = `<img src="../${p.image}" style="max-width: 200px; border-radius: 8px;">`;
                        } else {
                            currentImg.innerHTML = '<p style="color: #999;">Нет изображения</p>';
                        }

                        // Показываем дополнительные изображения
                        const additionalImgs = document.getElementById('edit_additional_images');
                        additionalImgs.innerHTML = '';
                        if (data.images && data.images.length > 0) {
                            data.images.forEach(img => {
                                const div = document.createElement('div');
                                div.style.position = 'relative';
                                div.innerHTML = `
                                    <img src="../${img.image_path}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                                    <button type="button" onclick="deleteImage(${img.id})"
                                            style="position: absolute; top: 5px; right: 5px; background: #e91e63; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; font-size: 16px; line-height: 1;">
                                        &times;
                                    </button>
                                `;
                                additionalImgs.appendChild(div);
                            });
                        }

                        document.getElementById('editModal').classList.add('active');
                    } else {
                        alert('Ошибка: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Ошибка при загрузке данных товара');
                });
        }

        function closeModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function deleteProduct(id, name) {
            if (confirm(`Удалить товар "${name}"?\n\nВсе изображения товара также будут удалены.`)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        function deleteImage(imageId) {
            if (confirm('Удалить это изображение?')) {
                document.getElementById('delete_image_id').value = imageId;
                document.getElementById('deleteImageForm').submit();
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
