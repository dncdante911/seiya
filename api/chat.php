<?php
/**
 * API для чата с кондитером
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

session_start();
$db = getDB();

// Получение новых сообщений
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_messages') {
    try {
        if (!isset($_SESSION['chat_session_id'])) {
            jsonResponse(['success' => false, 'error' => 'Сессия не найдена']);
        }

        $session_id = $_SESSION['chat_session_id'];
        $last_message_id = $_GET['last_id'] ?? 0;

        $stmt = $db->prepare("SELECT * FROM chat_messages WHERE session_id = ? AND id > ? ORDER BY created_at ASC");
        $stmt->execute([$session_id, $last_message_id]);
        $messages = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'messages' => $messages
        ]);
    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// Отправка сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_SESSION['chat_session_id'])) {
            jsonResponse(['success' => false, 'error' => 'Сессия не найдена']);
        }

        $session_id = $_SESSION['chat_session_id'];
        $message = trim($_POST['message'] ?? '');
        $image_path = null;

        // Обработка загрузки изображения
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadImage($_FILES['image'], 'uploads/chat/');

            if ($upload_result['success']) {
                $image_path = $upload_result['filepath'];
            } else {
                jsonResponse(['success' => false, 'error' => $upload_result['error']]);
            }
        }

        // Проверяем что есть либо сообщение либо изображение
        if (empty($message) && !$image_path) {
            jsonResponse(['success' => false, 'error' => 'Сообщение или изображение обязательны']);
        }

        // Если только изображение, устанавливаем дефолтное сообщение
        if (empty($message) && $image_path) {
            $message = 'Фото';
        }

        // Сохраняем сообщение
        $stmt = $db->prepare("
            INSERT INTO chat_messages (session_id, sender_type, message, message_type, image_path)
            VALUES (?, 'customer', ?, ?, ?)
        ");

        $message_type = $image_path ? 'image' : 'text';

        $stmt->execute([
            $session_id,
            $message,
            $message_type,
            $image_path
        ]);

        $message_id = $db->lastInsertId();

        // Обновляем время последнего сообщения в сессии
        $stmt = $db->prepare("UPDATE chat_sessions SET updated_at = NOW() WHERE id = ?");
        $stmt->execute([$session_id]);

        jsonResponse([
            'success' => true,
            'message_id' => $message_id
        ]);

    } catch (Exception $e) {
        jsonResponse(['success' => false, 'error' => 'Ошибка сервера: ' . $e->getMessage()], 500);
    }
}

jsonResponse(['success' => false, 'error' => 'Неподдерживаемый метод'], 405);
?>
