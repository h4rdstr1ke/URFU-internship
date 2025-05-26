<?php
header('Content-Type: application/json');
ob_start();

require __DIR__ . '/../config/session_config.php';
require __DIR__ . '/db_connect.php';

// Логирование входящего запроса
file_put_contents(
    __DIR__ . '/upload_debug.log',
    date('[Y-m-d H:i:s]') . " Request: " . print_r($_REQUEST, true) . "\n" .
    "Files: " . print_r($_FILES, true) . "\n",
    FILE_APPEND
);

try {
    // 1. Проверка метода
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Разрешены только POST-запросы", 405);
    }

    // 2. Проверка CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new Exception("Неверный CSRF-токен", 403);
    }

    // 3. Проверка авторизации
    if (empty($_SESSION['user_id'])) {
        throw new Exception("Требуется авторизация", 401);
    }

    // 4. Проверка файла
    if (empty($_FILES['avatar'])) {
        throw new Exception("Файл не был загружен", 400);
    }

    $file = $_FILES['avatar'];

    // 5. Проверка ошибок загрузки
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            0 => "Нет ошибок",
            1 => "Файл превышает upload_max_filesize",
            2 => "Файл превышает MAX_FILE_SIZE",
            3 => "Файл загружен не полностью",
            4 => "Файл не был загружен",
            6 => "Отсутствует временная папка",
            7 => "Не удалось записать файл на диск",
            8 => "Расширение PHP остановило загрузку"
        ];
        throw new Exception($errors[$file['error']] ?? "Неизвестная ошибка загрузки", 400);
    }

    // 6. Проверка размера (макс. 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception("Файл слишком большой (максимум 5MB)", 400);
    }

    // 7. Проверка типа файла (без fileinfo)
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions)) {
        throw new Exception("Допустимы только JPG, PNG или GIF", 400);
    }

    // 8. Загрузка на ImgBB
    $apiKey = "055b6e9b580db13c7fb69190d564c67e";
    $imageContent = file_get_contents($file['tmp_name']);
    $base64Image = base64_encode($imageContent);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.imgbb.com/1/upload?key=" . $apiKey,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => ['image' => $base64Image],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception("Ошибка при загрузке на ImgBB: " . curl_error($ch), 500);
    }
    curl_close($ch);

    $result = json_decode($response, true);
    if (empty($result['data']['url'])) {
        throw new Exception("ImgBB вернул ошибку: " . ($result['error']['message'] ?? 'Неизвестная ошибка'), 500);
    }

    // 9. Сохранение в БД
    $stmt = $db_connect->prepare("
        INSERT INTO personal_about (user_id, image) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE image = ?
    ");
    $stmt->bind_param("iss", $_SESSION['user_id'], $result['data']['url'], $result['data']['url']);

    if (!$stmt->execute()) {
        throw new Exception("Ошибка при сохранении в БД: " . $stmt->error, 500);
    }

    // Успешный ответ
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'url' => $result['data']['url']
    ]);

} catch (Exception $e) {
    ob_end_clean();
    http_response_code($e->getCode() ?: 400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);

    // Логирование ошибки
    file_put_contents(
        __DIR__ . '/upload_errors.log',
        date('[Y-m-d H:i:s]') . " ERROR [" . $e->getCode() . "]: " . $e->getMessage() . "\n" .
        "File: " . $e->getFile() . ":" . $e->getLine() . "\n" .
        "Trace:\n" . $e->getTraceAsString() . "\n\n",
        FILE_APPEND
    );
}