<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

// Получение одного товара
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        // Получаем дополнительные изображения
        $stmt = $db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order");
        $stmt->execute([$id]);
        $images = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'product' => $product,
            'images' => $images
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Товар не найден'], 404);
    }
}

// Получение всех товаров
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;

if ($category_id) {
    $stmt = $db->prepare("SELECT * FROM products WHERE category_id = ? AND active = 1 ORDER BY created_at DESC");
    $stmt->execute([$category_id]);
} else {
    $stmt = $db->query("SELECT * FROM products WHERE active = 1 ORDER BY created_at DESC");
}

$products = $stmt->fetchAll();

jsonResponse(['success' => true, 'products' => $products]);
?>
