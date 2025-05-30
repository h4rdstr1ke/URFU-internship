<?php
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/db_connect.php';

// Инициализация массива ошибок
$_SESSION['auth_errors'] = [
    'errors' => [],
    'fields' => [],
    'general' => ''
];

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['auth_errors']['general'] = 'Ошибка: только POST-запросы.';
    header('Location: /pages/reg_auth.php');
    exit();
}

// Проверка CSRF-токена
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    $_SESSION['auth_errors']['general'] = 'Недействительный CSRF-токен.';
    header('Location: /pages/reg_auth.php');
    exit();
}

// Получаем данные из формы
$login = trim($_POST['login'] ?? '');
$password = trim($_POST['password'] ?? '');

// Сохраняем введенные данные
$_SESSION['auth_errors']['fields']['login'] = $login;

// Проверяем заполнение полей
if (empty($login)) {
    $_SESSION['auth_errors']['errors']['login'] = 'Введите логин.';
}

if (empty($password)) {
    $_SESSION['auth_errors']['errors']['password'] = 'Введите пароль.';
}

// Если есть ошибки - возвращаем на форму
if (!empty($_SESSION['auth_errors']['errors'])) {
    header('Location: /pages/reg_auth.php');
    exit();
}

// Поиск пользователя в БД
$stmt = $db_connect->prepare("SELECT user_id, login, pass, is_admin FROM register_user WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Проверка пароля
if (!$user || !password_verify($password, $user['pass'])) {
    $_SESSION['auth_errors']['general'] = 'Неверный логин или пароль.';
    header('Location: /pages/reg_auth.php');
    exit();
}

// Успешная авторизация
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['login'] = $user['login'];
$_SESSION['authenticated'] = true;

// Если пользователь админ
if ($user['is_admin']) {
    $_SESSION['admin_id'] = $user['user_id'];
    $_SESSION['is_admin'] = true;
}

// Очищаем ошибки
unset($_SESSION['auth_errors']);

header('Location: ../pages/profile.php');
exit();
?>