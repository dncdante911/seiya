<?php
/**
 * WHMCS API Client
 *
 * Клас для роботи з WHMCS API
 * Документація: https://developers.whmcs.com/api/
 */

require_once __DIR__ . '/../config/whmcs.php';

class WHMCSClient {
    private $apiUrl;
    private $identifier;
    private $secret;

    public function __construct() {
        $this->apiUrl = rtrim(WHMCS_URL, '/') . '/includes/api.php';
        $this->identifier = WHMCS_API_IDENTIFIER;
        $this->secret = WHMCS_API_SECRET;
    }

    /**
     * Виконати API запит до WHMCS
     */
    private function apiRequest($action, $params = []) {
        if (!isWHMCSConfigured()) {
            logWHMCS('WHMCS не налаштовано', ['action' => $action]);
            return [
                'result' => 'error',
                'message' => 'WHMCS інтеграція не налаштована. Перевірте config/whmcs.php'
            ];
        }

        // Додаємо обов'язкові параметри
        $postData = array_merge([
            'identifier' => $this->identifier,
            'secret' => $this->secret,
            'action' => $action,
            'responsetype' => 'json',
        ], $params);

        // Лог запиту
        logWHMCS("API Request: $action", ['params' => $params]);

        // Якщо тестовий режим - повертаємо mock відповідь
        if (WHMCS_TEST_MODE) {
            return $this->mockResponse($action, $params);
        }

        // Виконуємо реальний запит
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                logWHMCS("CURL Error: $error", ['action' => $action]);
                return ['result' => 'error', 'message' => "CURL Error: $error"];
            }

            curl_close($ch);

            $result = json_decode($response, true);
            logWHMCS("API Response: $action", ['result' => $result]);

