<?php
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['admin_id'])) {
    die("Ошибка: доступ запрещен");
}

$card_id = isset($_GET['card_id']) ? (int) $_GET['card_id'] : 0;

if ($card_id <= 0) {
    die("Ошибка: неверный ID карточки");
}

$stmt = $db_connect->prepare("SELECT admin_id FROM cataloge WHERE card_id = ?");
$stmt->bind_param("i", $card_id);
$stmt->execute();
$stmt->bind_result($card_admin_id);
$stmt->fetch();
$stmt->close();

if ($_SESSION['admin_id'] != $card_admin_id) {
    die("Ошибка: вы не можете удалить эту карточку");
}

$stmt = $db_connect->prepare("DELETE FROM cataloge WHERE card_id = ?");
$stmt->bind_param("i", $card_id);

if ($stmt->execute()) {
    header("Location: ../pages/cataloge.php?success=1");
} else {
    header("Location: ../pages/cataloge.php?error=1");
}

$stmt->close();
$db_connect->close();
?>