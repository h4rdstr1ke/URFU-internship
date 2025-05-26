<?php
// вывод ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/session_config.php';
require_once 'db_connect.php';

// Проверка, что форма отправлена методом POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Ошибка: форма должна отправляться методом POST.');
}

// Проверка CSRF-токена (с доп проверками)
if (empty($_SESSION['csrf_token']) || empty($_POST['csrf_token'])) {
    die('Отсутствует CSRF-токен.');
}

if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('Недействительный CSRF-токен.');
}

// Получение и очищение данных из формы
$login = trim($db_connect->real_escape_string($_POST['login'] ?? ''));
$email = trim($db_connect->real_escape_string($_POST['email'] ?? ''));
$password = trim($_POST['password'] ?? '');
$repeat_password = trim($_POST['repeat_password'] ?? '');

// Валидация данных
$errors = [];

// Проверка логина (3-50 символов, только буквы и цифры)
if (!preg_match('/^[a-zA-Z0-9]{3,50}$/', $login)) {
    $errors[] = 'Логин должен содержать только латинские буквы и цифры (3-50 символов).';
}

// Проверка email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Некорректный email.';
}

// Проверка пароля (минимум 6 символов) 
if (strlen($password) < 6) {
    $errors[] = 'Пароль должен быть не менее 6 символов.';
}

// Проверка совпадения паролей
if ($password !== $repeat_password) {
    $errors[] = 'Пароли не совпадают.';
}

// Если есть ошибки - выводим их
if (!empty($errors)) {
    die(implode('<br>', $errors));
}

// Проверка, не занят ли логин или email
$check_query = $db_connect->prepare("SELECT user_id FROM register_user WHERE login = ? OR email = ?");
$check_query->bind_param("ss", $login, $email);
$check_query->execute();
$check_result = $check_query->get_result();

if ($check_result->num_rows > 0) {
    die('Логин или email уже заняты.');
}

// Хеширование
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Вставка данных в БД
$insert_query = $db_connect->prepare("INSERT INTO register_user (login, pass, email) VALUES (?, ?, ?)");
$insert_query->bind_param("sss", $login, $hashed_password, $email);

if ($insert_query->execute()) {
    // Получаем ID нового пользователя
    $user_id = $db_connect->insert_id;

    // Записываем данные в сессию
    $_SESSION['user_id'] = $user_id;
    $_SESSION['login'] = $login;
    $_SESSION['email'] = $email;
    $_SESSION['authenticated'] = true;

    header('Location: /pages/profile.php');
    exit();
}

// закр соединения
$check_query->close();
$insert_query->close();
$db_connect->close();
?>