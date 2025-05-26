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
    header('Location: /login.php');
    exit();
}

// Получение данных пользователя из БД
$user_id = $_SESSION['user_id'];

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
    <style>
        /* Основные стили */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h2 {
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        /* Стили для форм */
        .form-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        input[type="text"],
        input[type="email"],
        input[type="file"],
        textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        input:disabled,
        textarea:disabled {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            color: #555;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background-color: #45a049;
        }

        .logout-btn {
            background-color: #f44336;
        }

        .logout-btn:hover {
            background-color: #d32f2f;
        }

        .edit-btn {
            background-color: #2196F3;
        }

        .edit-btn:hover {
            background-color: #0b7dda;
        }

        /* Стили для аватара */
        #avatar-preview {
            max-width: 200px;
            border-radius: 50%;
            margin: 10px 0;
            display: block;
        }

        /* Стили для уведомлений */
        .notification {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }

        /* Скрытые элементы */
        .hidden {
            display: none;
        }

        /* Кнопка редактирования внизу */
        .edit-container {
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>

<body>
    <header>
        <h2>Мой профиль</h2>
    </header>

    <main>
        <!-- Секция аватара -->
        <section class="form-section">
            <h3>Аватар профиля</h3>

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
                    value="<?= htmlspecialchars($account_data['academic'] ?? '') ?>" placeholder="Укажите вашу группу"
                    disabled>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email"
                    value="<?= htmlspecialchars($account_data['email'] ?? '') ?>" placeholder="example@mail.com"
                    disabled>

                <label for="telegram">Telegram:</label>
                <input type="text" id="telegram" name="telegram"
                    value="<?= htmlspecialchars($account_data['telegram'] ?? '') ?>" placeholder="@username" disabled>

                <label for="about">Обо мне:</label>
                <textarea id="about" name="about" placeholder="Расскажите о себе"
                    disabled><?= htmlspecialchars($about_data['about'] ?? '') ?></textarea>

                <h4>Мои достижения</h4>
                <label for="achievementOne">Достижение 1 (ссылка):</label>
                <input type="text" id="achievementOne" name="achievementOne"
                    value="<?= htmlspecialchars($about_data['achievementOne'] ?? '') ?>"
                    placeholder="https://example.com/achievement1" disabled>

                <label for="achievementTwo">Достижение 2 (ссылка):</label>
                <input type="text" id="achievementTwo" name="achievementTwo"
                    value="<?= htmlspecialchars($about_data['achievementTwo'] ?? '') ?>"
                    placeholder="https://example.com/achievement2" disabled>

                <label for="achievementThree">Достижение 3 (ссылка):</label>
                <input type="text" id="achievementThree" name="achievementThree"
                    value="<?= htmlspecialchars($about_data['achievementThree'] ?? '') ?>"
                    placeholder="https://example.com/achievement3" disabled>

                <button type="submit" class="hidden">Сохранить изменения</button>
            </form>
            <div id="profile-result" class="notification"></div>
        </section>

        <!-- Кнопка редактирования -->
        <div class="edit-container">
            <button id="edit-btn" class="edit-btn">Редактировать профиль</button>
        </div>

        <!-- Выход -->
        <section>
            <form action="../includes/logout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <button type="submit" class="logout-btn">Выйти из системы</button>
            </form>
        </section>
    </main>
    <script type="module" src="/js/main.js"></script>
    <script>

    </script>
</body>

</html>