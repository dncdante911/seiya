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

// Обработка сохранения настроек
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    try {
        // Массив всех настроек для сохранения
        $settings = [
            // General settings
            'site_name' => trim($_POST['site_name'] ?? ''),
            'site_phone' => trim($_POST['site_phone'] ?? ''),
            'site_email' => trim($_POST['site_email'] ?? ''),
            'working_hours' => trim($_POST['working_hours'] ?? ''),

            // Social media
            'social_vk' => trim($_POST['social_vk'] ?? ''),
            'social_instagram' => trim($_POST['social_instagram'] ?? ''),
            'social_facebook' => trim($_POST['social_facebook'] ?? ''),

            // Content
            'about_us_text' => trim($_POST['about_us_text'] ?? ''),
            'delivery_info_text' => trim($_POST['delivery_info_text'] ?? ''),
            'terms_conditions' => trim($_POST['terms_conditions'] ?? '')
        ];

        // Валидация обязательных полей
        if (empty($settings['site_name'])) {
            $error = 'Название сайта обязательно!';
        } elseif (!empty($settings['site_email']) && !isValidEmail($settings['site_email'])) {
            $error = 'Неверный формат email!';
        } elseif (!empty($settings['site_phone']) && !isValidPhone($settings['site_phone'])) {
            $error = 'Неверный формат телефона!';
        } else {
            // Сохраняем все настройки
            foreach ($settings as $key => $value) {
                updateSetting($key, $value);
            }

            $success = 'Настройки успешно сохранены!';
        }
    } catch (Exception $e) {
        $error = 'Ошибка при сохранении настроек: ' . $e->getMessage();
    }
}

