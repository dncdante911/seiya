<?php
/**
 * Установка языка
 */
require_once __DIR__ . '/config/language.php';

$lang = $_GET['lang'] ?? '';
$redirect = $_GET['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '/';

if (setLanguage($lang)) {
    // Язык успешно установлен
    header("Location: $redirect");
    exit;
} else {
    // Неверный язык
    header("Location: $redirect");
    exit;
}
?>
