<?php
/**
 * API для обработки заказов кондитерки
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Метод не поддерживается'], 405);
}

try {
    $db = getDB();

    // Получаем данные из формы
    $order_type = $_POST['order_type'] ?? 'product';
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $delivery_date = $_POST['delivery_date'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    $product_id = $_POST['product_id'] ?? null;
    $constructor_data = $_POST['constructor_data'] ?? null;
    $total_price = $_POST['total_price'] ?? 0;

    // Валидация
    if (empty($customer_name)) {
        jsonResponse(['success' => false, 'error' => 'Укажите ваше имя']);
    }

    if (empty($customer_phone) || !isValidPhone($customer_phone)) {
        jsonResponse(['success' => false, 'error' => 'Укажите корректный номер телефона']);
    }

    if (!empty($customer_email) && !isValidEmail($customer_email)) {
        jsonResponse(['success' => false, 'error' => 'Укажите корректный email']);
    }

    if (empty($delivery_date)) {
        jsonResponse(['success' => false, 'error' => 'Укажите дату доставки']);
    }

    // Проверяем дату
    $date = DateTime::createFromFormat('Y-m-d', $delivery_date);
    if (!$date || $date < new DateTime()) {
        jsonResponse(['success' => false, 'error' => 'Дата доставки должна быть в будущем']);
    }

    // Если заказ товара, проверяем существование товара
    if ($order_type === 'product' && $product_id) {
        $stmt = $db->prepare("SELECT price FROM products WHERE id = ? AND active = 1");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            jsonResponse(['success' => false, 'error' => 'Товар не найден']);
        }

        $total_price = $product['price'];
    }

    // Создаем заказ
    $stmt = $db->prepare("
        INSERT INTO orders_confectionery
        (order_type, customer_name, customer_phone, customer_email, delivery_date,
         product_id, constructor_data, total_price, comment, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')
    ");

    $stmt->execute([
        $order_type,
        $customer_name,
        $customer_phone,
        $customer_email ?: null,
        $delivery_date,
        $product_id,
        $constructor_data,
        $total_price,
        $comment
    ]);

    $order_id = $db->lastInsertId();

    jsonResponse([
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Заказ успешно оформлен'
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
}
?>
