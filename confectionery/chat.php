<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

session_start();
$db = getDB();

// Проверяем, нужно ли попросить ввести имя
$needNameInput = false;
$messages = [];

if (!isset($_SESSION['chat_session_id'])) {
    // Новый пользователь - попросим ввести имя
    $needNameInput = true;
} else {
    // Получаем сообщения для существующей сессии
    $session_id = $_SESSION['chat_session_id'];
    $stmt = $db->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
    $stmt->execute([$session_id]);
    $messages = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чат с кондитером - Кондитерка</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/confectionery.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../" class="navbar-logo">🎂 Кондитерка</a>
            <div class="navbar-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <ul class="navbar-menu" id="navbarMenu">
                <li><a href="index.php">Главная</a></li>
                <li><a href="catalog.php">Каталог</a></li>
                <li><a href="constructor.php">Конструктор тортов</a></li>
                <li><a href="chat.php">Чат с кондитером</a></li>
                <li><a href="../">← На главную</a></li>
            </ul>
        </div>
    </nav>

    <div class="chat-container">
        <h1 class="section-title">Чат с кондитером</h1>
        <p style="text-align: center; margin-bottom: 2rem; color: #666;">
            Задайте вопрос, покажите фото примера торта, и наш кондитер вам поможет!
        </p>

        <div class="chat-box">
            <div class="chat-header">
                <h3>💬 Чат с кондитером</h3>
                <p style="font-size: 0.9rem; opacity: 0.9;">Мы обычно отвечаем в течение нескольких минут</p>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php if (empty($messages)): ?>
                    <div class="message">
                        <div class="message-avatar">К</div>
                        <div class="message-content">
                            <div>Здравствуйте! Я кондитер. Чем могу вам помочь?</div>
                            <div class="message-time"><?= date('H:i') ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?= $msg['sender_type'] === 'customer' ? 'own' : '' ?>">
                            <div class="message-avatar">
                                <?= $msg['sender_type'] === 'customer' ? 'Я' : 'К' ?>
                            </div>
                            <div class="message-content">
                                <?php if ($msg['message_type'] === 'image' && $msg['image_path']): ?>
                                    <img src="../<?= escape($msg['image_path']) ?>" class="message-image" alt="Фото">
                                <?php endif; ?>
                                <div><?= nl2br(escape($msg['message'])) ?></div>
                                <div class="message-time"><?= formatDateTime($msg['created_at'], 'd.m.Y H:i') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form class="chat-input" id="chatForm" onsubmit="sendMessage(event)">
                <input type="file" id="fileInput" accept="image/*" style="display: none;" onchange="handleFileSelect(event)">
                <button type="button" class="btn file-upload-btn" onclick="document.getElementById('fileInput').click()">
                    📎
                </button>
                <input type="text" id="messageInput" placeholder="Введите сообщение..." class="form-control" required>
                <button type="submit" class="btn btn-primary">Отправить</button>
            </form>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 Кондитерка. Все права защищены.</p>
            </div>
        </div>
    </footer>

    <!-- Модальное окно для ввода имени -->
    <?php if ($needNameInput): ?>
    <div id="nameModal" style="
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    ">
        <div style="
            background: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        ">
            <h2 style="margin-top: 0; color: #ff6b9d;">👋 Добро пожаловать!</h2>
            <p style="color: #666; margin-bottom: 1.5rem;">
                Представьтесь, пожалуйста, чтобы кондитеру было проще с вами общаться:
            </p>
            <form id="nameForm" onsubmit="submitName(event)">
                <input
                    type="text"
                    id="customerName"
                    placeholder="Ваше имя"
                    class="form-control"
                    required
                    style="margin-bottom: 1rem;"
                    autocomplete="name"
                >
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Начать чат
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        let selectedFile = null;
        const needNameInput = <?= $needNameInput ? 'true' : 'false' ?>;

        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function handleFileSelect(event) {
            selectedFile = event.target.files[0];
            if (selectedFile) {
                alert('Файл выбран: ' + selectedFile.name + '\nТеперь введите сообщение и нажмите "Отправить"');
            }
        }

        async function sendMessage(e) {
            e.preventDefault();

            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();

            if (!message && !selectedFile) return;

            const formData = new FormData();
            formData.append('message', message);
            if (selectedFile) {
                formData.append('image', selectedFile);
            }

            try {
                const response = await fetch('../api/chat.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Добавляем сообщение в чат
                    addMessageToChat(message, selectedFile, true);

                    messageInput.value = '';
                    selectedFile = null;
                    document.getElementById('fileInput').value = '';
                } else {
                    alert('Ошибка: ' + result.error);
                }
            } catch (error) {
                alert('Произошла ошибка при отправке сообщения');
            }
        }

        function addMessageToChat(message, file, isOwn) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message' + (isOwn ? ' own' : '');

            let imageHtml = '';
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = messageDiv.querySelector('.message-image');
                    if (img) img.src = e.target.result;
                };
                reader.readAsDataURL(file);
                imageHtml = '<img src="" class="message-image" alt="Фото">';
            }

            messageDiv.innerHTML = `
                <div class="message-avatar">${isOwn ? 'Я' : 'К'}</div>
                <div class="message-content">
                    ${imageHtml}
                    <div>${message}</div>
                    <div class="message-time">${new Date().toLocaleTimeString('uk-UA', {hour: '2-digit', minute: '2-digit'})}</div>
                </div>
            `;

            chatMessages.appendChild(messageDiv);
            scrollToBottom();
        }

        // Автоматическая прокрутка вниз при загрузке
        window.addEventListener('load', scrollToBottom);

        // Периодическая проверка новых сообщений (каждые 5 секунд)
        setInterval(async () => {
            try {
                const response = await fetch('../api/chat.php?action=get_messages');
                const result = await response.json();

                if (result.success && result.messages && result.messages.length > 0) {
                    location.reload();
                }
            } catch (error) {
                console.error('Ошибка при проверке новых сообщений:', error);
            }
        }, 5000);

        // Мобильное меню
        function toggleMenu() {
            const menu = document.getElementById('navbarMenu');
            const toggle = document.querySelector('.navbar-toggle');
            menu.classList.toggle('active');
            toggle.classList.toggle('active');
        }

        // Закрыть меню при клике на пункт
        document.querySelectorAll('.navbar-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('navbarMenu').classList.remove('active');
                document.querySelector('.navbar-toggle').classList.remove('active');
            });
        });

        // Функция для сохранения имени клиента
        async function submitName(e) {
            e.preventDefault();
            const name = document.getElementById('customerName').value.trim();

            if (!name) return;

            try {
                const formData = new FormData();
                formData.append('action', 'set_name');
                formData.append('name', name);

                const response = await fetch('../api/chat.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Закрываем модальное окно
                    const modal = document.getElementById('nameModal');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                } else {
                    alert('Ошибка: ' + result.error);
                }
            } catch (error) {
                alert('Произошла ошибка');
            }
        }

        // Сброс сессии при закрытии страницы/вкладки
        window.addEventListener('beforeunload', function(e) {
            // Отправляем запрос на сброс сессии
            navigator.sendBeacon('../api/chat.php?action=clear_session');
        });

        // Также сбрасываем при переходе на другую страницу
        window.addEventListener('pagehide', function(e) {
            navigator.sendBeacon('../api/chat.php?action=clear_session');
        });
    </script>
</body>
</html>
