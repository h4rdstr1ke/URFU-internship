<?php
require_once __DIR__ . '/../config/session_config.php';

// Проверка CSRF-токена
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Недействительный CSRF-токен');
    }
}

// Полное уничтожение сессии
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}
session_destroy();

// Перенаправление с сообщением
$_SESSION['logout_message'] = 'Вы успешно вышли из системы';
header('Location: ../index.html');
exit();
?>