<?php
// настройка отображения ошибок (тл для разраб)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение необходимых файлов
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../includes/db_connect.php';

// Проверка авторизации пользователя
if (empty($_SESSION['authenticated'])) {
    header('Location: ../pages/reg_auth.php');
    exit();
}

// Проверка на админа, для правильной подгрузки профиля
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == true) {
    // Для администратора - берем ID из GET
    if (isset($_GET['user_id'])) {
        $user_id = (int) $_GET['user_id'];
    } else {
        // Если админ, но не указан user_id - перенаправляем в админ-панель
        header('Location: admin_profile.php');
        exit;
    }
} else {
    // Для обычного пользователя - берем его собственный ID
    $user_id = (int) $_SESSION['user_id'];
}
//Получение данных профиля из бд
function getUserProfileData($db_connect, $user_id)
{
    // Данные из таблицы personal_account
    $account_stmt = $db_connect->prepare("
        SELECT name, surname, patronymic, academic, email, telegram 
        FROM personal_account 
        WHERE user_id = ?
    ");
    $account_stmt->bind_param("i", $user_id);
    $account_stmt->execute();
    $account_data = $account_stmt->get_result()->fetch_assoc();
    $account_stmt->close();

    // Данные из таблицы personal_about
    $about_stmt = $db_connect->prepare("
        SELECT achievementOne, achievementTwo, achievementThree, about, image 
        FROM personal_about 
        WHERE user_id = ?
    ");
    $about_stmt->bind_param("i", $user_id);
    $about_stmt->execute();
    $about_data = $about_stmt->get_result()->fetch_assoc();
    $about_stmt->close();

    return [
        'account' => $account_data,
        'about' => $about_data
    ];
}

// Получаем данные профиля
$profile_data = getUserProfileData($db_connect, $user_id);
$account_data = $profile_data['account'];
$about_data = $profile_data['about'];

// Получаем статусы заявок пользователя
$application_stmt = $db_connect->prepare("
    SELECT a.application_id, a.status, c.title, a.date_change 
    FROM application_status a
    JOIN cataloge c ON a.card_id = c.card_id
    WHERE a.user_id = ?
    ORDER BY a.date_change DESC
");
$application_stmt->bind_param("i", $user_id);
$application_stmt->execute();
$applications = $application_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$application_stmt->close();

// аормируем полное ФИО
$full_name = trim(($account_data['surname'] ?? '') . ' ' .
    ($account_data['name'] ?? '') . ' ' .
    ($account_data['patronymic'] ?? ''));
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мой профиль</title>
    <link rel="stylesheet" href="../assets/css/normalize.css" />
    <link rel="stylesheet" href="../assets/css/pages/profile.css" />
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

    <div class="page">
        <!-- вадм -->
        <?php if (!empty($_SESSION['is_admin'])) { ?>
            <a href="admin_profile.php">Вернуться в админ-панель</a>
        <?php } ?>
        <!-- Выпадающее меню заявок -->
        <?php if (!empty($applications) && empty($_SESSION['is_admin'])): ?>
            <div class="applications-dropdown">
                <button class="dropdown-toggle" onclick="toggleDropdown(this)">
                    Мои уведомления <span class="badge">кол-во: <?= count($applications) ?></span>
                </button>
                <div class="dropdown-menu">
                    <?php foreach ($applications as $app):
                        // Полное преобразование всех возможных статусов
                        $status_ru = match (strtolower($app['status'])) {
                            'wait', 'pending' => 'На рассмотрении',
                            'accepted', 'approve' => 'Принята',
                            'rejected', 'decline' => 'Отклонена',
                            default => $app['status'] // Оригинальное значение, если не распознано
                        };

                        $status_class = match (strtolower($app['status'])) {
                            'wait', 'pending' => 'pending',
                            'accepted', 'approve' => 'accepted',
                            'rejected', 'decline' => 'rejected',
                            default => strtolower($app['status'])
                        };
                        ?>
                        <div class="application-item status-<?= $status_class ?>">
                            <div class="application-header">
                                <span class="application-title"><?= htmlspecialchars($app['title']) ?></span>
                                <span class="application-status"><?= $status_ru ?></span>
                            </div>
                            <div>ID: <?= htmlspecialchars($app['application_id']) ?></div>
                            <?php if ($app['date_change']): ?>
                                <div class="application-date">
                                    <?= date('d.m.Y H:i', strtotime($app['date_change'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <main class="main">
            <div class="avatar-achievement">
                <section class="form-avatar">
                    <?php if (!empty($about_data['image'])): ?>
                        <img src="<?= htmlspecialchars($about_data['image']) ?>" id="avatar-preview" alt="Фото Профиля">
                    <?php else: ?>
                        <img src="../assets/images/avatar_default.jpg" id="avatar-preview" alt="Фото Профиля">
                    <?php endif; ?>

                    <form id="avatar-form" enctype="multipart/form-data" class="hidden">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <label for="avatar">Выберите изображение:</label>
                        <input type="file" id="avatar" name="avatar" accept="image/*" required disabled>
                        <button type="submit" class="hidden">Загрузить аватар</button>
                    </form>
                    <div id="upload-result" class="notification"></div>
                </section>
                <section class="achievement">
                    <h4>Мои достижения</h4>
                    <label for="achievementOne">Достижение 1 (ссылка):</label>
                    <input type="text" form="profile-form" id="achievementOne" name="achievementOne"
                        value="<?= htmlspecialchars($about_data['achievementOne'] ?? '') ?>"
                        placeholder="https://example.com/achievement1" disabled>

                    <label for="achievementTwo">Достижение 2 (ссылка):</label>
                    <input type="text" form="profile-form" id="achievementTwo" name="achievementTwo"
                        value="<?= htmlspecialchars($about_data['achievementTwo'] ?? '') ?>"
                        placeholder="https://example.com/achievement2" disabled>

                    <label for="achievementThree">Достижение 3 (ссылка):</label>
                    <input type="text" form="profile-form" id="achievementThree" name="achievementThree"
                        value="<?= htmlspecialchars($about_data['achievementThree'] ?? '') ?>"
                        placeholder="https://example.com/achievement3" disabled>
                </section>
            </div>
            <div class="info-about">
                <!-- Основная информация профиля -->
                <section class="form-section">
                    <h3>Основная информация</h3>
                    <form id="profile-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <label for="name">ФИО:</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($full_name) ?>"
                            placeholder="Иванов Иван Иванович" disabled>

                        <label for="academic">Академическая группа:</label>
                        <input type="text" id="academic" name="academic"
                            value="<?= htmlspecialchars($account_data['academic'] ?? '') ?>"
                            placeholder="Укажите вашу группу" disabled>

                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email"
                            value="<?= htmlspecialchars($account_data['email'] ?? '') ?>" placeholder="example@mail.com"
                            disabled>

                        <label for="telegram">Telegram:</label>
                        <input type="text" id="telegram" name="telegram"
                            value="<?= htmlspecialchars($account_data['telegram'] ?? '') ?>" placeholder="@username"
                            disabled>
                    </form>
                </section>
                <section class="about">
                    <label for="about">Обо мне:</label>
                    <textarea form="profile-form" id="about" name="about" placeholder="Расскажите о себе"
                        disabled><?= htmlspecialchars($about_data['about'] ?? '') ?></textarea>
                </section>
            </div>

        </main>
        <div id="profile-result" class="notification test"></div>
        <!-- Кнопка редактирования -->
        <div class="edit-profile">
            <?php if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != true) { ?>
                <div class="edit-container">
                    <button id="edit-btn" class="edit-btn">Редактировать профиль</button>
                </div>
            <?php } ?>
            <button form="profile-form" type="submit" class="hidden">Сохранить изменения</button>
        </div>
        <!-- Выход -->
        <?php if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != true) { ?>
            <form class="logout-form" action="../includes/logout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <button type="submit" class="logout-btn">Выйти из системы</button>
            </form>
        <?php } ?>
    </div>
    <script type="module" src="/js/main.js"></script>
    <script>
        function toggleDropdown(button) {
            const menu = button.nextElementSibling;
            button.classList.toggle('open');
            menu.classList.toggle('open');
        }
    </script>
</body>

</html>