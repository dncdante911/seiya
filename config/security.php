<?php
/**
 * Функции безопасности
 */

// ============= CSRF ЗАЩИТА =============

/**
 * Генерация CSRF токена
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Проверка CSRF токена
 */
function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * HTML input с CSRF токеном
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Получить CSRF токен для JavaScript
 */
function getCSRFToken() {
    return generateCSRFToken();
}

// ============= RATE LIMITING =============

/**
 * Проверка rate limit для логина
 * @param string $identifier - IP адрес или username
 * @param int $maxAttempts - максимальное количество попыток
 * @param int $timeWindow - окно времени в секундах
 * @return bool - true если можно продолжать, false если заблокирован
 */
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 900) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $key = 'rate_limit_' . md5($identifier);
    $now = time();

    // Инициализируем если не существует
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 0,
            'first_attempt' => $now,
            'blocked_until' => 0
        ];
    }

    $data = $_SESSION[$key];

    // Проверяем если заблокирован
    if ($data['blocked_until'] > $now) {
        return false;
    }

    // Сбрасываем если прошло время окна
    if ($now - $data['first_attempt'] > $timeWindow) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'first_attempt' => $now,
            'blocked_until' => 0
        ];
        return true;
    }

    // Увеличиваем счетчик
    $_SESSION[$key]['attempts']++;

    // Блокируем если превышен лимит
    if ($_SESSION[$key]['attempts'] > $maxAttempts) {
        $_SESSION[$key]['blocked_until'] = $now + $timeWindow;
        return false;
    }

    return true;
}

/**
 * Получить оставшееся время блокировки
 */
function getRateLimitTimeout($identifier) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $key = 'rate_limit_' . md5($identifier);

    if (!isset($_SESSION[$key])) {
        return 0;
    }

    $remaining = $_SESSION[$key]['blocked_until'] - time();
    return max(0, $remaining);
}

/**
 * Сброс rate limit (например, при успешном логине)
 */
function resetRateLimit($identifier) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $key = 'rate_limit_' . md5($identifier);
    unset($_SESSION[$key]);
}

// ============= IP И USER AGENT =============

/**
 * Получить IP адрес клиента
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

/**
 * Валидация User-Agent (базовая проверка на боты)
 */
function isValidUserAgent() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Блокируем подозрительные User-Agent
    $blocked = ['sqlmap', 'nikto', 'nmap', 'masscan', 'python-requests'];

    foreach ($blocked as $pattern) {
        if (stripos($userAgent, $pattern) !== false) {
            return false;
        }
    }

    return true;
}

// ============= ЗАЩИТА ОТ XSS =============

/**
 * Очистка HTML от опасных тегов (расширенная версия escape)
 */
function sanitizeHTML($html, $allowedTags = '<p><br><strong><em><u><a>') {
    return strip_tags($html, $allowedTags);
}

/**
 * Защита от XSS в атрибутах
 */
function escapeAttr($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ============= ЗАЩИТА ОТ SQL ИНЪЕКЦИЙ =============

/**
 * Дополнительная валидация для ID (должны быть только числа)
 */
function validateID($id) {
    return filter_var($id, FILTER_VALIDATE_INT) !== false && $id > 0;
}

// ============= ЗАЩИТА СЕССИЙ =============

/**
 * Защита сессий от фиксации и хайджекинга
 */
function secureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Настройки безопасности сессии
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.cookie_samesite', 'Lax');

        session_start();

        // Проверка IP и User-Agent для защиты от хайджекинга
        if (!isset($_SESSION['_secure_check'])) {
            $_SESSION['_secure_check'] = [
                'ip' => getClientIP(),
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
        } else {
            $currentIP = getClientIP();
            $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Если IP или UA изменились - уничтожаем сессию
            if ($_SESSION['_secure_check']['ip'] !== $currentIP ||
                $_SESSION['_secure_check']['ua'] !== $currentUA) {
                session_unset();
                session_destroy();
                session_start();
            }
        }

        // Регенерация ID сессии каждые 30 минут
        if (!isset($_SESSION['_last_regeneration'])) {
            $_SESSION['_last_regeneration'] = time();
        } elseif (time() - $_SESSION['_last_regeneration'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_last_regeneration'] = time();
        }
    }
}

// ============= ЛОГИРОВАНИЕ ПОДОЗРИТЕЛЬНОЙ АКТИВНОСТИ =============

/**
 * Логирование подозрительных попыток
 */
function logSecurityEvent($event, $details = []) {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'details' => $details
    ];

    $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}
?>
