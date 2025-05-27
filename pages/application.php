<?php
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: reg_auth.php");
    exit;
}

$card_id = (int) ($_GET['card_id'] ?? 0);

// Проверка существования карточки и получение admin_id
$stmt = $db_connect->prepare("SELECT card_id, admin_id FROM cataloge WHERE card_id = ?");
$stmt->bind_param("i", $card_id);
$stmt->execute();
$card = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$card) {
    die("Карточка не найдена");
}

// Проверка существующей заявки
$stmt = $db_connect->prepare("SELECT application_id FROM application WHERE card_id = ? AND user_id = ? AND accept IS NOT FALSE");
$stmt->bind_param("ii", $card_id, $_SESSION['user_id']);
$stmt->execute();
$existing_application = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Если заявка уже существует и не была отклонена (accept !== FALSE)
if ($existing_application) {
    die("Вы уже подавали заявку на эту карточку");
}

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = trim($_POST['description']);

    if (!empty($description)) {
        $stmt = $db_connect->prepare("INSERT INTO application (card_id, applicationDesc, admin_id, user_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isii", $card['card_id'], $description, $card['admin_id'], $_SESSION['user_id']);

        if ($stmt->execute()) {
            $success = "Заявка успешно отправлена!";
        } else {
            $error = "Ошибка при отправке заявки: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Пожалуйста, заполните описание";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Подача заявки</title>
    <style>
        .application-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        textarea {
            width: 100%;
            min-height: 150px;
            margin: 10px 0;
            padding: 10px;
        }
    </style>
</head>

<body>
    <header>

    </header>

    <div class="application-container">
        <?php if (isset($success)): ?>
            <h2>Заявка отправлена</h2>
            <p>Ваша заявка успешно отправлена администратору.</p>
            <a href="cataloge.php">Вернуться в каталог</a>
        <?php else: ?>
            <h2>Подача заявки</h2>

            <?php if (isset($error)): ?>
                <div style="color: red;"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div>
                    <label for="description">Укажите информацию, которая может быть нам полезна</label>
                    <textarea name="description" id="description" required></textarea>
                </div>
                <button type="submit">Отправить заявку</button>
                <a href="cataloge.php">Отмена</a>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>