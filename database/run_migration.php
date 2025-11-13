<?php
/**
 * Скрипт для выполнения SQL миграций
 */
require_once __DIR__ . '/../config/database.php';

$sqlFile = __DIR__ . '/05_news_contacts_about.sql';

if (!file_exists($sqlFile)) {
    die("SQL файл не найден: $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("Ошибка чтения SQL файла\n");
}

try {
    $db = getDB();

    // Разделяем на отдельные запросы
    $queries = array_filter(
        array_map('trim', explode(';', $sql)),
        function($query) {
            return !empty($query) && !preg_match('/^--/', $query);
        }
    );

    echo "Начинаем миграцию...\n\n";

    foreach ($queries as $index => $query) {
        if (empty(trim($query))) continue;

        echo "Выполняем запрос " . ($index + 1) . "...\n";

        try {
            $db->exec($query);
            echo "✓ Успешно\n\n";
        } catch (PDOException $e) {
            echo "✗ Ошибка: " . $e->getMessage() . "\n\n";
            // Продолжаем выполнение остальных запросов
        }
    }

    echo "\n=================================\n";
    echo "Миграция завершена!\n";
    echo "=================================\n\n";

    // Проверяем созданные таблицы
    echo "Проверка созданных таблиц:\n";
    $tables = ['news', 'contacts', 'about_us'];

    foreach ($tables as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        echo "- $table: {$result['count']} записей\n";
    }

} catch (Exception $e) {
    die("Ошибка подключения к БД: " . $e->getMessage() . "\n");
}
?>
