<?php
/**
 * WHMCS Integration Configuration
 *
 * ІНСТРУКЦІЯ З НАЛАШТУВАННЯ:
 * 1. Замініть плейсхолдери нижче на реальні дані з вашого WHMCS
 * 2. Перевірте, що IP вашого сервера додано в WHMCS API whitelist
 * 3. Переконайтесь, що SSL (HTTPS) працює на вашому домені
 *
 * Як отримати ці дані дивіться в WHMCS_INTEGRATION.md
 */

// WHMCS API Configuration
define('WHMCS_URL', 'https://ваш-домен.com/whmcs/');  // ЗАМІНІТЬ на ваш WHMCS URL
define('WHMCS_API_IDENTIFIER', 'api-XXXXXXXXXX');      // ЗАМІНІТЬ на ваш API Identifier
define('WHMCS_API_SECRET', 'YYYYYYYYYYYYYYYY');        // ЗАМІНІТЬ на ваш API Secret

// Product IDs from WHMCS
// Отримайте з WHMCS Admin → Setup → Products/Services
define('WHMCS_PRODUCT_CONFECTIONERY', 0);  // ЗАМІНІТЬ на ID продукту кондитерки
define('WHMCS_PRODUCT_FIRESHOW', 0);       // ЗАМІНІТЬ на ID продукту фаершоу

// WHMCS Integration Settings
define('WHMCS_ENABLED', false);  // Встановіть true після налаштування
define('WHMCS_TEST_MODE', true); // Тестовий режим (не створює реальні замовлення)

// Currency
define('WHMCS_CURRENCY', 'UAH');

// Callback/Webhook Settings
define('WHMCS_WEBHOOK_SECRET', bin2hex(random_bytes(32))); // Секрет для валідації webhook

/**
 * Перевірка конфігурації WHMCS
 */
function isWHMCSConfigured() {
    return WHMCS_ENABLED
        && WHMCS_URL !== 'https://ваш-домен.com/whmcs/'
        && WHMCS_API_IDENTIFIER !== 'api-XXXXXXXXXX'
        && WHMCS_API_SECRET !== 'YYYYYYYYYYYYYYYY'
        && WHMCS_PRODUCT_CONFECTIONERY > 0
        && WHMCS_PRODUCT_FIRESHOW > 0;
}

/**
 * Логування WHMCS операцій
 */
function logWHMCS($message, $data = []) {
    $logFile = __DIR__ . '/../logs/whmcs.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = sprintf(
        "[%s] %s | Data: %s\n",
        $timestamp,
        $message,
        json_encode($data, JSON_UNESCAPED_UNICODE)
    );

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

?>
