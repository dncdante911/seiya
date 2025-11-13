<?php
/**
 * Система локализации сайта
 */

// Инициализация сессии если не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Доступные языки
define('AVAILABLE_LANGUAGES', ['uk', 'ru']);
define('DEFAULT_LANGUAGE', 'uk');

/**
 * Получить текущий язык
 */
function getCurrentLanguage() {
    // Проверяем сессию
    if (isset($_SESSION['language']) && in_array($_SESSION['language'], AVAILABLE_LANGUAGES)) {
        return $_SESSION['language'];
    }

    // Проверяем cookie
    if (isset($_COOKIE['language']) && in_array($_COOKIE['language'], AVAILABLE_LANGUAGES)) {
        $_SESSION['language'] = $_COOKIE['language'];
        return $_COOKIE['language'];
    }

    // Возвращаем язык по умолчанию
    return DEFAULT_LANGUAGE;
}

/**
 * Установить язык
 */
function setLanguage($lang) {
    if (!in_array($lang, AVAILABLE_LANGUAGES)) {
        return false;
    }

    $_SESSION['language'] = $lang;

    // Сохраняем в cookie на год
    setcookie('language', $lang, time() + 31536000, '/');

    return true;
}

/**
 * Загрузить языковой файл
 */
function loadLanguage($lang = null) {
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }

    $lang_file = __DIR__ . "/../lang/{$lang}.php";

    if (file_exists($lang_file)) {
        return include $lang_file;
    }

    // Если файл не найден, загружаем дефолтный
    return include __DIR__ . "/../lang/" . DEFAULT_LANGUAGE . ".php";
}

// Загружаем переводы для текущего языка
$GLOBALS['translations'] = loadLanguage();

/**
 * Получить перевод
 * @param string $key - ключ перевода
 * @param array $params - параметры для подстановки
 * @return string
 */
function t($key, $params = []) {
    $translation = $GLOBALS['translations'][$key] ?? $key;

    // Подстановка параметров
    if (!empty($params)) {
        foreach ($params as $param_key => $param_value) {
            $translation = str_replace("{{$param_key}}", $param_value, $translation);
        }
    }

    return $translation;
}

/**
 * Короткий алиас для t()
 */
function __($key, $params = []) {
    return t($key, $params);
}

/**
 * Получить название языка
 */
function getLanguageName($lang) {
    $names = [
        'uk' => 'Українська',
        'ru' => 'Русский'
    ];
    return $names[$lang] ?? $lang;
}

/**
 * Получить флаг языка (emoji)
 */
function getLanguageFlag($lang) {
    $flags = [
        'uk' => '🇺🇦',
        'ru' => '🇷🇺'
    ];
    return $flags[$lang] ?? '';
}
?>
