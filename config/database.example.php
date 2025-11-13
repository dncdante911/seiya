<?php
/**
 * Конфигурация подключения к базе данных - ПРИМЕР
 * Скопируйте этот файл в database.php и настройте под свои данные
 * MariaDB 10.11
 */

// Настройки подключения к БД (измените на свои)
define('DB_HOST', 'localhost');               // Хост БД (обычно localhost)
define('DB_NAME', 'confectionery_fireshow');  // Имя базы данных
define('DB_USER', 'root');                    // Пользователь БД
define('DB_PASS', '');                        // Пароль БД
define('DB_CHARSET', 'utf8mb4');              // Кодировка

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Предотвращаем клонирование
    private function __clone() {}

    // Предотвращаем десериализацию
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Функция для получения подключения к БД
function getDB() {
    return Database::getInstance()->getConnection();
}
?>
