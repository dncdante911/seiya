<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();

// Статистика
$stats = [
    'confectionery_orders' => $db->query("SELECT COUNT(*) FROM orders_confectionery WHERE status = 'new'")->fetchColumn(),
    'fireshow_orders' => $db->query("SELECT COUNT(*) FROM orders_fireshow WHERE status = 'new'")->fetchColumn(),
    'products' => $db->query("SELECT COUNT(*) FROM products WHERE active = 1")->fetchColumn(),
    'chat_sessions' => $db->query("SELECT COUNT(*) FROM chat_sessions WHERE status = 'active'")->fetchColumn(),
];

// Последние заказы
$recent_orders_conf = $db->query("SELECT * FROM orders_confectionery ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_orders_fire = $db->query("SELECT * FROM orders_fireshow ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header">
                <h1>Главная панель</h1>
                <div class="admin-user">
                    👤 <?= escape($_SESSION['admin_username']) ?>
                    <a href="logout.php" class="btn btn-danger" style="margin-left: 1rem;">Выход</a>
                </div>
            </header>

            <div class="container">
                <!-- Статистика -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">🎂</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= $stats['confectionery_orders'] ?></div>
                            <div class="stat-label">Новые заказы (кондитерка)</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">🔥</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= $stats['fireshow_orders'] ?></div>
                            <div class="stat-label">Новые заказы (фаершоу)</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">📦</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= $stats['products'] ?></div>
                            <div class="stat-label">Активные товары</div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon">💬</div>
                        <div class="stat-info">
                            <div class="stat-value"><?= $stats['chat_sessions'] ?></div>
                            <div class="stat-label">Активные чаты</div>
                        </div>
                    </div>
                </div>

                <!-- Последние заказы кондитерки -->
                <div class="section-box">
                    <h2>Последние заказы (Кондитерка)</h2>
                    <?php if (empty($recent_orders_conf)): ?>
                        <p>Заказов пока нет</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Клиент</th>
                                    <th>Телефон</th>
                                    <th>Дата доставки</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders_conf as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td><?= escape($order['customer_name']) ?></td>
                                        <td><?= escape($order['customer_phone']) ?></td>
                                        <td><?= formatDate($order['delivery_date']) ?></td>
                                        <td><?= formatPrice($order['total_price']) ?></td>
                                        <td><span class="badge badge-<?= $order['status'] ?>"><?= $order['status'] ?></span></td>
                                        <td>
                                            <a href="orders_confectionery.php?id=<?= $order['id'] ?>" class="btn btn-primary">Просмотр</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Последние заказы фаершоу -->
                <div class="section-box">
                    <h2>Последние заказы (Фаершоу)</h2>
                    <?php if (empty($recent_orders_fire)): ?>
                        <p>Заказов пока нет</p>
                    <?php else: ?>
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Клиент</th>
                                    <th>Телефон</th>
                                    <th>Дата мероприятия</th>
                                    <th>Артистов</th>
                                    <th>Сумма</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders_fire as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td><?= escape($order['customer_name']) ?></td>
                                        <td><?= escape($order['customer_phone']) ?></td>
                                        <td><?= formatDate($order['event_date']) ?></td>
                                        <td><?= $order['num_artists'] ?></td>
                                        <td><?= formatPrice($order['total_price']) ?></td>
                                        <td><span class="badge badge-<?= $order['status'] ?>"><?= $order['status'] ?></span></td>
                                        <td>
                                            <a href="orders_fireshow.php?id=<?= $order['id'] ?>" class="btn btn-primary">Просмотр</a>
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
</body>
</html>
