<?php
/**
 * WHMCS Webhook Callback Handler
 *
 * Цей файл отримує повідомлення від WHMCS про зміну статусу платежів
 * URL для налаштування в WHMCS: https://seiya.com.ua/api/whmcs_callback.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/whmcs.php';

// Логуємо всі вхідні запити
logWHMCS('Webhook received', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'post' => $_POST,
    'get' => $_GET,
    'headers' => getallheaders()
]);

// Перевірка методу запиту
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    logWHMCS('Invalid request method', ['method' => $_SERVER['REQUEST_METHOD']]);
    die('Method Not Allowed');
}

// Отримуємо дані від WHMCS
$payload = file_get_contents('php://input');
$data = json_decode($payload, true) ?? $_POST;

// Перевірка обов'язкових полів
if (!isset($data['invoiceid']) && !isset($data['invoice_id'])) {
    http_response_code(400);
    logWHMCS('Missing invoice ID', ['data' => $data]);
    die('Bad Request: Missing invoice ID');
}

$invoiceId = $data['invoiceid'] ?? $data['invoice_id'];
$status = $data['status'] ?? '';

logWHMCS('Processing webhook', [
    'invoice_id' => $invoiceId,
    'status' => $status
]);

try {
    $db = getDB();

    // Шукаємо замовлення кондитерки з цим invoice_id
    $stmt = $db->prepare("SELECT * FROM orders_confectionery WHERE whmcs_invoice_id = ?");
    $stmt->execute([$invoiceId]);
    $confectioneryOrder = $stmt->fetch();

    if ($confectioneryOrder) {
        handleConfectioneryOrder($db, $confectioneryOrder, $status, $data);
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Confectionery order updated']);
        exit;
    }

    // Шукаємо замовлення фаершоу з цим invoice_id
    $stmt = $db->prepare("SELECT * FROM orders_fireshow WHERE whmcs_invoice_id = ?");
    $stmt->execute([$invoiceId]);
    $fireshowOrder = $stmt->fetch();

    if ($fireshowOrder) {
        handleFireshowOrder($db, $fireshowOrder, $status, $data);
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Fireshow order updated']);
        exit;
    }

    // Замовлення не знайдено
    logWHMCS('Order not found for invoice', ['invoice_id' => $invoiceId]);
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Order not found']);

} catch (Exception $e) {
    logWHMCS('Exception in webhook handler', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

/**
 * Обробка платежу для замовлення кондитерки
 */
function handleConfectioneryOrder($db, $order, $status, $webhookData) {
    $orderId = $order['id'];
    $newStatus = mapWHMCSStatus($status);

    logWHMCS('Updating confectionery order', [
        'order_id' => $orderId,
        'old_status' => $order['status'],
        'new_status' => $newStatus,
        'whmcs_status' => $status
    ]);

    // Оновлюємо статус замовлення
    $stmt = $db->prepare("
        UPDATE orders_confectionery
        SET
            status = ?,
            payment_status = ?,
            whmcs_data = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $newStatus,
        $status,
        json_encode($webhookData),
        $orderId
    ]);

    // Якщо оплата успішна - відправляємо email
    if ($status === 'Paid' || $status === 'paid') {
        sendConfectioneryOrderEmail($order, 'paid');
    }

    logWHMCS('Confectionery order updated', ['order_id' => $orderId, 'new_status' => $newStatus]);
}

/**
 * Обробка платежу для замовлення фаершоу
 */
function handleFireshowOrder($db, $order, $status, $webhookData) {
    $orderId = $order['id'];
    $newStatus = mapWHMCSStatus($status);

    logWHMCS('Updating fireshow order', [
        'order_id' => $orderId,
        'old_status' => $order['status'],
        'new_status' => $newStatus,
        'whmcs_status' => $status
    ]);

    // Оновлюємо статус замовлення
    $stmt = $db->prepare("
        UPDATE orders_fireshow
        SET
            status = ?,
            payment_status = ?,
            whmcs_data = ?,
            updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $newStatus,
        $status,
        json_encode($webhookData),
        $orderId
    ]);

    // Якщо оплата успішна - відправляємо email
    if ($status === 'Paid' || $status === 'paid') {
        sendFireshowOrderEmail($order, 'paid');
    }

    logWHMCS('Fireshow order updated', ['order_id' => $orderId, 'new_status' => $newStatus]);
}

/**
 * Мапінг статусів WHMCS на статуси сайту
 */
function mapWHMCSStatus($whmcsStatus) {
    $statusMap = [
        'Paid' => 'confirmed',
        'paid' => 'confirmed',
        'Unpaid' => 'pending',
        'unpaid' => 'pending',
        'Cancelled' => 'cancelled',
        'cancelled' => 'cancelled',
        'Refunded' => 'cancelled',
        'refunded' => 'cancelled',
    ];

    return $statusMap[$whmcsStatus] ?? 'pending';
}

/**
 * Відправка email про оплату замовлення кондитерки
 */
function sendConfectioneryOrderEmail($order, $type) {
    $to = $order['customer_email'];
    $subject = "Оплата замовлення №{$order['id']} підтверджена";

    $message = "
    <html>
    <head>
        <title>Оплата підтверджена</title>
    </head>
    <body>
        <h2>Дякуємо за оплату!</h2>
        <p>Ваше замовлення №{$order['id']} успішно оплачено.</p>
        <p><strong>Статус:</strong> Підтверджено</p>
        <p><strong>Сума:</strong> {$order['total_price']} грн</p>
        <p>Ми зв'яжемось з вами найближчим часом для уточнення деталей.</p>
        <br>
        <p>З повагою,<br>Команда Seiya</p>
    </body>
    </html>
    ";

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: noreply@seiya.com.ua',
    ];

    mail($to, $subject, $message, implode("\r\n", $headers));
    logWHMCS('Email sent', ['to' => $to, 'subject' => $subject]);
}

/**
 * Відправка email про оплату замовлення фаершоу
 */
function sendFireshowOrderEmail($order, $type) {
    $to = $order['customer_email'];
    $subject = "Оплата замовлення фаершоу №{$order['id']} підтверджена";

    $message = "
    <html>
    <head>
        <title>Оплата підтверджена</title>
    </head>
    <body>
        <h2>Дякуємо за оплату!</h2>
        <p>Ваше замовлення фаершоу №{$order['id']} успішно оплачено.</p>
        <p><strong>Статус:</strong> Підтверджено</p>
        <p><strong>Сума:</strong> {$order['total_price']} грн</p>
        <p><strong>Дата заходу:</strong> {$order['event_date']} {$order['event_time']}</p>
        <p>Ми зв'яжемось з вами найближчим часом для уточнення деталей виступу.</p>
        <br>
        <p>З повагою,<br>Команда Seiya</p>
    </body>
    </html>
    ";

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: noreply@seiya.com.ua',
    ];

    mail($to, $subject, $message, implode("\r\n", $headers));
    logWHMCS('Email sent', ['to' => $to, 'subject' => $subject]);
}

?>
