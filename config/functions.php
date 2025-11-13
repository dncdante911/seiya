<?php
/**
 * Общие функции и хелперы
 */

// Защита от XSS
function escape($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Безопасный вывод
function e($string) {
    echo escape($string);
}

// Генерация slug из строки
function generateSlug($string) {
    $string = mb_strtolower($string, 'UTF-8');

    $replace = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    ];

    $string = strtr($string, $replace);
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    $string = trim($string, '-');

    return $string;
}

// Форматирование цены
function formatPrice($price) {
    return number_format($price, 0, '.', ' ') . ' грн';
}

// Валидация email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Валидация телефона
function isValidPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10;
}

// Загрузка изображения
function uploadImage($file, $directory = 'uploads/') {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Неверный параметр'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Ошибка загрузки файла'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Файл слишком большой (макс. 5MB)'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Недопустимый тип файла'];
    }

    $extension = match($mimeType) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => 'jpg'
    };

    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $directory . $filename;

    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Не удалось сохранить файл'];
    }

    return ['success' => true, 'filepath' => $filepath, 'filename' => $filename];
}

// Загрузка видео
function uploadVideo($file, $directory = 'uploads/videos/') {
    $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg'];
    $maxSize = 50 * 1024 * 1024; // 50MB

    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Неверный параметр'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Ошибка загрузки файла'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Файл слишком большой (макс. 50MB)'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Недопустимый тип файла'];
    }

    $extension = match($mimeType) {
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogg',
        default => 'mp4'
    };

    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filepath = $directory . $filename;

    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'error' => 'Не удалось сохранить файл'];
    }

    return ['success' => true, 'filepath' => $filepath, 'filename' => $filename];
}

// Получение настроек сайта
function getSetting($key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT value FROM settings WHERE key_name = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();

    return $result ? $result['value'] : $default;
}

// Обновление настройки
function updateSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?)
                          ON DUPLICATE KEY UPDATE value = ?");
    return $stmt->execute([$key, $value, $value]);
}

// Проверка авторизации админа
function isAdminLoggedIn() {
    session_start();
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

// Получение ID текущего админа
function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

// Редирект
function redirect($url) {
    header("Location: $url");
    exit;
}

// JSON ответ
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Форматирование даты
function formatDate($date, $format = 'd.m.Y') {
    return date($format, strtotime($date));
}

// Форматирование даты и времени
function formatDateTime($datetime, $format = 'd.m.Y H:i') {
    return date($format, strtotime($datetime));
}
?>
