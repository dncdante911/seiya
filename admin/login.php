<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';

// Если уже авторизован, перенаправляем в админку
if (isAdminLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_role'] = $user['role'];
                    redirect('index.php');
                } else {
                    $error = 'Невірний пароль. <a href="create_admin.php" style="color: #fff; text-decoration: underline;">Створити нового адміна</a>';
                }
            } else {
                $error = 'Користувача не знайдено. <a href="create_admin.php" style="color: #fff; text-decoration: underline;">Створити адміна</a>';
            }
        } catch (Exception $e) {
            $error = 'Помилка підключення до БД: ' . $e->getMessage() . '<br><a href="create_admin.php" style="color: #fff; text-decoration: underline;">Перевірте налаштування</a>';
        }
    } else {
        $error = 'Заповніть всі поля';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админку</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-box {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-box h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--dark-color);
        }
    </style>
</head>
<body>
    <div class="login-page">
        <div class="login-box">
            <h1>🔐 Вход в админку</h1>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= escape($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" name="username" class="form-control" required autofocus>
                </div>

                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Войти</button>
            </form>

            <p style="text-align: center; margin-top: 2rem; color: #666;">
                <small>Дефолтный логин: admin / Пароль: admin123</small>
            </p>
        </div>
    </div>
</body>
</html>
