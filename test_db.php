<?php
/**
 * Діагностика підключення до БД та PHP
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Діагностика сайту Seiya</h1>";
echo "<hr>";

// 1. Перевірка версії PHP
echo "<h2>1. PHP версія</h2>";
echo "<p>PHP версія: " . phpversion() . "</p>";
echo "<p>✅ PHP працює!</p>";
echo "<hr>";

// 2. Перевірка підключення до БД
echo "<h2>2. Підключення до бази даних</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDB();
    echo "<p>✅ Підключення до БД успішне!</p>";

    // Перевіряємо таблиці
    echo "<h3>Наявні таблиці:</h3>";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

    // Перевіряємо кількість записів
    echo "<h3>Кількість записів:</h3>";
    $counts = [
        'fireshow_programs' => $db->query("SELECT COUNT(*) FROM fireshow_programs")->fetchColumn(),
        'fireshow_portfolio' => $db->query("SELECT COUNT(*) FROM fireshow_portfolio")->fetchColumn(),
        'users' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
        'categories' => $db->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
        'products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    ];

    foreach ($counts as $table => $count) {
        $icon = $count > 0 ? '✅' : '⚠️';
        echo "<p>$icon $table: <strong>$count</strong></p>";
    }

} catch (Exception $e) {
    echo "<p>❌ Помилка: " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'><strong>БД не підключена або не налаштована!</strong></p>";
    echo "<h3>Що робити:</h3>";
    echo "<ol>";
    echo "<li>Перевірте config/database.php</li>";
    echo "<li>Створіть базу даних 'confectionery_fireshow'</li>";
    echo "<li>Імпортуйте database/schema.sql</li>";
    echo "</ol>";
}

echo "<hr>";

// 3. Перевірка files
echo "<h2>3. Перевірка файлів CSS</h2>";
$cssFiles = [
    'assets/css/main.css',
    'assets/css/fireshow.css',
    'assets/css/confectionery.css'
];

foreach ($cssFiles as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "<p>✅ $file (розмір: " . round($size/1024, 2) . " KB)</p>";
    } else {
        echo "<p>❌ $file - НЕ ЗНАЙДЕНО!</p>";
    }
}

echo "<hr>";

// 4. Перевірка функцій
echo "<h2>4. Перевірка функцій</h2>";
require_once __DIR__ . '/config/functions.php';

$functions = ['formatPrice', 'escape', 'isAdminLoggedIn', 'getSetting'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "<p>✅ $func() - працює</p>";
    } else {
        echo "<p>❌ $func() - НЕ ЗНАЙДЕНО!</p>";
    }
}

echo "<hr>";

// 5. Перевірка мобільного меню
echo "<h2>5. Перевірка CSS класів для мобільного меню</h2>";
$mainCss = file_get_contents('assets/css/main.css');
$requiredClasses = ['.navbar-toggle', '.navbar-menu', '.hero-content', '.features-grid', '.footer'];
foreach ($requiredClasses as $class) {
    if (strpos($mainCss, $class) !== false) {
        echo "<p>✅ $class - знайдено в main.css</p>";
    } else {
        echo "<p>❌ $class - НЕ ЗНАЙДЕНО в main.css!</p>";
    }
}

echo "<hr>";
echo "<h2>✅ Діагностика завершена</h2>";
echo "<p><a href='index.php'>← Повернутися на головну</a></p>";
echo "<p><a href='admin/create_admin.php'>Створити адміністратора →</a></p>";

echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
h1 { color: #333; }
h2 { color: #666; background: white; padding: 10px; border-left: 4px solid #667eea; }
p { line-height: 1.6; }
hr { margin: 20px 0; }
a { color: #667eea; text-decoration: none; font-weight: bold; }
</style>";
?>
