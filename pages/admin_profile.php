<?php
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Проверка что пользователь - администратор
if (!isset($_SESSION['admin_id'])) {
    header("Location: reg_auth.php");
    exit;
}

// Обработка создания новой карточки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_card'])) {
    $title = trim($_POST['title']);
    $smallDesc = trim($_POST['smallDesc']);
    $fullDesc = trim($_POST['fullDesc']);

    if (!empty($title) && !empty($smallDesc)) {
        $stmt = $db_connect->prepare("INSERT INTO cataloge (title, smallDesc, fullDesc, admin_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $title, $smallDesc, $fullDesc, $_SESSION['admin_id']);
        $stmt->execute();
        $stmt->close();

        // Перенаправляем чтобы избежать дублирования при обновлении
        header("Location: admin_profile.php");
        exit;
    }
}

// Обработка действий с заявками
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept'])) {
        $application_id = (int) $_POST['application_id'];
        $stmt = $db_connect->prepare("UPDATE application SET accept = TRUE WHERE application_id = ? AND admin_id = ?");
        $stmt->bind_param("ii", $application_id, $_SESSION['admin_id']);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['reject'])) {
        $application_id = (int) $_POST['application_id'];
        $stmt = $db_connect->prepare("UPDATE application SET accept = FALSE WHERE application_id = ? AND admin_id = ?");
        $stmt->bind_param("ii", $application_id, $_SESSION['admin_id']);
        $stmt->execute();
        $stmt->close();
    }
}

// Получаем заявки для текущего администратора
function getApplications($db, $admin_id, $status)
{
    $query = "SELECT a.*, c.title 
              FROM application a
              JOIN cataloge c ON a.card_id = c.card_id
              WHERE a.admin_id = ?";

    if ($status === 'pending') {
        $query .= " AND a.accept IS NULL";
    } elseif ($status === 'accepted') {
        $query .= " AND a.accept = TRUE";
    } elseif ($status === 'rejected') {
        $query .= " AND a.accept = FALSE";
    }

    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    return $result;
}

$pending_apps = getApplications($db_connect, $_SESSION['admin_id'], 'pending');
$accepted_apps = getApplications($db_connect, $_SESSION['admin_id'], 'accepted');
$rejected_apps = getApplications($db_connect, $_SESSION['admin_id'], 'rejected');
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>Профиль администратора</title>
    <style>
        .application-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .application-item {
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
        }

        .button-group {
            margin-top: 10px;
        }

        button {
            margin-right: 5px;
            padding: 5px 10px;
            cursor: pointer;
        }

        .accept-btn {
            background: #4CAF50;
            color: white;
            border: none;
        }

        .reject-btn {
            background: #f44336;
            color: white;
            border: none;
        }

        .create-card-form {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f0f8ff;
        }

        .form-group {
            margin-bottom: 10px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        .form-group textarea {
            min-height: 100px;
        }

        .create-btn {
            background: #2196F3;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .logout-btn {
            background: #f44336;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <h1>Панель администратора</h1>

    <!-- Форма создания новой карточки -->
    <div class="create-card-form">
        <h2>Создать новую карточку</h2>
        <form method="POST">
            <div class="form-group">
                <label for="title">Название:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="smallDesc">Краткое описание:</label>
                <input type="text" id="smallDesc" name="smallDesc" required>
            </div>
            <div class="form-group">
                <label for="fullDesc">Полное описание:</label>
                <textarea id="fullDesc" name="fullDesc"></textarea>
            </div>
            <button type="submit" name="create_card" class="create-btn">Создать карточку</button>
        </form>
    </div>

    <div class="application-section">
        <h2>Необработанные заявки</h2>
        <?php if ($pending_apps->num_rows > 0): ?>
            <?php while ($app = $pending_apps->fetch_assoc()): ?>
                <div class="application-item">
                    <h3><?= htmlspecialchars($app['title']) ?></h3>
                    <p><?= htmlspecialchars($app['applicationDesc']) ?></p>
                    <div class="button-group">
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                            <button type="submit" name="accept" class="accept-btn">Принять</button>
                            <button type="submit" name="reject" class="reject-btn">Отклонить</button>
                        </form>
                        <button onclick="location.href='profile.php?user_id=<?= $app['user_id'] ?>'">
                            Профиль пользователя
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Нет необработанных заявок</p>
        <?php endif; ?>
    </div>

    <div class="application-section">
        <h2>Принятые заявки</h2>
        <?php if ($accepted_apps->num_rows > 0): ?>
            <?php while ($app = $accepted_apps->fetch_assoc()): ?>
                <div class="application-item">
                    <h3><?= htmlspecialchars($app['title']) ?></h3>
                    <p><?= htmlspecialchars($app['applicationDesc']) ?></p>
                    <button onclick="location.href='profile.php?user_id=<?= $app['user_id'] ?>'">
                        Профиль пользователя
                    </button>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Нет принятых заявок</p>
        <?php endif; ?>
    </div>

    <div class="application-section">
        <h2>Отклоненные заявки</h2>
        <?php if ($rejected_apps->num_rows > 0): ?>
            <?php while ($app = $rejected_apps->fetch_assoc()): ?>
                <div class="application-item">
                    <h3><?= htmlspecialchars($app['title']) ?></h3>
                    <p><?= htmlspecialchars($app['applicationDesc']) ?></p>
                    <button onclick="location.href='profile.php?user_id=<?= $app['user_id'] ?>'">
                        Профиль пользователя
                    </button>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Нет отклоненных заявок</p>
        <?php endif; ?>
    </div>

    <section>
        <form action="../includes/logout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button type="submit" class="logout-btn">Выйти из системы</button>
        </form>
    </section>
</body>

</html>