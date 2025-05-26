<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();

require __DIR__ . '/../config/session_config.php';
require __DIR__ . '/db_connect.php';

try {
    // Проверка авторизации и CSRF
    if (empty($_SESSION['user_id'])) {
        throw new Exception("Требуется авторизация", 401);
    }
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception("Неверный CSRF-токен", 403);
    }

    $userId = $_SESSION['user_id'];

    // Валидация ФИО
    if (empty($_POST['name'])) {
        throw new Exception("ФИО обязательно для заполнения", 400);
    }

    $fioParts = explode(' ', trim($_POST['name']));
    if (count($fioParts) < 2) {
        throw new Exception("Укажите ФИО через пробел", 400);
    }

    $surname = $fioParts[0] ?? '';
    $name = $fioParts[1] ?? '';
    $patronymic = $fioParts[2] ?? '';

    // Проверка на допустимые символы (только буквы, дефисы и пробелы)
    if (!preg_match('/^[а-яА-ЯёЁa-zA-Z\- ]+$/u', $_POST['name'])) {
        throw new Exception("ФИО может содержать только буквы, дефисы и пробелы", 400);
    }

    // Валидация академической группы (формат: РИ-230912)
    $academic = $_POST['academic'] ?? '';
    if (!empty($academic) && !preg_match('/^[А-ЯЁ]{2}-\d{6}$/u', $academic)) {
        throw new Exception("Академическая группа должна быть в формате: РИ-230912 (2 буквы, дефис, 6 цифр)", 400);
    }

    // Валидация email
    $email = $_POST['email'] ?? '';
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Укажите корректный email", 400);
    }

    // Ограничение длины email
    if (strlen($email) > 150) {
        throw new Exception("Email слишком длинный (максимум 150 символов)", 400);
    }

    // Валидация Telegram
    $telegram = $_POST['telegram'] ?? '';
    if ($telegram && !preg_match('/^@?[a-zA-Z0-9_]{5,32}$/', $telegram)) {
        throw new Exception("Укажите корректный Telegram в формате @username или username", 400);
    }

    // Удаляем @ в начале, если есть
    $telegram = ltrim($telegram, '@');

    // Валидация достижений как URL проектов (GitHub, GitLab, Bitbucket)
    $validateProjectUrl = function ($url) {
        if (empty($url)) {
            return true; // Пустые значения разрешены
        }

        // Проверка, что это валидный URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Разрешенные домены для проектов
        $allowedDomains = [
            'github.com',
            'gitlab.com',
            'bitbucket.org',
            'gitea.com',
            'sourceforge.net'
        ];

        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['host'])) {
            return false;
        }

        $host = strtolower($parsedUrl['host']);
        $host = preg_replace('/^www\./', '', $host); // Удаляем www. если есть

        return in_array($host, $allowedDomains);
    };

    $achievementOne = $_POST['achievementOne'] ?? '';
    $achievementTwo = $_POST['achievementTwo'] ?? '';
    $achievementThree = $_POST['achievementThree'] ?? '';

    // Проверка каждого достижения
    foreach (['achievementOne' => $achievementOne, 'achievementTwo' => $achievementTwo, 'achievementThree' => $achievementThree] as $field => $value) {
        if (!empty($value) && !$validateProjectUrl($value)) {
            throw new Exception("Поле '{$field}' должно содержать ссылку на GitHub, GitLab, Bitbucket или другой разрешенный хостинг проектов", 400);
        }

        // Дополнительная проверка длины URL
        if (strlen($value) > 255) {
            throw new Exception("Ссылка в поле '{$field}' слишком длинная (максимум 255 символов)", 400);
        }
    }

    // Валидация поля "О себе" (максимальная длина 1000 символов)
    $about = $_POST['about'] ?? '';
    if (strlen($about) > 1000) {
        throw new Exception("Поле 'О себе' слишком длинное (максимум 1000 символов)", 400);
    }

    // Обновляем данные в БД
    $db_connect->begin_transaction();

    // Обновляем personal_account
    $accountStmt = $db_connect->prepare("
        UPDATE personal_account 
        SET name=?, surname=?, patronymic=?, academic=?, email=?, telegram=?
        WHERE user_id=?
    ");
    $accountStmt->bind_param(
        "ssssssi",
        $name,
        $surname,
        $patronymic,
        $academic,
        $email,
        $telegram,
        $userId
    );
    $accountStmt->execute();

    // Обновляем personal_about
    $aboutStmt = $db_connect->prepare("
        INSERT INTO personal_about (user_id, achievementOne, achievementTwo, achievementThree, about)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            achievementOne=VALUES(achievementOne),
            achievementTwo=VALUES(achievementTwo),
            achievementThree=VALUES(achievementThree),
            about=VALUES(about)
    ");
    $aboutStmt->bind_param(
        "issss",
        $userId,
        $achievementOne,
        $achievementTwo,
        $achievementThree,
        $about
    );
    $aboutStmt->execute();

    $db_connect->commit();

    // Чистый JSON-ответ
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Данные успешно обновлены'
    ]);

} catch (Exception $e) {
    // Откатываем изменения при ошибке
    if (isset($db_connect)) {
        $db_connect->rollback();
    }

    ob_end_clean();
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}