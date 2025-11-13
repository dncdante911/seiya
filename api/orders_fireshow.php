<?php
/**
 * API для обработки заказов фаершоу
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/whmcs.php';
require_once __DIR__ . '/../includes/whmcs_client.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Метод не поддерживается'], 405);
}

try {
    $db = getDB();

    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $event_type = $_POST['event_type'] ?? '';
    $event_location = trim($_POST['event_location'] ?? '');
    $program_id = $_POST['program_id'] ?? null;
    $num_artists = $_POST['num_artists'] ?? 1;
    $total_price = $_POST['total_price'] ?? 0;
    $comment = trim($_POST['comment'] ?? '');

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

    if (empty($event_date)) {
        jsonResponse(['success' => false, 'error' => 'Укажите дату мероприятия']);
    }

    if (empty($event_location)) {
        jsonResponse(['success' => false, 'error' => 'Укажите место проведения']);
    }

    // Проверяем дату
    $date = DateTime::createFromFormat('Y-m-d', $event_date);
    if (!$date || $date < new DateTime()) {
        jsonResponse(['success' => false, 'error' => 'Дата мероприятия должна быть в будущем']);
    }

    // Если указана программа, проверяем её
    if ($program_id) {
        $stmt = $db->prepare("SELECT * FROM fireshow_programs WHERE id = ? AND active = 1");
        $stmt->execute([$program_id]);
        $program = $stmt->fetch();

        if (!$program) {
            jsonResponse(['success' => false, 'error' => 'Программа не найдена']);
        }

        // Проверяем количество артистов
        if ($num_artists < $program['min_artists'] || $num_artists > $program['max_artists']) {
            jsonResponse(['success' => false, 'error' => "Количество артистов должно быть от {$program['min_artists']} до {$program['max_artists']}"]);
        }

        $total_price = $program['price_per_artist'] * $num_artists;
    }

    // Создаем заказ в локальной БД
    $stmt = $db->prepare("
        INSERT INTO orders_fireshow
        (customer_name, customer_phone, customer_email, event_date, event_time,
         event_type, event_location, program_id, num_artists, total_price, comment, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')
    ");

    $stmt->execute([
        $customer_name,
        $customer_phone,
        $customer_email ?: null,
        $event_date,
        $event_time ?: null,
        $event_type ?: null,
        $event_location,
        $program_id,
        $num_artists,
        $total_price,
        $comment
    ]);

    $order_id = $db->lastInsertId();

    // Если WHMCS включен - создаем заказ там
    $paymentUrl = null;
    if (isWHMCSConfigured()) {
        try {
            $whmcs = new WHMCSClient();

            // Получаем название программы для описания
            $program_name = '';
            $duration = null;
            if ($program_id && isset($program)) {
                $program_name = $program['name'];
                $duration = $program['duration'];
            }

            $whmcsResult = $whmcs->createFireshowOrder([
                'customer_name' => $customer_name,
                'customer_email' => $customer_email ?: $customer_name . '@example.com',
                'customer_phone' => $customer_phone,
                'program_name' => $program_name,
                'event_date' => $event_date,
                'event_time' => $event_time,
                'venue' => $event_location,
                'artists_count' => $num_artists,
                'duration' => $duration,
                'special_requirements' => $comment,
                'total_price' => $total_price,
            ]);

            if ($whmcsResult['success']) {
                // Обновляем заказ с данными WHMCS
                $stmt = $db->prepare("
                    UPDATE orders_fireshow
                    SET whmcs_client_id = ?,
                        whmcs_order_id = ?,
                        whmcs_invoice_id = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $whmcsResult['clientid'],
                    $whmcsResult['orderid'],
                    $whmcsResult['invoiceid'],
                    $order_id
                ]);

                // Получаем URL для оплаты
                $paymentUrl = $whmcs->getInvoicePaymentUrl($whmcsResult['invoiceid']);

                logWHMCS('Замовлення фаершоу створено успішно', [
                    'order_id' => $order_id,
                    'whmcs_order_id' => $whmcsResult['orderid'],
                    'invoice_id' => $whmcsResult['invoiceid']
                ]);
            }
        } catch (Exception $e) {
            // Логируем ошибку, но заказ уже создан в локальной БД
            logWHMCS('Помилка створення замовлення фаершоу в WHMCS', [
                'order_id' => $order_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    $response = [
        'success' => true,
        'order_id' => $order_id,
        'message' => 'Заказ успешно оформлен'
    ];

    if ($paymentUrl) {
        $response['payment_url'] = $paymentUrl;
        $response['message'] = 'Заказ успешно оформлен. Перейдите к оплате.';
    }

    jsonResponse($response);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
}
?>