// Загружаем текущие настройки
$currentSettings = [
    // General
    'site_name' => getSetting('site_name', 'Кондитерская & Фаершоу'),
    'site_phone' => getSetting('site_phone', ''),
    'site_email' => getSetting('site_email', ''),
    'working_hours' => getSetting('working_hours', ''),

    // Social media
    'social_vk' => getSetting('social_vk', ''),
    'social_instagram' => getSetting('social_instagram', ''),
    'social_facebook' => getSetting('social_facebook', ''),

    // Content
    'about_us_text' => getSetting('about_us_text', ''),
    'delivery_info_text' => getSetting('delivery_info_text', ''),
    'terms_conditions' => getSetting('terms_conditions', '')
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки сайта - Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .settings-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            border-bottom: 2px solid #e0e0e0;
        }

        .tab-button {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
        }

        .tab-button:hover {
            color: var(--primary-color);
        }

        .tab-button.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-group label .required {
            color: #e91e63;
        }

        .form-group .help-text {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.85rem;
            color: #666;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }

        .settings-footer {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 1.5rem 2rem;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            margin: 2rem -2rem -2rem;
            border-radius: 0 0 10px 10px;
        }

        .btn-save {
            padding: 0.875rem 2rem;
            font-size: 1rem;
            font-weight: 600;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header">
                <h1>Настройки сайта</h1>
                <div class="admin-user">
                    👤 <?= escape($_SESSION['admin_username']) ?>
                    <a href="logout.php" class="btn btn-danger" style="margin-left: 1rem;">Выход</a>
                </div>
            </header>

            <div class="container">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>

                <div class="section-box">
                    <div class="settings-tabs">
                        <button class="tab-button active" onclick="switchTab('general')">
                            Общие настройки
                        </button>
                        <button class="tab-button" onclick="switchTab('social')">
                            Социальные сети
                        </button>
                        <button class="tab-button" onclick="switchTab('content')">
                            Контент
                        </button>
                    </div>

                    <form method="POST" class="admin-form">
                        <input type="hidden" name="action" value="save_settings">

                        <!-- Вкладка: Общие настройки -->
                        <div id="tab-general" class="tab-content active">
                            <div class="section-title">Основная информация</div>

                            <div class="form-group">
                                <label>
                                    Название сайта <span class="required">*</span>
                                </label>
                                <input type="text"
                                       name="site_name"
                                       class="form-control"
                                       value="<?= escape($currentSettings['site_name']) ?>"
                                       required>
                                <small class="help-text">Отображается в шапке сайта и в заголовках браузера</small>
                            </div>

                            <div class="form-group">
                                <label>Контактный телефон</label>
                                <input type="tel"
                                       name="site_phone"
                                       class="form-control"
                                       value="<?= escape($currentSettings['site_phone']) ?>"
                                       placeholder="+380 XX XXX XX XX">
                                <small class="help-text">Основной телефон для связи с клиентами</small>
                            </div>

                            <div class="form-group">
                                <label>Email для связи</label>
                                <input type="email"
                                       name="site_email"
                                       class="form-control"
                                       value="<?= escape($currentSettings['site_email']) ?>"
                                       placeholder="info@example.com">
                                <small class="help-text">Email для обратной связи и уведомлений</small>
                            </div>

                            <div class="form-group">
                                <label>Часы работы</label>
                                <textarea name="working_hours"
                                          class="form-control"
                                          rows="3"
                                          placeholder="Пн-Пт: 9:00 - 18:00&#10;Сб-Вс: 10:00 - 16:00"><?= escape($currentSettings['working_hours']) ?></textarea>
                                <small class="help-text">Укажите режим работы вашего бизнеса</small>
                            </div>
                        </div>

                        <!-- Вкладка: Социальные сети -->
                        <div id="tab-social" class="tab-content">
                            <div class="section-title">Ссылки на социальные сети</div>

                            <div class="form-group">
                                <label>ВКонтакте</label>
                                <input type="url"
                                       name="social_vk"
                                       class="form-control"
                                       value="<?= escape($currentSettings['social_vk']) ?>"
                                       placeholder="https://vk.com/your_page">
                                <small class="help-text">Полная ссылка на вашу страницу или группу ВКонтакте</small>
                            </div>

                            <div class="form-group">
                                <label>Instagram</label>
                                <input type="url"
                                       name="social_instagram"
                                       class="form-control"
                                       value="<?= escape($currentSettings['social_instagram']) ?>"
                                       placeholder="https://instagram.com/your_account">
                                <small class="help-text">Полная ссылка на ваш Instagram аккаунт</small>
                            </div>

                            <div class="form-group">
                                <label>Facebook</label>
                                <input type="url"
                                       name="social_facebook"
                                       class="form-control"
                                       value="<?= escape($currentSettings['social_facebook']) ?>"
                                       placeholder="https://facebook.com/your_page">
                                <small class="help-text">Полная ссылка на вашу страницу Facebook</small>
                            </div>

                            <div style="padding: 1rem; background: #f0f8ff; border-left: 4px solid #2196f3; border-radius: 5px; margin-top: 1.5rem;">
                                <strong>Совет:</strong> Ссылки на социальные сети будут отображаться в футере сайта. Оставьте поле пустым, если не хотите показывать конкретную социальную сеть.
                            </div>
                        </div>

                        <!-- Вкладка: Контент -->
                        <div id="tab-content" class="tab-content">
                            <div class="section-title">Текстовое содержимое страниц</div>

                            <div class="form-group">
                                <label>О нас</label>
                                <textarea name="about_us_text"
                                          class="form-control"
                                          rows="6"
                                          placeholder="Расскажите о вашей компании, истории, ценностях..."><?= escape($currentSettings['about_us_text']) ?></textarea>
                                <small class="help-text">Текст для раздела "О нас" на главной странице</small>
                            </div>

                            <div class="form-group">
                                <label>Информация о доставке</label>
                                <textarea name="delivery_info_text"
                                          class="form-control"
                                          rows="6"
                                          placeholder="Условия доставки, стоимость, сроки..."><?= escape($currentSettings['delivery_info_text']) ?></textarea>
                                <small class="help-text">Подробная информация об условиях и стоимости доставки</small>
                            </div>

                            <div class="form-group">
                                <label>Условия и соглашения</label>
                                <textarea name="terms_conditions"
                                          class="form-control"
                                          rows="8"
                                          placeholder="Правила использования сайта, политика конфиденциальности..."><?= escape($currentSettings['terms_conditions']) ?></textarea>
                                <small class="help-text">Юридическая информация, условия использования, политика конфиденциальности</small>
                            </div>

                            <div style="padding: 1rem; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px; margin-top: 1.5rem;">
                                <strong>Важно:</strong> Текстовый контент поддерживает перенос строк. Для лучшего форматирования используйте абзацы.
                            </div>
                        </div>

                        <!-- Футер с кнопкой сохранения -->
                        <div class="settings-footer">
                            <button type="submit" class="btn btn-success btn-save">
                                💾 Сохранить все настройки
                            </button>
                            <span style="margin-left: 1rem; color: #666;">
                                Все изменения будут применены мгновенно
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Скрываем все вкладки
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));

            // Убираем активный класс со всех кнопок
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(btn => btn.classList.remove('active'));

            // Показываем выбранную вкладку
            document.getElementById('tab-' + tabName).classList.add('active');

            // Активируем соответствующую кнопку
            event.target.classList.add('active');
        }

        // Автоматически скрывать алерты через 5 секунд
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Предупреждение при попытке покинуть страницу с несохраненными изменениями
        let formChanged = false;
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input, textarea');

        inputs.forEach(input => {
            input.addEventListener('change', () => {
                formChanged = true;
            });
        });

        form.addEventListener('submit', () => {
            formChanged = false;
        });

        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
