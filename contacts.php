<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/functions.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM contacts LIMIT 1");
$contacts = $stmt->fetch();

// Если нет данных, показываем дефолтные
if (!$contacts) {
    $contacts = [
        'company_name' => 'Seiya - Кондитерські вироби та вогняне шоу',
        'phone' => '+380 XX XXX XX XX',
        'email' => 'info@seiya.com.ua',
        'address' => 'м. Київ, Україна',
        'work_hours' => 'Пн-Пт: 9:00-18:00<br>Сб-Нд: за домовленістю',
        'instagram' => '',
        'facebook' => '',
        'telegram' => '',
        'viber' => '',
        'whatsapp' => '',
        'map_latitude' => null,
        'map_longitude' => null
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контакти - <?= escape($contacts['company_name']) ?></title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .contacts-page {
            min-height: 100vh;
            padding: 2rem 0;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .contacts-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .contacts-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .contacts-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #2d3748;
        }

        .contacts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .contact-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .contact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .contact-card-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .contact-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #2d3748;
        }

        .contact-card p {
            color: #4a5568;
            line-height: 1.6;
        }

        .contact-card a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .contact-card a:hover {
            color: var(--secondary-color);
        }

        .social-links {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .social-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .social-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .map-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .map-container h2 {
            margin-bottom: 1.5rem;
            color: #2d3748;
        }

        .map-container iframe {
            width: 100%;
            height: 400px;
            border: none;
            border-radius: 10px;
        }

        .back-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 3rem;
        }

        .back-link {
            padding: 1rem 2rem;
            background: white;
            color: var(--primary-color);
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .back-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 768px) {
            .contacts-header h1 {
                font-size: 2rem;
            }

            .contacts-grid {
                grid-template-columns: 1fr;
            }

            .back-links {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="contacts-page">
        <div class="contacts-container">
            <div class="contacts-header">
                <h1>📞 Контакти</h1>
                <p style="font-size: 1.2rem; color: #4a5568;">Зв'яжіться з нами зручним для вас способом</p>
            </div>

            <div class="contacts-grid">
                <!-- Телефон -->
                <div class="contact-card">
                    <div class="contact-card-icon">📱</div>
                    <h3>Телефон</h3>
                    <p>
                        <a href="tel:<?= escape(preg_replace('/[^0-9+]/', '', $contacts['phone'])) ?>">
                            <?= escape($contacts['phone']) ?>
                        </a>
                    </p>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #718096;">
                        Дзвоніть нам у будь-який час!
                    </p>
                </div>

                <!-- Email -->
                <div class="contact-card">
                    <div class="contact-card-icon">✉️</div>
                    <h3>Email</h3>
                    <p>
                        <a href="mailto:<?= escape($contacts['email']) ?>">
                            <?= escape($contacts['email']) ?>
                        </a>
                    </p>
                    <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #718096;">
                        Напишіть нам і ми відповімо протягом 24 годин
                    </p>
                </div>

                <!-- Адрес -->
                <?php if ($contacts['address']): ?>
                <div class="contact-card">
                    <div class="contact-card-icon">📍</div>
                    <h3>Адреса</h3>
                    <p><?= nl2br(escape($contacts['address'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- Часы работы -->
                <?php if ($contacts['work_hours']): ?>
                <div class="contact-card">
                    <div class="contact-card-icon">🕐</div>
                    <h3>Години роботи</h3>
                    <p><?= nl2br(escape($contacts['work_hours'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Социальные сети -->
            <?php if ($contacts['instagram'] || $contacts['facebook'] || $contacts['telegram'] || $contacts['viber'] || $contacts['whatsapp']): ?>
            <div class="map-container">
                <h2>🌐 Ми в соціальних мережах</h2>
                <div class="social-links">
                    <?php if ($contacts['instagram']): ?>
                        <a href="<?= escape($contacts['instagram']) ?>" target="_blank" class="social-link">
                            📷 Instagram
                        </a>
                    <?php endif; ?>

                    <?php if ($contacts['facebook']): ?>
                        <a href="<?= escape($contacts['facebook']) ?>" target="_blank" class="social-link">
                            📘 Facebook
                        </a>
                    <?php endif; ?>

                    <?php if ($contacts['telegram']): ?>
                        <a href="<?= escape($contacts['telegram']) ?>" target="_blank" class="social-link">
                            ✈️ Telegram
                        </a>
                    <?php endif; ?>

                    <?php if ($contacts['viber']): ?>
                        <a href="viber://chat?number=<?= escape(preg_replace('/[^0-9+]/', '', $contacts['viber'])) ?>" class="social-link">
                            💜 Viber
                        </a>
                    <?php endif; ?>

                    <?php if ($contacts['whatsapp']): ?>
                        <a href="https://wa.me/<?= escape(preg_replace('/[^0-9]/', '', $contacts['whatsapp'])) ?>" target="_blank" class="social-link">
                            💬 WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Карта -->
            <?php if ($contacts['map_latitude'] && $contacts['map_longitude']): ?>
            <div class="map-container">
                <h2>🗺️ Ми на карті</h2>
                <iframe
                    src="https://www.google.com/maps?q=<?= $contacts['map_latitude'] ?>,<?= $contacts['map_longitude'] ?>&hl=uk&z=<?= $contacts['map_zoom'] ?: 14 ?>&output=embed"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <?php endif; ?>

            <!-- Навигация назад -->
            <div class="back-links">
                <a href="confectionery/" class="back-link">🍰 Кондитерка</a>
                <a href="fireshow/" class="back-link">🔥 Фаершоу</a>
                <a href="/" class="back-link">🏠 Головна</a>
            </div>
        </div>
    </div>
</body>
</html>
