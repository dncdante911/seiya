<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$success = '';
$error = '';

// Получаем выбранный чат из параметра
$selected_session_id = isset($_GET['session']) ? (int)$_GET['session'] : null;

// Получаем все сессии чата
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
$sessions = $db->query($sessions_query)->fetchAll();

// Если не выбран чат, берем первый активный
if (!$selected_session_id && !empty($sessions)) {
    $selected_session_id = $sessions[0]['id'];
}

// Получаем сообщения выбранного чата
$messages = [];
if ($selected_session_id) {
    $stmt = $db->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
    $stmt->execute([$selected_session_id]);
    $messages = $stmt->fetchAll();

    // Отмечаем сообщения клиента как прочитанные
    $stmt = $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender_type = 'customer' AND is_read = 0");
    $stmt->execute([$selected_session_id]);
}

// Получаем информацию о выбранной сессии
$selected_session = null;
if ($selected_session_id) {
    $stmt = $db->prepare("SELECT * FROM chat_sessions WHERE id = ?");
    $stmt->execute([$selected_session_id]);
    $selected_session = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чаты - Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .chat-container {
            display: flex;
            height: calc(100vh - 120px);
            gap: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Левая панель - список чатов */
        .chat-sessions-panel {
            width: 350px;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            background: #fafafa;
        }

        .sessions-header {
            padding: 1.5rem;
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .sessions-list {
            flex: 1;
            overflow-y: auto;
        }

        .session-item {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: background 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: block;
            position: relative;
        }

        .session-item:hover {
            background: #f0f0f0;
        }

        .session-item.active {
            background: white;
            border-left: 4px solid var(--primary-color);
        }

        .session-item.closed {
            opacity: 0.6;
        }

        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .session-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: #333;
        }

        .session-time {
            font-size: 0.75rem;
            color: #999;
        }

        .session-preview {
            font-size: 0.85rem;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            margin-bottom: 0.5rem;
        }

        .session-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .session-status {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.8rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }

        .status-dot.active {
            background: #4caf50;
        }

        .status-dot.closed {
            background: #999;
        }

        .unread-badge {
            background: var(--primary-color);
            color: white;
            border-radius: 12px;
            padding: 0.2rem 0.6rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Правая панель - сообщения */
        .chat-messages-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }

        .messages-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e0e0e0;
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-info h2 {
            margin: 0 0 0.3rem 0;
            font-size: 1.2rem;
            color: #333;
        }

        .chat-meta {
            font-size: 0.85rem;
            color: #666;
        }

        .chat-actions {
            display: flex;
            gap: 0.5rem;
        }

        .messages-container {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            background: #f9f9f9;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            display: flex;
            gap: 0.75rem;
            max-width: 70%;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.customer {
            align-self: flex-start;
        }

        .message.admin {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .message.admin .message-avatar {
            background: #4caf50;
        }

        .message-content {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .message-bubble {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            background: white;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            word-wrap: break-word;
        }

        .message.admin .message-bubble {
            background: var(--primary-color);
            color: white;
        }

        .message-image {
            max-width: 300px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .message-image:hover {
            transform: scale(1.02);
        }

        .message-time {
            font-size: 0.7rem;
            color: #999;
            padding: 0 0.5rem;
        }

        .chat-input-container {
            padding: 1.5rem;
            border-top: 1px solid #e0e0e0;
            background: white;
        }

        .chat-input-form {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 24px;
            font-size: 0.95rem;
            resize: none;
            min-height: 44px;
            max-height: 120px;
            transition: border-color 0.3s ease;
        }

        .chat-input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .input-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: none;
            background: #f0f0f0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
        }

        .btn-icon:hover {
            background: #e0e0e0;
            transform: scale(1.05);
        }

        .btn-send {
            background: var(--primary-color);
            color: white;
        }

        .btn-send:hover {
            background: #d81b60;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
            text-align: center;
            padding: 2rem;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .typing-indicator {
            display: none;
            padding: 0.5rem 1rem;
            color: #666;
            font-size: 0.85rem;
            font-style: italic;
        }

        .typing-indicator.active {
            display: block;
        }

        /* Модальное окно для изображения */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }

        .image-modal.active {
            display: flex;
        }

        .image-modal img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 8px;
        }

        .image-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: white;
            color: #333;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                height: auto;
            }

            .chat-sessions-panel {
                width: 100%;
                max-height: 300px;
            }

            .message {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header">
                <h1>Чаты с клиентами</h1>
                <div class="admin-user">
                    <?= escape($_SESSION['admin_username']) ?>
                    <a href="logout.php" class="btn btn-danger" style="margin-left: 1rem;">Выход</a>
                </div>
            </header>

            <div class="container">
                <div class="chat-container">
                    <!-- Левая панель - список чатов -->
                    <div class="chat-sessions-panel">
                        <div class="sessions-header">
                            Активные чаты (<?= count(array_filter($sessions, fn($s) => $s['status'] === 'active')) ?>)
                        </div>

                        <div class="sessions-list" id="sessionsList">
                            <?php if (empty($sessions)): ?>
                                <div class="empty-state" style="padding: 2rem;">
                                    <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
                                    <p>Нет чатов</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($sessions as $session): ?>
                                    <a href="?session=<?= $session['id'] ?>"
                                       class="session-item <?= $session['id'] == $selected_session_id ? 'active' : '' ?> <?= $session['status'] === 'closed' ? 'closed' : '' ?>"
                                       data-session-id="<?= $session['id'] ?>">
                                        <div class="session-header">
                                            <span class="session-name">
                                                <?= escape($session['customer_name'] ?: 'Гость') ?>
                                            </span>
                                            <span class="session-time">
                                                <?= $session['last_message_time'] ? formatDateTime($session['last_message_time'], 'H:i') : formatDateTime($session['created_at'], 'H:i') ?>
                                            </span>
                                        </div>

                                        <?php if ($session['last_message']): ?>
                                            <div class="session-preview">
                                                <?= escape(mb_substr($session['last_message'], 0, 50)) ?><?= mb_strlen($session['last_message']) > 50 ? '...' : '' ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="session-footer">
                                            <span class="session-status">
                                                <span class="status-dot <?= $session['status'] ?>"></span>
                                                <?= $session['status'] === 'active' ? 'Активен' : 'Закрыт' ?>
                                            </span>

                                            <?php if ($session['unread_count'] > 0): ?>
                                                <span class="unread-badge"><?= $session['unread_count'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Правая панель - сообщения -->
                    <div class="chat-messages-panel">
                        <?php if ($selected_session): ?>
                            <div class="messages-header">
                                <div class="chat-info">
                                    <h2><?= escape($selected_session['customer_name'] ?: 'Гость') ?></h2>
                                    <div class="chat-meta">
                                        <?php if ($selected_session['customer_phone']): ?>
                                            📱 <?= escape($selected_session['customer_phone']) ?>
                                        <?php endif; ?>
                                        <?php if ($selected_session['customer_email']): ?>
                                            • 📧 <?= escape($selected_session['customer_email']) ?>
                                        <?php endif; ?>
                                        • Начат: <?= formatDateTime($selected_session['created_at']) ?>
                                    </div>
                                </div>

                                <div class="chat-actions">
                                    <?php if ($selected_session['status'] === 'active'): ?>
                                        <button onclick="closeChat(<?= $selected_session['id'] ?>)" class="btn btn-secondary btn-sm">
                                            Закрыть чат
                                        </button>
                                    <?php else: ?>
                                        <button onclick="reopenChat(<?= $selected_session['id'] ?>)" class="btn btn-success btn-sm">
                                            Открыть снова
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="messages-container" id="messagesContainer">
                                <?php if (empty($messages)): ?>
                                    <div class="empty-state">
                                        <div class="empty-state-icon">💭</div>
                                        <p>Пока нет сообщений</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($messages as $msg): ?>
                                        <div class="message <?= $msg['sender_type'] ?>" data-message-id="<?= $msg['id'] ?>">
                                            <div class="message-avatar">
                                                <?= $msg['sender_type'] === 'admin' ? '👤' : '👨' ?>
                                            </div>
                                            <div class="message-content">
                                                <?php if ($msg['message_type'] === 'text'): ?>
                                                    <div class="message-bubble">
                                                        <?= nl2br(escape($msg['message'])) ?>
                                                    </div>
                                                <?php elseif ($msg['message_type'] === 'image' && $msg['image_path']): ?>
                                                    <img src="../<?= escape($msg['image_path']) ?>"
                                                         alt="Image"
                                                         class="message-image"
                                                         onclick="openImageModal(this.src)">
                                                    <?php if ($msg['message']): ?>
                                                        <div class="message-bubble">
                                                            <?= nl2br(escape($msg['message'])) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <div class="message-time">
                                                    <?= formatDateTime($msg['created_at'], 'd.m.Y H:i') ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <?php if ($selected_session['status'] === 'active'): ?>
                                <div class="typing-indicator" id="typingIndicator">
                                    Клиент печатает...
                                </div>

                                <div class="chat-input-container">
                                    <form id="chatForm" class="chat-input-form" onsubmit="sendMessage(event)">
                                        <input type="hidden" name="session_id" value="<?= $selected_session['id'] ?>">
                                        <input type="hidden" name="image_file" id="imageFileInput">

                                        <textarea
                                            name="message"
                                            id="messageInput"
                                            class="chat-input"
                                            placeholder="Введите сообщение..."
                                            rows="1"
                                            onkeydown="handleKeyPress(event)"></textarea>

                                        <div class="input-actions">
                                            <label for="fileInput" class="btn-icon" title="Прикрепить изображение">
                                                📎
                                            </label>
                                            <input type="file"
                                                   id="fileInput"
                                                   accept="image/*"
                                                   style="display: none;"
                                                   onchange="handleFileSelect(event)">

                                            <button type="submit" class="btn-icon btn-send" title="Отправить">
                                                ➤
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="chat-input-container" style="text-align: center; color: #999;">
                                    Чат закрыт. Для отправки сообщений откройте его снова.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">💬</div>
                                <h3>Выберите чат</h3>
                                <p>Выберите чат из списка слева для просмотра сообщений</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно для просмотра изображения -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <button class="image-modal-close" onclick="closeImageModal()">&times;</button>
        <img id="modalImage" src="" alt="">
    </div>

    <script>
        const selectedSessionId = <?= $selected_session_id ? $selected_session_id : 'null' ?>;
        let autoRefreshInterval;
        let lastMessageCount = <?= count($messages) ?>;

        // Автоматическая прокрутка вниз
        function scrollToBottom() {
            const container = document.getElementById('messagesContainer');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        }

        // Прокрутка при загрузке
        window.addEventListener('load', scrollToBottom);

        // Отправка сообщения
        async function sendMessage(event) {
            event.preventDefault();

            const form = event.target;
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            const imageFile = document.getElementById('imageFileInput').value;

            if (!message && !imageFile) {
                return;
            }

            const formData = new FormData(form);

            try {
                const response = await fetch('../api/chat.php?action=send_message', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    messageInput.value = '';
                    document.getElementById('imageFileInput').value = '';
                    messageInput.style.height = 'auto';

                    // Обновляем сообщения
                    await loadMessages();
                } else {
                    alert('Ошибка: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка отправки сообщения');
            }
        }

        // Загрузка сообщений
        async function loadMessages() {
            if (!selectedSessionId) return;

            try {
                const response = await fetch(`../api/chat.php?action=get_messages&session_id=${selectedSessionId}`);
                const data = await response.json();

                if (data.success && data.messages) {
                    const container = document.getElementById('messagesContainer');
                    const wasAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;

                    // Обновляем только если есть новые сообщения
                    if (data.messages.length !== lastMessageCount) {
                        renderMessages(data.messages);
                        lastMessageCount = data.messages.length;

                        if (wasAtBottom) {
                            scrollToBottom();
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        // Отрисовка сообщений
        function renderMessages(messages) {
            const container = document.getElementById('messagesContainer');

            if (messages.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">💭</div>
                        <p>Пока нет сообщений</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = messages.map(msg => {
                let content = '';

                if (msg.message_type === 'text') {
                    content = `<div class="message-bubble">${escapeHtml(msg.message).replace(/\n/g, '<br>')}</div>`;
                } else if (msg.message_type === 'image' && msg.image_path) {
                    content = `
                        <img src="../${escapeHtml(msg.image_path)}"
                             alt="Image"
                             class="message-image"
                             onclick="openImageModal(this.src)">
                        ${msg.message ? `<div class="message-bubble">${escapeHtml(msg.message).replace(/\n/g, '<br>')}</div>` : ''}
                    `;
                }

                return `
                    <div class="message ${msg.sender_type}" data-message-id="${msg.id}">
                        <div class="message-avatar">
                            ${msg.sender_type === 'admin' ? '👤' : '👨'}
                        </div>
                        <div class="message-content">
                            ${content}
                            <div class="message-time">
                                ${formatDateTime(msg.created_at)}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Загрузка списка сессий
        async function loadSessions() {
            try {
                const response = await fetch('../api/chat.php?action=get_sessions');
                const data = await response.json();

                if (data.success && data.sessions) {
                    renderSessions(data.sessions);
                }
            } catch (error) {
                console.error('Error loading sessions:', error);
            }
        }

        // Отрисовка списка сессий
        function renderSessions(sessions) {
            const container = document.getElementById('sessionsList');

            if (sessions.length === 0) {
                container.innerHTML = `
                    <div class="empty-state" style="padding: 2rem;">
                        <div style="font-size: 3rem; margin-bottom: 1rem;">💬</div>
                        <p>Нет чатов</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = sessions.map(session => {
                const isActive = session.id == selectedSessionId;
                const isClosed = session.status === 'closed';

                return `
                    <a href="?session=${session.id}"
                       class="session-item ${isActive ? 'active' : ''} ${isClosed ? 'closed' : ''}"
                       data-session-id="${session.id}">
                        <div class="session-header">
                            <span class="session-name">
                                ${escapeHtml(session.customer_name || 'Гость')}
                            </span>
                            <span class="session-time">
                                ${session.last_message_time ? formatTime(session.last_message_time) : formatTime(session.created_at)}
                            </span>
                        </div>

                        ${session.last_message ? `
                            <div class="session-preview">
                                ${escapeHtml(session.last_message.substring(0, 50))}${session.last_message.length > 50 ? '...' : ''}
                            </div>
                        ` : ''}

                        <div class="session-footer">
                            <span class="session-status">
                                <span class="status-dot ${session.status}"></span>
                                ${session.status === 'active' ? 'Активен' : 'Закрыт'}
                            </span>

                            ${session.unread_count > 0 ? `
                                <span class="unread-badge">${session.unread_count}</span>
                            ` : ''}
                        </div>
                    </a>
                `;
            }).join('');
        }

        // Закрытие чата
        async function closeChat(sessionId) {
            if (!confirm('Закрыть этот чат?')) return;

            try {
                const response = await fetch('../api/chat.php?action=close_session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `session_id=${sessionId}`
                });

                const data = await response.json();

                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка закрытия чата');
            }
        }

        // Повторное открытие чата
        async function reopenChat(sessionId) {
            try {
                const response = await fetch('../api/chat.php?action=reopen_session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `session_id=${sessionId}`
                });

                const data = await response.json();

                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка открытия чата');
            }
        }

        // Обработка выбора файла
        async function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);

            try {
                const response = await fetch('../api/chat.php?action=upload_image', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('imageFileInput').value = data.filepath;
                    alert('Изображение загружено! Теперь отправьте сообщение.');
                } else {
                    alert('Ошибка загрузки: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Ошибка загрузки изображения');
            }
        }

        // Обработка Enter для отправки
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage(event);
            }
        }

        // Модальное окно для изображения
        function openImageModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').classList.add('active');
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.remove('active');
        }

        // Вспомогательные функции
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function formatTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleTimeString('ru-RU', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Звуковое оповещение о новом сообщении
        let lastMessageCount = 0;
        let audioContext = null;

        function playNotificationSound() {
            try {
                // Инициализируем AudioContext если его нет
                if (!audioContext) {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                }

                // Создаем простой звук уведомления
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                // Настройки звука
                oscillator.frequency.value = 800; // Частота 800 Hz
                oscillator.type = 'sine'; // Синусоида

                // Настройки громкости с затуханием
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

                // Воспроизведение
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.3);
            } catch (error) {
                console.error('Error playing sound:', error);
            }
        }

        // Проверка новых сообщений с звуковым уведомлением
        let previousUnreadCount = 0;

        async function checkForNewMessages() {
            try {
                const response = await fetch('../api/chat.php?action=get_sessions');
                const data = await response.json();

                if (data.success) {
                    const totalUnread = data.sessions.reduce((sum, session) => {
                        return sum + parseInt(session.unread_count || 0);
                    }, 0);

                    // Если есть новые непрочитанные сообщения - воспроизводим звук
                    if (totalUnread > previousUnreadCount && previousUnreadCount > 0) {
                        playNotificationSound();
                        // Мигание заголовка
                        document.title = '🔔 Новое сообщение!';
                        setTimeout(() => {
                            document.title = '💬 Чаты - Админ-панель';
                        }, 3000);
                    }

                    previousUnreadCount = totalUnread;
                }
            } catch (error) {
                console.error('Error checking messages:', error);
            }
        }

        // Автоматическое обновление сообщений каждые 3 секунды
        if (selectedSessionId) {
            autoRefreshInterval = setInterval(() => {
                loadMessages();
            }, 3000);
        }

        // Автоматическое обновление списка сессий каждые 5 секунд
        setInterval(() => {
            loadSessions();
            checkForNewMessages(); // Проверяем новые сообщения со звуком
        }, 5000);

        // Инициализируем счетчик непрочитанных при загрузке
        checkForNewMessages();

        // Автоматическое изменение размера textarea
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });
        }
    </script>
</body>
</html>
