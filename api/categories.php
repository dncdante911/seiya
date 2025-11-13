<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

// Получение одной категории
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();

    if ($category) {
        jsonResponse(['success' => true, 'category' => $category]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Категория не найдена'], 404);
    }
}

// Получение всех категорий
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
jsonResponse(['success' => true, 'categories' => $categories]);
?>