            return $result;

        } catch (Exception $e) {
            logWHMCS("Exception: " . $e->getMessage(), ['action' => $action]);
            return ['result' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Mock відповіді для тестового режиму
     */
    private function mockResponse($action, $params) {
        logWHMCS("MOCK Response (TEST MODE): $action", ['params' => $params]);

        switch ($action) {
            case 'AddClient':
                return [
                    'result' => 'success',
                    'clientid' => 999,
                ];

            case 'GetClientsDetails':
                return [
                    'result' => 'success',
                    'client' => [
                        'id' => 999,
                        'firstname' => $params['firstname'] ?? 'Test',
                        'lastname' => $params['lastname'] ?? 'Client',
                        'email' => $params['email'] ?? 'test@example.com',
                    ]
                ];

            case 'AddOrder':
                return [
                    'result' => 'success',
                    'orderid' => 888,
                    'productids' => '123',
                    'invoiceid' => 777,
                ];

            case 'GetInvoice':
                return [
                    'result' => 'success',
                    'invoiceid' => 777,
                    'total' => $params['total'] ?? '1000.00',
                    'status' => 'Unpaid',
                ];

            default:
                return [
                    'result' => 'success',
                    'message' => 'Mock response for ' . $action
                ];
        }
    }

    /**
     * Додати або отримати клієнта
     */
    public function getOrCreateClient($data) {
        // Спочатку шукаємо клієнта за email
        $searchResult = $this->apiRequest('GetClientsDetails', [
            'email' => $data['email'],
            'stats' => false
        ]);

        if ($searchResult['result'] === 'success' && isset($searchResult['client'])) {
            logWHMCS('Клієнт знайдений', ['clientid' => $searchResult['client']['id']]);
            return [
                'success' => true,
                'clientid' => $searchResult['client']['id']
            ];
        }

        // Якщо не знайдено - створюємо нового
        $createResult = $this->apiRequest('AddClient', [
            'firstname' => $data['name'] ?? $data['firstname'] ?? '',
            'lastname' => $data['lastname'] ?? '',
            'email' => $data['email'],
            'phonenumber' => $data['phone'] ?? '',
            'address1' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'country' => 'UA',
            'currency' => WHMCS_CURRENCY,
            'password2' => bin2hex(random_bytes(8)), // Випадковий пароль
        ]);

        if ($createResult['result'] === 'success') {
            logWHMCS('Клієнт створений', ['clientid' => $createResult['clientid']]);
            return [
                'success' => true,
                'clientid' => $createResult['clientid']
            ];
        }

        return [
            'success' => false,
            'message' => $createResult['message'] ?? 'Помилка створення клієнта'
        ];
    }

    /**
     * Створити замовлення кондитерки
     */
    public function createConfectioneryOrder($orderData) {
        // Отримуємо або створюємо клієнта
        $clientResult = $this->getOrCreateClient([
            'name' => $orderData['customer_name'],
            'email' => $orderData['customer_email'],
            'phone' => $orderData['customer_phone'],
        ]);

        if (!$clientResult['success']) {
            return $clientResult;
        }

        $clientId = $clientResult['clientid'];

        // Формуємо опис замовлення
        $description = "Замовлення кондитерки\n";
        if (isset($orderData['product_name'])) {
            $description .= "Товар: {$orderData['product_name']}\n";
        }
        if (isset($orderData['constructor_data'])) {
            $description .= "Торт на замовлення (конструктор)\n";
        }
        if (isset($orderData['delivery_date'])) {
            $description .= "Дата доставки: {$orderData['delivery_date']}\n";
        }
        if (isset($orderData['comment'])) {
            $description .= "Коментар: {$orderData['comment']}\n";
        }

        // Створюємо замовлення
        $result = $this->apiRequest('AddOrder', [
            'clientid' => $clientId,
            'paymentmethod' => 'banktransfer', // Буде змінено при виборі способу оплати
            'pid' => [WHMCS_PRODUCT_CONFECTIONERY],
            'billingcycle' => ['onetime'],
            'customfields' => base64_encode(serialize([
                'Order Details' => $description
            ])),
            'amount' => [$orderData['total_price']],
        ]);

        if ($result['result'] === 'success') {
            logWHMCS('Замовлення кондитерки створено', [
                'orderid' => $result['orderid'],
                'invoiceid' => $result['invoiceid']
            ]);

            return [
                'success' => true,
                'orderid' => $result['orderid'],
                'invoiceid' => $result['invoiceid'],
                'clientid' => $clientId,
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Помилка створення замовлення'
        ];
    }

    /**
     * Створити замовлення фаершоу
     */
    public function createFireshowOrder($orderData) {
        // Отримуємо або створюємо клієнта
        $clientResult = $this->getOrCreateClient([
            'name' => $orderData['customer_name'],
            'email' => $orderData['customer_email'],
            'phone' => $orderData['customer_phone'],
        ]);

        if (!$clientResult['success']) {
            return $clientResult;
        }

        $clientId = $clientResult['clientid'];

        // Формуємо опис замовлення
        $description = "Замовлення фаершоу\n";
        if (isset($orderData['program_name'])) {
            $description .= "Програма: {$orderData['program_name']}\n";
        }
        if (isset($orderData['event_date'])) {
            $description .= "Дата заходу: {$orderData['event_date']}\n";
        }
        if (isset($orderData['event_time'])) {
            $description .= "Час: {$orderData['event_time']}\n";
        }
        if (isset($orderData['venue'])) {
            $description .= "Місце: {$orderData['venue']}\n";
        }
        if (isset($orderData['artists_count'])) {
            $description .= "Кількість артистів: {$orderData['artists_count']}\n";
        }
        if (isset($orderData['duration'])) {
            $description .= "Тривалість: {$orderData['duration']} хв\n";
        }
        if (isset($orderData['special_requirements'])) {
            $description .= "Спеціальні вимоги: {$orderData['special_requirements']}\n";
        }

        // Створюємо замовлення
        $result = $this->apiRequest('AddOrder', [
            'clientid' => $clientId,
            'paymentmethod' => 'banktransfer',
            'pid' => [WHMCS_PRODUCT_FIRESHOW],
            'billingcycle' => ['onetime'],
            'customfields' => base64_encode(serialize([
                'Order Details' => $description
            ])),
            'amount' => [$orderData['total_price']],
        ]);

        if ($result['result'] === 'success') {
            logWHMCS('Замовлення фаершоу створено', [
                'orderid' => $result['orderid'],
                'invoiceid' => $result['invoiceid']
            ]);

            return [
                'success' => true,
                'orderid' => $result['orderid'],
                'invoiceid' => $result['invoiceid'],
                'clientid' => $clientId,
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Помилка створення замовлення'
        ];
    }

    /**
     * Отримати інформацію про рахунок
     */
    public function getInvoice($invoiceId) {
        $result = $this->apiRequest('GetInvoice', [
            'invoiceid' => $invoiceId
        ]);

        if ($result['result'] === 'success') {
            return [
                'success' => true,
                'invoice' => $result
            ];
        }

        return [
            'success' => false,
            'message' => $result['message'] ?? 'Помилка отримання рахунку'
        ];
    }

    /**
     * Отримати URL для оплати рахунку
     */
    public function getInvoicePaymentUrl($invoiceId) {
        $whmcsUrl = rtrim(WHMCS_URL, '/');
        return "$whmcsUrl/viewinvoice.php?id=$invoiceId";
    }
}

?>
