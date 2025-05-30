<?php
require_once __DIR__ . '/../config/session_config.php';
require_once 'db_connect.php';

// Инициализация массива ошибок
$_SESSION['reg_errors'] = [
    'errors' => [],
    'fields' => [],
    'general' => ''
];

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['reg_errors']['general'] = 'Ошибка: форма должна отправляться методом POST.';
    header('Location: /pages/reg_auth.php?form=register');
    exit();
}

// Проверка CSRF-токена
if (empty($_SESSION['csrf_token']) || empty($_POST['csrf_token'])) {
    $_SESSION['reg_errors']['general'] = 'Отсутствует CSRF-токен.';
    header('Location: /pages/reg_auth.php?form=register');
    exit();
}

if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['reg_errors']['general'] = 'Недействительный CSRF-токен.';
    header('Location: /pages/reg_auth.php?form=register');
    exit();
}

// Получение данных
$login = trim($_POST['login'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$repeat_password = trim($_POST['repeat_password'] ?? '');

// Сохраняем введенные значения
$_SESSION['reg_errors']['fields'] = [
    'login' => $login,
    'email' => $email
];

// Валидация данных
if (!preg_match('/^[a-zA-Z0-9]{3,50}$/', $login)) {
    $_SESSION['reg_errors']['errors']['login'] = 'Логин должен содержать только латинские буквы и цифры (3-50 символов).';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['reg_errors']['errors']['email'] = 'Некорректный email.';
}

if (strlen($password) < 6) {
    $_SESSION['reg_errors']['errors']['password'] = 'Пароль должен быть не менее 6 символов.';
}

if ($password !== $repeat_password) {
    $_SESSION['reg_errors']['errors']['repeat_password'] = 'Пароли не совпадают.';
}

// Если есть ошибки - возвращаем на форму
if (!empty($_SESSION['reg_errors']['errors'])) {
    header('Location: /pages/reg_auth.php?form=register');
    exit();
}

// Проверка уникальности логина/email
$check_query = $db_connect->prepare("SELECT user_id FROM register_user WHERE login = ? OR email = ?");
$check_query->bind_param("ss", $login, $email);
$check_query->execute();
$check_result = $check_query->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['reg_errors']['general'] = 'Логин или email уже заняты.';
    header('Location: /pages/reg_auth.php?form=register');
    exit();
}

// Регистрация пользователя
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$insert_query = $db_connect->prepare("INSERT INTO register_user (login, pass, email) VALUES (?, ?, ?)");
$insert_query->bind_param("sss", $login, $hashed_password, $email);

if ($insert_query->execute()) {
    $_SESSION['user_id'] = $db_connect->insert_id;
    $_SESSION['login'] = $login;
    $_SESSION['authenticated'] = true;
    unset($_SESSION['reg_errors']);
    header('Location: /pages/profile.php');
    exit();
} else {
    $_SESSION['reg_errors']['general'] = 'Ошибка при регистрации. Попробуйте позже.';
    header('Location: ../pages/reg_auth.php?form=register');
    exit();
}
?>