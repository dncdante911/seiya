<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

// Получение одного ингредиента
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM cake_ingredients WHERE id = ?");
    $stmt->execute([$id]);
    $ingredient = $stmt->fetch();

    if ($ingredient) {
        jsonResponse(['success' => true, 'ingredient' => $ingredient]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Ингредиент не найден'], 404);
    }
}

// Получение всех ингредиентов
$ingredients = $db->query("SELECT * FROM cake_ingredients ORDER BY type, name")->fetchAll();
jsonResponse(['success' => true, 'ingredients' => $ingredients]);
?>
