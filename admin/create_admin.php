<?php
/**
 * Скрипт для создания админа
 * Запустите этот файл один раз через браузер: http://ваш-сайт/admin/create_admin.php
 * После создания админа - УДАЛИТЕ этот файл!
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();

    // Проверяем существует ли админ
    $stmt = $db->query("SELECT * FROM users WHERE username = 'admin'");
    $admin = $stmt->fetch();

    if ($admin) {
        echo "<h2>✅ Админ уже существует!</h2>";
        echo "<p>Логин: <strong>admin</strong></p>";
        echo "<p>Пароль: <strong>admin123</strong></p>";
        echo "<hr>";
        echo "<p><a href='login.php'>Перейти к входу →</a></p>";
    } else {
        // Создаем админа
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@seiya.com.ua', $password, 'admin']);

        echo "<h2>✅ Админ успешно создан!</h2>";
        echo "<p>Логин: <strong>admin</strong></p>";
        echo "<p>Пароль: <strong>admin123</strong></p>";
        echo "<hr>";
        echo "<p><strong>⚠️ ВАЖНО:</strong> После входа удалите этот файл (create_admin.php)!</p>";
        echo "<p><a href='login.php'>Перейти к входу →</a></p>";
    }

    echo "<hr>";
    echo "<h3>Информация о подключении:</h3>";
    echo "<p>✅ Подключение к базе данных работает!</p>";

} catch (Exception $e) {
    echo "<h2>❌ Ошибка:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<hr>";
    echo "<h3>Что делать:</h3>";
    echo "<ol>";
    echo "<li>Проверьте настройки в файле <code>config/database.php</code></li>";
    echo "<li>Убедитесь что база данных создана</li>";
    echo "<li>Импортируйте файл <code>database/schema.sql</code></li>";
    echo "</ol>";
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание админа</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h2 { color: #333; }
        p { line-height: 1.6; }
        code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            border: 1px solid #ddd;
        }
        a {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        a:hover { background: #5568d3; }
    </style>
</head>
</html>
