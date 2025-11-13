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

    if ($action === 'update_status') {
        $id = (int)$_POST['id'];
        $status = $_POST['status'] ?? '';
        $valid_statuses = ['new', 'confirmed', 'in_progress', 'completed', 'cancelled'];

        if ($id && in_array($status, $valid_statuses)) {
            try {
                $stmt = $db->prepare("UPDATE orders_confectionery SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                $success = 'Статус заказа обновлен!';
            } catch (Exception $e) {
                $error = 'Ошибка при обновлении статуса: ' . $e->getMessage();
            }
        } else {
            $error = 'Некорректные данные!';
        }
    }

    if ($action === 'add_note') {
        $id = (int)$_POST['id'];
        $note = trim($_POST['note'] ?? '');

        if ($id && $note) {
            try {
                $stmt = $db->prepare("SELECT notes FROM orders_confectionery WHERE id = ?");
                $stmt->execute([$id]);
                $order = $stmt->fetch();

                $admin_username = $_SESSION['admin_username'] ?? 'Админ';
                $timestamp = date('d.m.Y H:i');
                $new_note = "[$timestamp] $admin_username: $note";

                $existing_notes = $order['notes'] ?? '';
                $updated_notes = $existing_notes ? $existing_notes . "\n" . $new_note : $new_note;

                $stmt = $db->prepare("UPDATE orders_confectionery SET notes = ? WHERE id = ?");
                $stmt->execute([$updated_notes, $id]);
                $success = 'Заметка добавлена!';
            } catch (Exception $e) {
                $error = 'Ошибка при добавлении заметки: ' . $e->getMessage();
            }
        } else {
            $error = 'Введите текст заметки!';
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($id) {
            try {
                $stmt = $db->prepare("DELETE FROM orders_confectionery WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Заказ удален!';
            } catch (Exception $e) {
                $error = 'Ошибка при удалении заказа: ' . $e->getMessage();
            }
        }
    }
}

// Фильтр по статусу
$status_filter = $_GET['status'] ?? 'all';
$where_clause = '';
$params = [];

if ($status_filter !== 'all') {
    $where_clause = 'WHERE status = ?';
    $params[] = $status_filter;
}

// Получаем все заказы
$query = "
    SELECT o.*, p.name as product_name, p.image as product_image
    FROM orders_confectionery o
    LEFT JOIN products p ON o.product_id = p.id
    $where_clause
    ORDER BY
        CASE
            WHEN o.status = 'new' THEN 1
            WHEN o.status = 'confirmed' THEN 2
            WHEN o.status = 'in_progress' THEN 3
            WHEN o.status = 'completed' THEN 4
            WHEN o.status = 'cancelled' THEN 5
        END,
        o.created_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Подсчет заказов по статусам
$stats = [
    'all' => 0,
    'new' => 0,
    'confirmed' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'cancelled' => 0
];

$stmt = $db->query("SELECT status, COUNT(*) as count FROM orders_confectionery GROUP BY status");
while ($row = $stmt->fetch()) {
    $stats[$row['status']] = $row['count'];
    $stats['all'] += $row['count'];
}

// Функция для получения названия статуса
function getStatusName($status) {
    $names = [
        'new' => 'Новый',
        'confirmed' => 'Подтвержден',
        'in_progress' => 'В работе',
        'completed' => 'Завершен',
        'cancelled' => 'Отменен'
    ];
    return $names[$status] ?? $status;
}

// Функция для получения класса badge статуса
function getStatusBadgeClass($status) {
    $classes = [
        'new' => 'badge-new',
        'confirmed' => 'badge-confirmed',
        'in_progress' => 'badge-in_progress',
        'completed' => 'badge-completed',
        'cancelled' => 'badge-cancelled'
    ];
    return $classes[$status] ?? '';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказы кондитерки - Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .filter-btn {
            padding: 0.6rem 1.2rem;
            border: 2px solid #ddd;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
            font-weight: 500;
        }

        .filter-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .filter-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .filter-btn .count {
            background: rgba(0,0,0,0.1);
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            margin-left: 0.5rem;
            font-size: 0.85rem;
        }

        .filter-btn.active .count {
            background: rgba(255,255,255,0.3);
        }

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .order-details dt {
            font-weight: 600;
            color: #666;
        }

        .order-details dd {
            margin: 0;
            color: #333;
        }

        .order-json-preview {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #666;
            font-size: 0.85rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: #999;
            line-height: 1;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .modal-close:hover {
            background: #f0f0f0;
            color: #333;
        }

        .order-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .order-info-item {
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .order-info-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.3rem;
        }

        .order-info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
        }

        .product-details {
            background: #f0f8ff;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .product-details h3 {
            margin-top: 0;
            color: var(--primary-color);
        }

        .constructor-details {
            background: #fff5f5;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .constructor-details h3 {
            margin-top: 0;
            color: #e91e63;
        }

        .detail-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            margin-right: 0.5rem;
        }

        .notes-section {
            background: #fffef0;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1.5rem;
        }

        .notes-section h3 {
            margin-top: 0;
            color: #ff9800;
        }

        .notes-content {
            white-space: pre-line;
            color: #666;
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .status-change-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-top: 0.5rem;
        }

        .badge-new {
            background: #f44336;
            color: white;
        }

        .badge-confirmed {
            background: #2196f3;
            color: white;
        }

        .badge-in_progress {
            background: #ffc107;
            color: #333;
        }

        .badge-completed {
            background: #4caf50;
            color: white;
        }

        .badge-cancelled {
            background: #9e9e9e;
            color: white;
        }

        .quick-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header">
                <h1>Заказы кондитерки</h1>
                <div class="admin-user">
                    <?= escape($_SESSION['admin_username']) ?>
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

                <!-- Фильтры -->
                <div class="section-box">
                    <h2>Фильтр заказов</h2>
                    <div class="filter-buttons">
                        <a href="?status=all" class="filter-btn <?= $status_filter === 'all' ? 'active' : '' ?>">
                            Все <span class="count"><?= $stats['all'] ?></span>
                        </a>
                        <a href="?status=new" class="filter-btn <?= $status_filter === 'new' ? 'active' : '' ?>">
                            Новые <span class="count"><?= $stats['new'] ?></span>
                        </a>
                        <a href="?status=confirmed" class="filter-btn <?= $status_filter === 'confirmed' ? 'active' : '' ?>">
                            Подтверждены <span class="count"><?= $stats['confirmed'] ?></span>
                        </a>
                        <a href="?status=in_progress" class="filter-btn <?= $status_filter === 'in_progress' ? 'active' : '' ?>">
                            В работе <span class="count"><?= $stats['in_progress'] ?></span>
                        </a>
                        <a href="?status=completed" class="filter-btn <?= $status_filter === 'completed' ? 'active' : '' ?>">
                            Завершены <span class="count"><?= $stats['completed'] ?></span>
                        </a>
                        <a href="?status=cancelled" class="filter-btn <?= $status_filter === 'cancelled' ? 'active' : '' ?>">
                            Отменены <span class="count"><?= $stats['cancelled'] ?></span>
                        </a>
                    </div>
                </div>

                <!-- Список заказов -->
                <div class="section-box">
                    <h2>Список заказов (<?= count($orders) ?>)</h2>

                    <?php if (empty($orders)): ?>
                        <p style="text-align: center; color: #999; padding: 2rem;">Заказов не найдено</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Клиент</th>
                                    <th>Контакты</th>
                                    <th>Доставка</th>
                                    <th>Детали заказа</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th>Дата создания</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?= $order['id'] ?></strong></td>
                                        <td><?= escape($order['customer_name']) ?></td>
                                        <td>
                                            <div style="font-size: 0.9rem;">
                                                <div><?= escape($order['customer_phone']) ?></div>
                                                <?php if ($order['customer_email']): ?>
                                                    <div style="color: #666; font-size: 0.85rem;"><?= escape($order['customer_email']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.9rem;">
                                                <div><strong><?= formatDate($order['delivery_date']) ?></strong></div>
                                                <?php if ($order['delivery_time']): ?>
                                                    <div style="color: #666;"><?= escape($order['delivery_time']) ?></div>
                                                <?php endif; ?>
                                                <?php if ($order['delivery_address']): ?>
                                                    <div style="color: #666; font-size: 0.85rem; margin-top: 0.3rem;">
                                                        <?= escape($order['delivery_address']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($order['order_type'] === 'product' && $order['product_name']): ?>
                                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                                    <?php if ($order['product_image']): ?>
                                                        <img src="../<?= escape($order['product_image']) ?>"
                                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;"
                                                             alt="">
                                                    <?php endif; ?>
                                                    <div>
                                                        <div style="font-weight: 600;"><?= escape($order['product_name']) ?></div>
                                                        <small style="color: #666;">Готовый товар</small>
                                                    </div>
                                                </div>
                                            <?php elseif ($order['order_type'] === 'cake'): ?>
                                                <div>
                                                    <div style="font-weight: 600;">Торт-конструктор</div>
                                                    <div class="order-json-preview" title="<?= escape($order['order_details']) ?>">
                                                        <?php
                                                        $details = json_decode($order['order_details'], true);
                                                        if ($details) {
                                                            echo "Форма: " . escape($details['shape'] ?? 'не указана');
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #999;">Нет данных</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong style="color: var(--primary-color);"><?= formatPrice($order['total_price']) ?></strong></td>
                                        <td>
                                            <span class="badge <?= getStatusBadgeClass($order['status']) ?>">
                                                <?= getStatusName($order['status']) ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 0.85rem; color: #666;">
                                            <?= formatDateTime($order['created_at']) ?>
                                        </td>
                                        <td>
                                            <div class="quick-actions">
                                                <button onclick="viewOrder(<?= $order['id'] ?>)" class="btn btn-primary btn-sm">
                                                    Подробнее
                                                </button>
                                                <button onclick="deleteOrder(<?= $order['id'] ?>, '<?= escape($order['customer_name']) ?>')"
                                                        class="btn btn-danger btn-sm">
                                                    Удалить
                                                </button>
                                            </div>
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

    <!-- Модальное окно просмотра заказа -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <h2>Детали заказа #<span id="modal_order_id"></span></h2>

            <div id="modal_content">
                <!-- Содержимое загружается динамически -->
            </div>
        </div>
    </div>

    <!-- Формы для действий -->
    <form method="POST" id="statusForm" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="id" id="status_order_id">
        <input type="hidden" name="status" id="status_value">
    </form>

    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

    <script>
        // Просмотр заказа
        function viewOrder(id) {
            const orders = <?= json_encode($orders, JSON_UNESCAPED_UNICODE) ?>;
            const order = orders.find(o => o.id == id);

            if (!order) {
                alert('Заказ не найден');
                return;
            }

            document.getElementById('modal_order_id').textContent = order.id;

            let html = `
                <div class="order-info-grid">
                    <div class="order-info-item">
                        <div class="order-info-label">Клиент</div>
                        <div class="order-info-value">${escapeHtml(order.customer_name)}</div>
                    </div>
                    <div class="order-info-item">
                        <div class="order-info-label">Телефон</div>
                        <div class="order-info-value">${escapeHtml(order.customer_phone)}</div>
                    </div>
                    ${order.customer_email ? `
                    <div class="order-info-item">
                        <div class="order-info-label">Email</div>
                        <div class="order-info-value">${escapeHtml(order.customer_email)}</div>
                    </div>
                    ` : ''}
                    <div class="order-info-item">
                        <div class="order-info-label">Дата доставки</div>
                        <div class="order-info-value">${formatDate(order.delivery_date)}</div>
                    </div>
                    ${order.delivery_time ? `
                    <div class="order-info-item">
                        <div class="order-info-label">Время доставки</div>
                        <div class="order-info-value">${escapeHtml(order.delivery_time)}</div>
                    </div>
                    ` : ''}
                    ${order.delivery_address ? `
                    <div class="order-info-item" style="grid-column: 1 / -1;">
                        <div class="order-info-label">Адрес доставки</div>
                        <div class="order-info-value">${escapeHtml(order.delivery_address)}</div>
                    </div>
                    ` : ''}
                    <div class="order-info-item">
                        <div class="order-info-label">Сумма заказа</div>
                        <div class="order-info-value" style="color: var(--primary-color); font-size: 1.3rem;">
                            ${formatPrice(order.total_price)}
                        </div>
                    </div>
                    <div class="order-info-item">
                        <div class="order-info-label">Дата создания</div>
                        <div class="order-info-value">${formatDateTime(order.created_at)}</div>
                    </div>
                </div>
            `;

            // Детали заказа (продукт или конструктор)
            if (order.order_type === 'product' && order.product_name) {
                html += `
                    <div class="product-details">
                        <h3>Заказанный товар</h3>
                        ${order.product_image ? `
                            <img src="../${escapeHtml(order.product_image)}"
                                 style="max-width: 200px; border-radius: 8px; margin-bottom: 1rem;"
                                 alt="${escapeHtml(order.product_name)}">
                        ` : ''}
                        <div class="detail-item">
                            <span class="detail-label">Название:</span>
                            ${escapeHtml(order.product_name)}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Цена:</span>
                            ${formatPrice(order.total_price)}
                        </div>
                    </div>
                `;
            } else if (order.order_type === 'cake' && order.order_details) {
                try {
                    const details = JSON.parse(order.order_details);
                    html += `
                        <div class="constructor-details">
                            <h3>Торт-конструктор</h3>
                            ${details.shape ? `<div class="detail-item"><span class="detail-label">Форма:</span> ${escapeHtml(details.shape)}</div>` : ''}
                            ${details.size ? `<div class="detail-item"><span class="detail-label">Размер:</span> ${escapeHtml(details.size)}</div>` : ''}
                            ${details.layers ? `<div class="detail-item"><span class="detail-label">Количество коржей:</span> ${escapeHtml(details.layers)}</div>` : ''}
                            ${details.filling ? `<div class="detail-item"><span class="detail-label">Начинка:</span> ${escapeHtml(details.filling)}</div>` : ''}
                            ${details.cream ? `<div class="detail-item"><span class="detail-label">Крем:</span> ${escapeHtml(details.cream)}</div>` : ''}
                            ${details.decoration ? `<div class="detail-item"><span class="detail-label">Декор:</span> ${escapeHtml(details.decoration)}</div>` : ''}
                            ${details.inscription ? `<div class="detail-item"><span class="detail-label">Надпись:</span> ${escapeHtml(details.inscription)}</div>` : ''}
                            ${details.weight ? `<div class="detail-item"><span class="detail-label">Вес:</span> ${escapeHtml(details.weight)}</div>` : ''}
                        </div>
                    `;
                } catch (e) {
                    html += `<div class="constructor-details"><p>Ошибка парсинга JSON деталей заказа</p></div>`;
                }
            }

            // Комментарий клиента
            if (order.comment) {
                html += `
                    <div style="background: #f9f9f9; padding: 1rem; border-radius: 8px; margin: 1rem 0;">
                        <h4 style="margin-top: 0; color: #666;">Комментарий клиента:</h4>
                        <p style="margin: 0; white-space: pre-line;">${escapeHtml(order.comment)}</p>
                    </div>
                `;
            }

            // Изменение статуса
            html += `
                <div style="margin: 1.5rem 0;">
                    <h3>Изменить статус</h3>
                    <form method="POST" class="status-change-form">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" value="${order.id}">
                        <select name="status" class="form-control" style="max-width: 200px;">
                            <option value="new" ${order.status === 'new' ? 'selected' : ''}>Новый</option>
                            <option value="confirmed" ${order.status === 'confirmed' ? 'selected' : ''}>Подтвержден</option>
                            <option value="in_progress" ${order.status === 'in_progress' ? 'selected' : ''}>В работе</option>
                            <option value="completed" ${order.status === 'completed' ? 'selected' : ''}>Завершен</option>
                            <option value="cancelled" ${order.status === 'cancelled' ? 'selected' : ''}>Отменен</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Обновить статус</button>
                    </form>
                </div>
            `;

            // Заметки администратора
            html += `
                <div class="notes-section">
                    <h3>Заметки администратора</h3>
                    ${order.notes ? `
                        <div class="notes-content">${escapeHtml(order.notes)}</div>
                        <hr style="margin: 1rem 0; border: none; border-top: 1px solid rgba(0,0,0,0.1);">
                    ` : '<p style="color: #999;">Заметок пока нет</p>'}
                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="add_note">
                        <input type="hidden" name="id" value="${order.id}">
                        <textarea name="note" class="form-control" rows="3" placeholder="Добавить заметку..." required></textarea>
                        <button type="submit" class="btn btn-success" style="margin-top: 0.5rem;">Добавить заметку</button>
                    </form>
                </div>
            `;

            document.getElementById('modal_content').innerHTML = html;
            document.getElementById('orderModal').classList.add('active');
        }

        // Закрытие модального окна
        function closeModal() {
            document.getElementById('orderModal').classList.remove('active');
        }

        // Удаление заказа
        function deleteOrder(id, customerName) {
            if (confirm(`Удалить заказ #${id} от ${customerName}?\n\nЭто действие нельзя отменить!`)) {
                document.getElementById('delete_id').value = id;
                document.getElementById('deleteForm').submit();
            }
        }

        // Вспомогательные функции
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('ru-RU').format(price) + ' грн';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU');
        }

        function formatDateTime(dateTimeString) {
            const date = new Date(dateTimeString);
            return date.toLocaleDateString('ru-RU') + ' ' + date.toLocaleTimeString('ru-RU', {hour: '2-digit', minute: '2-digit'});
        }

        // Закрытие модального окна по клику вне его
        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
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
