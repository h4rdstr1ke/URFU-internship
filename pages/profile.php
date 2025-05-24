<?php

// конфиг сессии 
require_once __DIR__ . '/../config/session_config.php';

// Проверка на авторизацию
if (empty($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    header('Location: /reg_auth.php');
    exit();
}

// Врнменно

$userId = $_SESSION['user_id'];
$userLogin = $_SESSION['login'];
echo $userId;

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <title>Личный кабинет</title>
</head>

<body>
    <p>Саламчик</p>
</body>

</html>