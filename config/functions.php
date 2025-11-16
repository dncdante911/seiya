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
    // Разрешены все популярные видео форматы
    $allowedTypes = [
        'video/mp4',
        'video/mpeg',
        'video/quicktime',      // MOV
        'video/x-msvideo',      // AVI
        'video/x-ms-wmv',       // WMV
        'video/x-flv',          // FLV
        'video/webm',
        'video/ogg',
        'video/x-matroska',     // MKV
        'video/3gpp',           // 3GP
        'video/x-m4v',          // M4V
        'application/octet-stream' // Для некоторых форматов которые не распознаются
    ];
    $maxSize = 2048 * 1024 * 1024; // 2GB

    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Неверный параметр'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'Файл превышает максимальный размер, разрешенный в php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Файл превышает максимальный размер',
            UPLOAD_ERR_PARTIAL => 'Файл был загружен только частично',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
            UPLOAD_ERR_EXTENSION => 'Загрузка файла остановлена расширением'
        ];
        return ['success' => false, 'error' => $errorMessages[$file['error']] ?? 'Ошибка загрузки файла'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Файл слишком большой (макс. 2GB)'];
    }

    // Проверяем расширение файла
    $originalName = $file['name'];
    $pathInfo = pathinfo($originalName);
    $fileExtension = strtolower($pathInfo['extension'] ?? '');

    $allowedExtensions = ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'ogg', 'ogv', 'mkv', '3gp', 'm4v', 'mpeg', 'mpg'];

    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['success' => false, 'error' => 'Недопустимый формат видео. Разрешены: ' . implode(', ', $allowedExtensions)];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    // Проверяем MIME type (более мягкая проверка из-за разнообразия форматов)
    if (!in_array($mimeType, $allowedTypes) && !str_starts_with($mimeType, 'video/')) {
        return ['success' => false, 'error' => 'Недопустимый тип файла'];
    }

    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }

    // Сохраняем временный файл
    $tempFilename = uniqid() . '_temp_' . time() . '.' . $fileExtension;
    $tempFilepath = $directory . $tempFilename;

    if (!move_uploaded_file($file['tmp_name'], $tempFilepath)) {
        return ['success' => false, 'error' => 'Не удалось сохранить файл'];
    }

    // Проверяем наличие FFmpeg для конвертации
    $ffmpegPath = exec('which ffmpeg');
    $needsConversion = in_array($fileExtension, ['mkv', 'avi', 'mov', 'wmv', 'flv', 'mpeg', 'mpg', '3gp']);

    if ($ffmpegPath && $needsConversion) {
        // FFmpeg доступен - конвертируем в MP4 H.264
        $outputFilename = uniqid() . '_' . time() . '.mp4';
        $outputFilepath = $directory . $outputFilename;

        // FFmpeg команда для конвертации в MP4 с H.264 кодеком
        $command = escapeshellcmd($ffmpegPath) . ' -i ' . escapeshellarg($tempFilepath) .
                   ' -c:v libx264 -preset medium -crf 23 -c:a aac -b:a 128k -movflags +faststart -y ' .
                   escapeshellarg($outputFilepath) . ' 2>&1';

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($outputFilepath)) {
            // Конвертация успешна - удаляем временный файл
            unlink($tempFilepath);
            return [
                'success' => true,
                'filepath' => $outputFilepath,
                'filename' => $outputFilename,
                'converted' => true,
                'original_format' => $fileExtension
            ];
        } else {
            // Конвертация не удалась - используем оригинальный файл
            $finalFilename = uniqid() . '_' . time() . '.' . $fileExtension;
            $finalFilepath = $directory . $finalFilename;
            rename($tempFilepath, $finalFilepath);

            return [
                'success' => true,
                'filepath' => $finalFilepath,
                'filename' => $finalFilename,
                'converted' => false,
                'warning' => 'Конвертация не удалась, сохранен оригинальный формат'
            ];
        }
    } else {
        // FFmpeg недоступен или формат не требует конвертации
        $finalFilename = uniqid() . '_' . time() . '.' . $fileExtension;
        $finalFilepath = $directory . $finalFilename;
        rename($tempFilepath, $finalFilepath);

        $message = !$ffmpegPath && $needsConversion ?
            'FFmpeg не установлен. Рекомендуется установить для лучшей совместимости с браузерами.' : null;

        return [
            'success' => true,
            'filepath' => $finalFilepath,
            'filename' => $finalFilename,
            'converted' => false,
            'warning' => $message
        ];
    }
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
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
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
