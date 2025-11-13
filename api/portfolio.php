<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

// Получение одной работы
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM fireshow_portfolio WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if ($item) {
        jsonResponse([
            'success' => true,
            'item' => $item
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Работа не найдена'], 404);
    }
}

// Получение всех работ
$active_only = isset($_GET['active_only']) ? (bool)$_GET['active_only'] : false;
$media_type = isset($_GET['media_type']) ? $_GET['media_type'] : null;

$query = "SELECT * FROM fireshow_portfolio WHERE 1=1";
$params = [];

if ($active_only) {
    $query .= " AND active = 1";
}

if ($media_type && in_array($media_type, ['image', 'video'])) {
    $query .= " AND media_type = ?";
    $params[] = $media_type;
}

$query .= " ORDER BY sort_order ASC, created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$portfolio = $stmt->fetchAll();

jsonResponse(['success' => true, 'portfolio' => $portfolio]);
?>
