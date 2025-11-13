<?php
/**
 * API для системы чатов (для админ-панели и клиентов)
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Разрешаем AJAX запросы
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$db = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // ============= АДМИН API =============

        // Получить список сессий
        case 'get_sessions':
            $sessions_query = "
                SELECT
                    cs.*,
                    (SELECT COUNT(*) FROM chat_messages WHERE session_id = cs.id AND is_read = 0 AND sender_type = 'customer') as unread_count,
                    (SELECT message FROM chat_messages WHERE session_id = cs.id ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM chat_messages WHERE session_id = cs.id ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM chat_sessions cs
                ORDER BY
                    CASE WHEN cs.status = 'active' THEN 0 ELSE 1 END,
                    last_message_time DESC,
                    cs.updated_at DESC
            ";

            $stmt = $db->query($sessions_query);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            jsonResponse([
                'success' => true,
                'sessions' => $sessions
            ]);
            break;

        // Получить сообщения для сессии (админ)
        case 'get_messages':
            $session_id = (int)($_GET['session_id'] ?? 0);

            if (!$session_id) {
                jsonResponse(['success' => false, 'error' => 'Session ID required'], 400);
            }

            $stmt = $db->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
            $stmt->execute([$session_id]);
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Отмечаем сообщения клиента как прочитанные
            $stmt = $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type = 'customer' AND is_read = 0");
            $stmt->execute([$session_id]);

            jsonResponse([
                'success' => true,
                'messages' => $messages
            ]);
            break;

        // Отправить сообщение от админа
        case 'send_message':
            session_start();
            if (!isAdminLoggedIn()) {
                jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }

            $session_id = (int)($_POST['session_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            $image_file = trim($_POST['image_file'] ?? '');

            if (!$session_id) {
                jsonResponse(['success' => false, 'error' => 'Session ID required'], 400);
            }

            if (!$message && !$image_file) {
                jsonResponse(['success' => false, 'error' => 'Message or image required'], 400);
            }

            // Проверяем, что сессия существует
            $stmt = $db->prepare("SELECT id, status FROM chat_sessions WHERE id = ?");
            $stmt->execute([$session_id]);
            $session = $stmt->fetch();

            if (!$session) {
                jsonResponse(['success' => false, 'error' => 'Session not found'], 404);
            }

            // Если сессия закрыта, автоматически открываем её при ответе админа
            if ($session['status'] === 'closed') {
                $stmt = $db->prepare("UPDATE chat_sessions SET status = 'active', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$session_id]);
            }

            // Определяем тип сообщения
            $message_type = $image_file ? 'image' : 'text';
            $image_path = $image_file ?: null;

            // Вставляем сообщение
            $stmt = $db->prepare("
                INSERT INTO chat_messages (session_id, sender_type, message, message_type, image_path, is_read, created_at)
                VALUES (?, 'admin', ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$session_id, $message, $message_type, $image_path]);

            // Обновляем время последнего обновления сессии и статус
            $stmt = $db->prepare("UPDATE chat_sessions SET status = 'active', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$session_id]);

            jsonResponse([
                'success' => true,
                'message_id' => $db->lastInsertId()
            ]);
            break;

        // Загрузка изображения (админ)
        case 'upload_image':
            session_start();
            if (!isAdminLoggedIn()) {
                jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }

            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                jsonResponse(['success' => false, 'error' => 'No image uploaded'], 400);
            }

            $upload = uploadImage($_FILES['image'], '../uploads/chat/');

            if ($upload['success']) {
                jsonResponse([
                    'success' => true,
                    'filepath' => str_replace('../', '', $upload['filepath']),
                    'filename' => $upload['filename']
                ]);
            } else {
                jsonResponse([
                    'success' => false,
                    'error' => $upload['error']
                ], 400);
            }
            break;

        // Закрыть сессию
        case 'close_session':
            session_start();
            if (!isAdminLoggedIn()) {
                jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }

            $session_id = (int)($_POST['session_id'] ?? 0);

            if (!$session_id) {
                jsonResponse(['success' => false, 'error' => 'Session ID required'], 400);
            }

            $stmt = $db->prepare("UPDATE chat_sessions SET status = 'closed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$session_id]);

            jsonResponse([
                'success' => true,
                'message' => 'Session closed'
            ]);
            break;

        // Повторно открыть сессию
        case 'reopen_session':
            session_start();
            if (!isAdminLoggedIn()) {
                jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }

            $session_id = (int)($_POST['session_id'] ?? 0);

            if (!$session_id) {
                jsonResponse(['success' => false, 'error' => 'Session ID required'], 400);
            }

            $stmt = $db->prepare("UPDATE chat_sessions SET status = 'active', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$session_id]);

            jsonResponse([
                'success' => true,
                'message' => 'Session reopened'
            ]);
            break;

        // Получить статистику непрочитанных сообщений
        case 'get_unread_count':
            session_start();
            if (!isAdminLoggedIn()) {
                jsonResponse(['success' => false, 'error' => 'Unauthorized'], 401);
            }

            $stmt = $db->query("
                SELECT COUNT(*) as count
                FROM chat_messages
                WHERE sender_type = 'customer' AND is_read = 0
            ");
            $result = $stmt->fetch();

            jsonResponse([
                'success' => true,
                'unread_count' => (int)$result['count']
            ]);
            break;

        // ============= КЛИЕНТСКИЙ API =============

        // Создать новую сессию или получить существующую (для клиентов на фронтенде)
        case 'create_session':
            session_start();
            $session_token = $_POST['session_token'] ?? '';
            $customer_name = trim($_POST['customer_name'] ?? '');
            $customer_phone = trim($_POST['customer_phone'] ?? '');
            $customer_email = trim($_POST['customer_email'] ?? '');

            // Если токен уже есть, проверяем существующую сессию
            if ($session_token) {
                $stmt = $db->prepare("SELECT id FROM chat_sessions WHERE session_token = ?");
                $stmt->execute([$session_token]);
                $existing = $stmt->fetch();

                if ($existing) {
                    // Обновляем информацию о клиенте, если она предоставлена
                    if ($customer_name || $customer_phone || $customer_email) {
                        $updates = [];
                        $params = [];

                        if ($customer_name) {
                            $updates[] = "customer_name = ?";
                            $params[] = $customer_name;
                        }
                        if ($customer_phone) {
                            $updates[] = "customer_phone = ?";
                            $params[] = $customer_phone;
                        }
                        if ($customer_email) {
                            $updates[] = "customer_email = ?";
                            $params[] = $customer_email;
                        }

                        if (!empty($updates)) {
                            $params[] = $existing['id'];
                            $stmt = $db->prepare("UPDATE chat_sessions SET " . implode(', ', $updates) . " WHERE id = ?");
                            $stmt->execute($params);
                        }
                    }

                    $_SESSION['chat_session_id'] = $existing['id'];

                    jsonResponse([
                        'success' => true,
                        'session_id' => $existing['id'],
                        'session_token' => $session_token,
                        'message' => 'Existing session'
                    ]);
                }
            }

            // Создаем новую сессию
            $session_token = bin2hex(random_bytes(32));

            $stmt = $db->prepare("
                INSERT INTO chat_sessions (session_token, customer_name, customer_phone, customer_email, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'active', NOW(), NOW())
            ");
            $stmt->execute([$session_token, $customer_name, $customer_phone, $customer_email]);

            $new_session_id = $db->lastInsertId();
            $_SESSION['chat_session_id'] = $new_session_id;

            jsonResponse([
                'success' => true,
                'session_id' => $new_session_id,
                'session_token' => $session_token,
                'message' => 'New session created'
            ]);
            break;

        // Отправить сообщение от клиента (для фронтенда)
        case 'send_customer_message':
            session_start();
            $session_token = $_POST['session_token'] ?? '';
            $message = trim($_POST['message'] ?? '');
            $image_path = null;

            if (!$session_token) {
                jsonResponse(['success' => false, 'error' => 'Session token required'], 400);
            }

            // Получаем сессию по токену
            $stmt = $db->prepare("SELECT id, status FROM chat_sessions WHERE session_token = ?");
            $stmt->execute([$session_token]);
            $session = $stmt->fetch();

            if (!$session) {
                jsonResponse(['success' => false, 'error' => 'Session not found'], 404);
            }

            // Обработка загрузки изображения
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadImage($_FILES['image'], '../uploads/chat/');

                if ($upload_result['success']) {
                    $image_path = str_replace('../', '', $upload_result['filepath']);
                } else {
                    jsonResponse(['success' => false, 'error' => $upload_result['error']]);
                }
            }

            // Проверяем что есть либо сообщение либо изображение
            if (empty($message) && !$image_path) {
                jsonResponse(['success' => false, 'error' => 'Message or image required'], 400);
            }

            // Если только изображение, устанавливаем дефолтное сообщение
            if (empty($message) && $image_path) {
                $message = 'Фото';
            }

            $message_type = $image_path ? 'image' : 'text';

            // Вставляем сообщение
            $stmt = $db->prepare("
                INSERT INTO chat_messages (session_id, sender_type, message, message_type, image_path, is_read, created_at)
                VALUES (?, 'customer', ?, ?, ?, 0, NOW())
            ");
            $stmt->execute([$session['id'], $message, $message_type, $image_path]);

            // Обновляем время последнего обновления сессии
            $stmt = $db->prepare("UPDATE chat_sessions SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$session['id']]);

            jsonResponse([
                'success' => true,
                'message_id' => $db->lastInsertId()
            ]);
            break;

        // Получить сообщения для клиента
        case 'get_customer_messages':
            session_start();
            $session_token = $_GET['session_token'] ?? '';
            $last_id = (int)($_GET['last_id'] ?? 0);

            if (!$session_token) {
                jsonResponse(['success' => false, 'error' => 'Session token required'], 400);
            }

            // Получаем сессию по токену
            $stmt = $db->prepare("SELECT id FROM chat_sessions WHERE session_token = ?");
            $stmt->execute([$session_token]);
            $session = $stmt->fetch();

            if (!$session) {
                jsonResponse(['success' => false, 'error' => 'Session not found'], 404);
            }

            // Получаем сообщения (все или только новые после last_id)
            if ($last_id > 0) {
                $stmt = $db->prepare("SELECT * FROM chat_messages WHERE session_id = ? AND id > ? ORDER BY created_at ASC");
                $stmt->execute([$session['id'], $last_id]);
            } else {
                $stmt = $db->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
                $stmt->execute([$session['id']]);
            }

            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Отмечаем сообщения админа как прочитанные
            $stmt = $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type = 'admin' AND is_read = 0");
            $stmt->execute([$session['id']]);

            jsonResponse([
                'success' => true,
                'messages' => $messages
            ]);
            break;

        // Установить имя клиента
        case 'set_name':
            session_start();
            $name = trim($_POST['name'] ?? '');

            if (!$name) {
                jsonResponse(['success' => false, 'error' => 'Name required'], 400);
            }

            // Если сессии нет, создаем новую
            if (!isset($_SESSION['chat_session_id'])) {
                $session_token = bin2hex(random_bytes(32));
                $_SESSION['chat_session_token'] = $session_token;

                $stmt = $db->prepare("INSERT INTO chat_sessions (session_token, customer_name) VALUES (?, ?)");
                $stmt->execute([$session_token, $name]);
                $_SESSION['chat_session_id'] = $db->lastInsertId();

                jsonResponse(['success' => true, 'message' => 'Session created and name set']);
                break;
            }

            $session_id = $_SESSION['chat_session_id'];

            // Обновляем имя в существующей сессии
            $stmt = $db->prepare("UPDATE chat_sessions SET customer_name = ? WHERE id = ?");
            $stmt->execute([$name, $session_id]);

            jsonResponse([
                'success' => true,
                'message' => 'Name updated'
            ]);
            break;

        // Очистить сессию при закрытии страницы
        case 'clear_session':
            session_start();

            if (isset($_SESSION['chat_session_id'])) {
                $session_id = $_SESSION['chat_session_id'];

                // Помечаем сессию как неактивную
                $stmt = $db->prepare("UPDATE chat_sessions SET status = 'closed', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$session_id]);

                // Очищаем PHP сессию
                unset($_SESSION['chat_session_id']);
                unset($_SESSION['chat_session_token']);
            }

            jsonResponse([
                'success' => true,
                'message' => 'Session cleared'
            ]);
            break;

        default:
            // Для POST запросов без action - отправка сообщения от клиента (старый API)
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$action) {
                session_start();

                if (!isset($_SESSION['chat_session_id'])) {
                    jsonResponse(['success' => false, 'error' => 'No active session'], 400);
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
                    jsonResponse(['success' => false, 'error' => 'Message or image required'], 400);
                }

                // Если только изображение, устанавливаем дефолтное сообщение
                if (empty($message) && $image_path) {
                    $message = 'Фото';
                }

                $message_type = $image_path ? 'image' : 'text';

                // Вставляем сообщение
                $stmt = $db->prepare("
                    INSERT INTO chat_messages (session_id, sender_type, message, message_type, image_path, is_read, created_at)
                    VALUES (?, 'customer', ?, ?, ?, 0, NOW())
                ");
                $stmt->execute([$session_id, $message, $message_type, $image_path]);

                // Обновляем время последнего обновления сессии
                $stmt = $db->prepare("UPDATE chat_sessions SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$session_id]);

                jsonResponse([
                    'success' => true,
                    'message_id' => $db->lastInsertId()
                ]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
            }
    }
} catch (Exception $e) {
    error_log('Chat API Error: ' . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ], 500);
}
