/**
 * Чат с кондитером - JavaScript
 */

const ChatSystem = {
    selectedFile: null,
    lastMessageId: 0,
    pollInterval: null,

    init() {
        this.setupEventListeners();
        this.scrollToBottom();
        this.startPolling();
    },

    setupEventListeners() {
        // Форма отправки
        const form = document.getElementById('chatForm');
        if (form) {
            form.addEventListener('submit', (e) => this.sendMessage(e));
        }

        // Выбор файла
        const fileInput = document.getElementById('fileInput');
        if (fileInput) {
            fileInput.addEventListener('change', (e) => this.handleFileSelect(e));
        }

        // Enter для отправки
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    form.dispatchEvent(new Event('submit'));
                }
            });
        }
    },

    scrollToBottom() {
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Плавная прокрутка
            chatMessages.scrollTo({
                top: chatMessages.scrollHeight,
                behavior: 'smooth'
            });
        }
    },

    handleFileSelect(event) {
        this.selectedFile = event.target.files[0];
        if (this.selectedFile) {
            const fileSize = (this.selectedFile.size / 1024 / 1024).toFixed(2);
            window.SiteHelpers.showNotification(
                `Файл выбран: ${this.selectedFile.name} (${fileSize} MB)`,
                'success'
            );
        }
    },

    async sendMessage(e) {
        e.preventDefault();

        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();

        if (!message && !this.selectedFile) {
            return;
        }

        const formData = new FormData();
        formData.append('message', message);
        if (this.selectedFile) {
            formData.append('image', this.selectedFile);
        }

        // Блокируем форму
        const submitBtn = e.target.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Отправка...';
        }

        try {
            const response = await fetch('../api/chat.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Добавляем сообщение в чат
                this.addMessageToUI(message, this.selectedFile, true);

                // Очищаем форму
                messageInput.value = '';
                this.selectedFile = null;
                document.getElementById('fileInput').value = '';
            } else {
                window.SiteHelpers.showNotification(
                    result.error || 'Ошибка отправки',
                    'error'
                );
            }
        } catch (error) {
            console.error('Chat error:', error);
            window.SiteHelpers.showNotification(
                'Произошла ошибка при отправке',
                'error'
            );
        } finally {
            // Разблокируем форму
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Отправить';
            }
        }
    },

    addMessageToUI(message, file, isOwn) {
        const chatMessages = document.getElementById('chatMessages');
        if (!chatMessages) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = 'message' + (isOwn ? ' own' : '');
        messageDiv.style.opacity = '0';
        messageDiv.style.transform = 'translateY(20px)';

        let imageHtml = '';
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = messageDiv.querySelector('.message-image');
                if (img) {
                    img.src = e.target.result;
                    img.style.opacity = '0';
                    setTimeout(() => {
                        img.style.transition = 'opacity 0.3s ease';
                        img.style.opacity = '1';
                    }, 100);
                }
            };
            reader.readAsDataURL(file);
            imageHtml = '<img src="" class="message-image" alt="Фото">';
        }

        const now = new Date();
        const time = now.toLocaleTimeString('uk-UA', { hour: '2-digit', minute: '2-digit' });

        messageDiv.innerHTML = `
            <div class="message-avatar">${isOwn ? 'Я' : 'К'}</div>
            <div class="message-content">
                ${imageHtml}
                <div>${message}</div>
                <div class="message-time">${time}</div>
            </div>
        `;

        chatMessages.appendChild(messageDiv);

        // Анимация появления
        setTimeout(() => {
            messageDiv.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            messageDiv.style.opacity = '1';
            messageDiv.style.transform = 'translateY(0)';
        }, 50);

        this.scrollToBottom();
    },

    startPolling() {
        // Проверяем новые сообщения каждые 3 секунды
        this.pollInterval = setInterval(() => {
            this.checkNewMessages();
        }, 3000);
    },

    async checkNewMessages() {
        try {
            const response = await fetch(`../api/chat.php?action=get_messages&last_id=${this.lastMessageId}`);
            const result = await response.json();

            if (result.success && result.messages && result.messages.length > 0) {
                result.messages.forEach(msg => {
                    if (msg.id > this.lastMessageId) {
                        this.lastMessageId = msg.id;
                        if (msg.sender_type === 'admin') {
                            this.addAdminMessage(msg);
                        }
                    }
                });
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
    },

    addAdminMessage(msg) {
        const chatMessages = document.getElementById('chatMessages');
        if (!chatMessages) return;

        const messageDiv = document.createElement('div');
        messageDiv.className = 'message';
        messageDiv.style.opacity = '0';
        messageDiv.style.transform = 'translateY(20px)';

        let imageHtml = '';
        if (msg.message_type === 'image' && msg.image_path) {
            imageHtml = `<img src="../${msg.image_path}" class="message-image" alt="Фото">`;
        }

        const time = new Date(msg.created_at).toLocaleTimeString('uk-UA', { hour: '2-digit', minute: '2-digit' });

        messageDiv.innerHTML = `
            <div class="message-avatar">К</div>
            <div class="message-content">
                ${imageHtml}
                <div>${msg.message}</div>
                <div class="message-time">${time}</div>
            </div>
        `;

        chatMessages.appendChild(messageDiv);

        // Анимация появления
        setTimeout(() => {
            messageDiv.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            messageDiv.style.opacity = '1';
            messageDiv.style.transform = 'translateY(0)';
        }, 50);

        this.scrollToBottom();

        // Звук уведомления (опционально)
        this.playNotificationSound();
    },

    playNotificationSound() {
        // Простой звук уведомления
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjWK0+7PhC8GHJK89Np/NggggdnzzYEuBzB+zfjbhTUHMnvK9N2GPgcxecn32oQ+BzF8yPTahT0HMHvI9NuFOwcxfMj02oU9BzB7yPTbhTsHMnzI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhTsHMXvI9NqFPQcwe8j024U7BzF7yPTbhTsHMnvI9NuFOwcxe8j024U8BzB7yPTbhQ==');
        audio.volume = 0.3;
        audio.play().catch(() => {});
    },

    destroy() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
    }
};

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
    ChatSystem.init();
});

// Очистка при уходе со страницы
window.addEventListener('beforeunload', () => {
    ChatSystem.destroy();
});
