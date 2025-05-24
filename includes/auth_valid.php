<?php
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Ошибка: только POST-запросы.');
}

// Проверка CSRF-токена (чуть позже уберу) либо оставлить
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    die('Недействительный CSRF-токен.');
}

// Получаем данные из формы
$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');

// Проверяем заполнение полей
if (empty($login) || empty($password)) {
    die('Заполните все поля.');
}

// Ищем пользователя в БД
$stmt = $db_connect->prepare("SELECT user_id, login, pass FROM register_user WHERE login = ?");
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

// Перенаправляем в личный кабинет
header('Location: ../pages/profile.php');
exit();
?>