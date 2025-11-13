<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/../config/security.php';

if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$db = getDB();
$success = '';
$error = '';

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $work_hours = trim($_POST['work_hours'] ?? '');

    $instagram = trim($_POST['instagram'] ?? '');
    $facebook = trim($_POST['facebook'] ?? '');
    $telegram = trim($_POST['telegram'] ?? '');
    $viber = trim($_POST['viber'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');

    $map_latitude = trim($_POST['map_latitude'] ?? '');
    $map_longitude = trim($_POST['map_longitude'] ?? '');
    $map_zoom = (int)($_POST['map_zoom'] ?? 14);

    if ($company_name && $phone && $email) {
        try {
            // Проверяем есть ли запись
            $stmt = $db->query("SELECT COUNT(*) as count FROM contacts");
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                // Обновляем существующую запись
                $stmt = $db->prepare("
                    UPDATE contacts SET
                        company_name = ?, phone = ?, email = ?, address = ?, work_hours = ?,
                        instagram = ?, facebook = ?, telegram = ?, viber = ?, whatsapp = ?,
                        map_latitude = ?, map_longitude = ?, map_zoom = ?
                    WHERE id = (SELECT id FROM (SELECT MIN(id) as id FROM contacts) as temp)
                ");
                $stmt->execute([
                    $company_name, $phone, $email, $address, $work_hours,
                    $instagram, $facebook, $telegram, $viber, $whatsapp,
                    $map_latitude ?: null, $map_longitude ?: null, $map_zoom
                ]);
            } else {
                // Создаем новую запись
                $stmt = $db->prepare("
                    INSERT INTO contacts (
                        company_name, phone, email, address, work_hours,
                        instagram, facebook, telegram, viber, whatsapp,
                        map_latitude, map_longitude, map_zoom
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $company_name, $phone, $email, $address, $work_hours,
                    $instagram, $facebook, $telegram, $viber, $whatsapp,
                    $map_latitude ?: null, $map_longitude ?: null, $map_zoom
                ]);
            }

            $success = 'Контактная информация успешно сохранена!';
        } catch (Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    } else {
        $error = 'Заполните обязательные поля (название, телефон, email)!';
    }
}

// Получаем текущие контакты
$stmt = $db->query("SELECT * FROM contacts LIMIT 1");
$contacts = $stmt->fetch();

// Если нет записи, создаем дефолтную
if (!$contacts) {
    $contacts = [
        'company_name' => 'Seiya - Кондитерські вироби та вогняне шоу',
        'phone' => '+380 XX XXX XX XX',
        'email' => 'info@seiya.com.ua',
        'address' => '',
        'work_hours' => 'Пн-Пт: 9:00-18:00, Сб-Нд: за домовленістю',
        'instagram' => '',
        'facebook' => '',
        'telegram' => '',
        'viber' => '',
        'whatsapp' => '',
        'map_latitude' => '',
        'map_longitude' => '',
        'map_zoom' => 14
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Контактная информация - Админка</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="admin-content">
            <header class="admin-header">
                <h1>📞 Контактная информация</h1>
            </header>

            <main style="padding: 2rem;">

            <?php if ($success): ?>
                <div class="alert alert-success"><?= escape($success) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= escape($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="form-container">
                <div class="card">
                    <h3>Основная информация</h3>

                    <div class="form-group">
                        <label>Название компании *</label>
                        <input type="text" name="company_name" class="form-control" value="<?= escape($contacts['company_name']) ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <label>Телефон *</label>
                            <input type="tel" name="phone" class="form-control" value="<?= escape($contacts['phone']) ?>" required placeholder="+380 XX XXX XX XX">
                        </div>

                        <div class="form-group" style="flex: 1;">
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control" value="<?= escape($contacts['email']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Адрес</label>
                        <textarea name="address" class="form-control" rows="2"><?= escape($contacts['address']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Часы работы</label>
                        <textarea name="work_hours" class="form-control" rows="2"><?= escape($contacts['work_hours']) ?></textarea>
                        <small class="form-text">Например: Пн-Пт: 9:00-18:00, Сб-Нд: за домовленістю</small>
                    </div>
                </div>

                <div class="card">
                    <h3>Социальные сети</h3>

                    <div class="form-group">
                        <label>Instagram</label>
                        <input type="url" name="instagram" class="form-control" value="<?= escape($contacts['instagram']) ?>" placeholder="https://instagram.com/your_account">
                    </div>

                    <div class="form-group">
                        <label>Facebook</label>
                        <input type="url" name="facebook" class="form-control" value="<?= escape($contacts['facebook']) ?>" placeholder="https://facebook.com/your_page">
                    </div>

                    <div class="form-group">
                        <label>Telegram</label>
                        <input type="text" name="telegram" class="form-control" value="<?= escape($contacts['telegram']) ?>" placeholder="@your_channel или https://t.me/your_channel">
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <label>Viber</label>
                            <input type="tel" name="viber" class="form-control" value="<?= escape($contacts['viber']) ?>" placeholder="+380XXXXXXXXX">
                        </div>

                        <div class="form-group" style="flex: 1;">
                            <label>WhatsApp</label>
                            <input type="tel" name="whatsapp" class="form-control" value="<?= escape($contacts['whatsapp']) ?>" placeholder="+380XXXXXXXXX">
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h3>Карта (Google Maps)</h3>
                    <p style="color: #666; margin-bottom: 1rem;">
                        Для получения координат откройте Google Maps, найдите нужное место, кликните правой кнопкой и выберите координаты.
                    </p>

                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <label>Широта (Latitude)</label>
                            <input type="text" name="map_latitude" class="form-control" value="<?= escape($contacts['map_latitude']) ?>" placeholder="50.450001">
                        </div>

                        <div class="form-group" style="flex: 1;">
                            <label>Долгота (Longitude)</label>
                            <input type="text" name="map_longitude" class="form-control" value="<?= escape($contacts['map_longitude']) ?>" placeholder="30.523333">
                        </div>

                        <div class="form-group" style="flex: 0.5;">
                            <label>Зум</label>
                            <input type="number" name="map_zoom" class="form-control" value="<?= escape($contacts['map_zoom']) ?>" min="1" max="20">
                        </div>
                    </div>

                    <?php if ($contacts['map_latitude'] && $contacts['map_longitude']): ?>
                        <div class="form-group">
                            <label>Предпросмотр карты</label>
                            <iframe
                                width="100%"
                                height="300"
                                style="border:0; border-radius: 8px;"
                                loading="lazy"
                                allowfullscreen
                                src="https://www.google.com/maps/embed/v1/place?key=YOUR_API_KEY&q=<?= $contacts['map_latitude'] ?>,<?= $contacts['map_longitude'] ?>&zoom=<?= $contacts['map_zoom'] ?>">
                            </iframe>
                            <small class="form-text">Примечание: Для работы карты нужен Google Maps API ключ</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Сохранить изменения</button>
                </div>
            </form>

            <!-- Предпросмотр -->
            <div class="card" style="margin-top: 2rem;">
                <h3>Предпросмотр контактов на сайте</h3>
                <div style="padding: 1rem; background: #f5f5f5; border-radius: 8px;">
                    <p><strong>📍 Адрес:</strong> <?= escape($contacts['address']) ?: 'Не указан' ?></p>
                    <p><strong>📞 Телефон:</strong> <a href="tel:<?= escape($contacts['phone']) ?>"><?= escape($contacts['phone']) ?></a></p>
                    <p><strong>✉️ Email:</strong> <a href="mailto:<?= escape($contacts['email']) ?>"><?= escape($contacts['email']) ?></a></p>
                    <p><strong>🕐 Часы работы:</strong> <?= nl2br(escape($contacts['work_hours'])) ?></p>

                    <div style="margin-top: 1rem;">
                        <?php if ($contacts['instagram']): ?>
                            <a href="<?= escape($contacts['instagram']) ?>" target="_blank" style="margin-right: 1rem;">📷 Instagram</a>
                        <?php endif; ?>
                        <?php if ($contacts['facebook']): ?>
                            <a href="<?= escape($contacts['facebook']) ?>" target="_blank" style="margin-right: 1rem;">📘 Facebook</a>
                        <?php endif; ?>
                        <?php if ($contacts['telegram']): ?>
                            <a href="<?= escape($contacts['telegram']) ?>" target="_blank" style="margin-right: 1rem;">✈️ Telegram</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
        </div> <!-- /.admin-content -->
    </div> <!-- /.admin-wrapper -->
</body>
</html>
