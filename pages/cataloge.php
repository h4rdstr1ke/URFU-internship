<?php
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Просто получаем все карточки из базы
$result = $db_connect->query("SELECT * FROM cataloge");
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Каталог</title>
    <style>
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            position: relative;
        }

        .delete-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff4444;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .respond-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <header class="header">
        <img src="../assets/images/Logo.svg" alt="Логотип">
        <nav>
            <a href="../index.html">Главная</a>
            <a href="#">Каталог</a>
            <a href="#">О нас</a>
            <a href='profile.php'>
                <img src="../assets/images/Personal_Account.svg" alt="Личный кабинет">
            </a>
        </nav>
    </header>

    <div class="card-container">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <!-- Кнопка удаления (только для создателя карточки) -->
                    <?php if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == $row['admin_id']): ?>
                        <a href="../includes/delete_card.php?card_id=<?= $row['card_id'] ?>" class="delete-btn"
                            onclick="return confirm('Удалить эту карточку?')">
                            Удалить
                        </a>
                    <?php endif; ?>

                    <h3><?= htmlspecialchars($row['title']) ?></h3>
                    <p><?= htmlspecialchars($row['smallDesc']) ?></p>

                    <!-- Кнопка отклика -->
                    <?php
                    // Перед выводом кнопки проверяем существование заявки
                    $has_application = false;
                    if (isset($_SESSION['user_id'])) {
                        $stmt = $db_connect->prepare("SELECT 1 FROM application WHERE card_id = ? AND user_id = ? AND accept IS NOT FALSE");
                        $stmt->bind_param("ii", $row['card_id'], $_SESSION['user_id']);
                        $stmt->execute();
                        $has_application = (bool) $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                    }
                    ?>

                    <?php if (!$has_application): ?>
                        <button class="respond-btn" onclick="window.location.href='application.php?card_id=<?= $row['card_id'] ?>'">
                            Откликнуться
                        </button>
                    <?php else: ?>
                        <button class="respond-btn" disabled style="background-color: #cccccc;">
                            Заявка отправлена
                        </button>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Нет доступных карточек</p>
        <?php endif; ?>
    </div>
</body>

</html>