<?php
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Ошибка: только POST-запросы.');
}

// Проверка CSRF-токена (можно включить позже)
// if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
//     die('Недействительный CSRF-токен.');
// }

// Получаем данные из формы
$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');

// Проверяем заполнение полей
if (empty($login) || empty($password)) {
    die('Заполните все поля.');
}

// Ищем пользователя в БД (теперь включаем проверку is_admin)
$stmt = $db_connect->prepare("SELECT user_id, login, pass, is_admin FROM register_user WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Проверка пароля
if (!$user || !password_verify($password, $user['pass'])) {
    die('Неверный логин или пароль.');
}

// Заносим данные в сессию
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['login'] = $user['login'];
$_SESSION['authenticated'] = true;

// Если пользователь админ - устанавливаем флаг
if ($user['is_admin']) {
    $_SESSION['admin_id'] = $user['user_id'];
    $_SESSION['is_admin'] = true;
}

// Перенаправляем в личный кабинет
header('Location: ../pages/profile.php');
exit();
?>