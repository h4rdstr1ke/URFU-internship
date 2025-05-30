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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="../assets/css/normalize.css" />
    <link rel="stylesheet" href="../assets/css/pages/admin.css" />
</head>

<body>
    <header class="header">
        <img class="header__logo" src="../assets/images/Logo.svg" alt="URFUintership Logo">
        <nav class="header-func">
            <a href="../index.html" class="header__link">Главная</a>
            <a href="cataloge.php" class="header__link">Каталог</a>
            <a href="#" class="header__link">О нас</a>
            <a href='#' class="header__account-link">
                <img class="header__personal_account" src="../assets/images/Personal_Account.svg" alt="Личный кабинет">
            </a>
        </nav>
    </header>
    <header class="mb-4">
        <h1>Административная панель</h1>
    </header>

    <main>
        <section class="create-card-form mb-4">
            <h2>Создать новую карточку</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="title">Название</label>
                    <input type="text" id="title" name="title" required>
                </div>
                <div class="form-group">
                    <label for="smallDesc">Краткое описание</label>
                    <input type="text" id="smallDesc" name="smallDesc" required>
                </div>
                <div class="form-group">
                    <label for="fullDesc">Подробное описание</label>
                    <textarea id="fullDesc" name="fullDesc"></textarea>
                </div>
                <button type="submit" name="create_card" class="btn btn-primary">
                    Создать карточку
                </button>
            </form>
        </section>

        <div class="accordion active" id="pending-accordion">
            <div class="accordion-header" onclick="toggleAccordion('pending-accordion')">
                <h2>
                    Заявки на рассмотрении
                    <span class="accordion-badge"><?= $pending_apps->num_rows ?></span>
                </h2>
                <span class="accordion-icon">▼</span>
            </div>
            <div class="accordion-content">
                <div class="accordion-content-inner">
                    <div class="application-list">
                        <?php if ($pending_apps->num_rows > 0): ?>
                            <?php while ($app = $pending_apps->fetch_assoc()): ?>
                                <div class="application-item pending">
                                    <h3 class="application-title"><?= htmlspecialchars($app['title']) ?></h3>
                                    <p class="application-desc"><?= htmlspecialchars($app['applicationDesc']) ?></p>
                                    <div class="d-flex">
                                        <form method="POST" class="d-flex">
                                            <input type="hidden" name="application_id" value="<?= $app['application_id'] ?>">
                                            <button type="submit" name="accept" class="btn btn-success btn-sm">
                                                Принять
                                            </button>
                                            <button type="submit" name="reject" class="btn btn-danger btn-sm ml-2">
                                                Отклонить
                                            </button>
                                        </form>
                                        <button onclick="location.href='profile.php?user_id=<?= $app['user_id'] ?>'"
                                            class="btn btn-outline btn-sm ml-2">
                                            Профиль
                                        </button>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-center text-muted">Нет заявок на рассмотрении</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion" id="accepted-accordion">
            <div class="accordion-header" onclick="toggleAccordion('accepted-accordion')">
                <h2>
                    Принятые заявки
                    <span class="accordion-badge"><?= $accepted_apps->num_rows ?></span>
                </h2>
                <span class="accordion-icon">▼</span>
            </div>
            <div class="accordion-content">
                <div class="accordion-content-inner">
                    <div class="application-list">
                        <?php if ($accepted_apps->num_rows > 0): ?>
                            <?php while ($app = $accepted_apps->fetch_assoc()): ?>
                                <div class="application-item accepted">
                                    <h3 class="application-title"><?= htmlspecialchars($app['title']) ?></h3>
                                    <p class="application-desc"><?= htmlspecialchars($app['applicationDesc']) ?></p>
                                    <button onclick="location.href='profile.php?user_id=<?= $app['user_id'] ?>'"
                                        class="btn btn-outline btn-sm">
                                        Профиль пользователя
                                    </button>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-center text-muted">Нет принятых заявок</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="accordion" id="rejected-accordion">
            <div class="accordion-header" onclick="toggleAccordion('rejected-accordion')">
                <h2>
                    Отклоненные заявки
                    <span class="accordion-badge"><?= $rejected_apps->num_rows ?></span>
                </h2>
                <span class="accordion-icon">▼</span>
            </div>
            <div class="accordion-content">
                <div class="accordion-content-inner">
                    <div class="application-list">
                        <?php if ($rejected_apps->num_rows > 0): ?>
                            <?php while ($app = $rejected_apps->fetch_assoc()): ?>
                                <div class="application-item rejected">
                                    <h3 class="application-title"><?= htmlspecialchars($app['title']) ?></h3>
                                    <p class="application-desc"><?= htmlspecialchars($app['applicationDesc']) ?></p>
                                    <button onclick="location.href='profile.php?user_id=<?= $app['user_id'] ?>'"
                                        class="btn btn-outline btn-sm">
                                        Профиль пользователя
                                    </button>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-center text-muted">Нет отклоненных заявок</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="mt-3">
        <form action="../includes/logout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <button type="submit" class="btn btn-outline">
                Выйти из системы
            </button>
        </form>
    </footer>

    <script>
        function toggleAccordion(id) {
            const accordion = document.getElementById(id);
            accordion.classList.toggle('active');
        }

        // По умолчанию открываем первый аккордеон
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('pending-accordion').classList.add('active');
        });
    </script>
</body>

</html>