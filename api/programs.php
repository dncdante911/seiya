<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

// Получение одной программы
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM fireshow_programs WHERE id = ?");
    $stmt->execute([$id]);
    $program = $stmt->fetch();

    if ($program) {
        jsonResponse([
            'success' => true,
            'program' => $program
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Программа не найдена'], 404);
    }
}

// Получение всех программ
$active_only = isset($_GET['active_only']) ? (bool)$_GET['active_only'] : false;

if ($active_only) {
    $stmt = $db->query("SELECT * FROM fireshow_programs WHERE active = 1 ORDER BY featured DESC, created_at DESC");
} else {
    $stmt = $db->query("SELECT * FROM fireshow_programs ORDER BY featured DESC, created_at DESC");
}

$programs = $stmt->fetchAll();

jsonResponse(['success' => true, 'programs' => $programs]);
?>
