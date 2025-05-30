<?php
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header("Location: reg_auth.php");
    exit;
}

$card_id = (int) ($_GET['card_id'] ?? 0);

// Изменено: добавлен столбец fullDesc в запрос
$stmt = $db_connect->prepare("SELECT card_id, admin_id, fullDesc FROM cataloge WHERE card_id = ?");
$stmt->bind_param("i", $card_id);
$stmt->execute();
$card = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$card) {
    die("Карточка не найдена");
}

// Остальной код без изменений
$stmt = $db_connect->prepare("SELECT application_id FROM application WHERE card_id = ? AND user_id = ? AND accept IS NOT FALSE");
$stmt->bind_param("ii", $card_id, $_SESSION['user_id']);
$stmt->execute();
$existing_application = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing_application) {
    die("Вы уже подавали заявку на эту карточку");
}

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
    <link rel="stylesheet" href="../assets/css/normalize.css" />
    <link rel="stylesheet" href="../assets/css/pages/application.css" />
</head>

<body>
    <div class="application-container">
        <?php if (isset($success)): ?>
            <div class="success-message">
                <h2>Заявка отправлена</h2>
                <p>Ваша заявка успешно отправлена администратору.</p>
            </div>
            <a href="catalоge.php" class="button-link">Вернуться в каталог</a>
        <?php else: ?>
            <h2>Подача заявки</h2>

            <!-- Добавленный блок с полным описанием -->
            <div class="full-description">
                <h3>Полное описание:</h3>
                <p><?= nl2br(htmlspecialchars($card['fullDesc'])) ?></p>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div>
                    <label for="description">Укажите информацию, которая может быть нам полезна</label>
                    <textarea name="description" id="description" required></textarea>
                </div>
                <button type="submit">Отправить заявку</button>
                <a href="catalоge.php">Отмена</a>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>