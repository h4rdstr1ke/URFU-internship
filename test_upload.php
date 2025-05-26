<?php
require __DIR__ . '/config/session_config.php';
require __DIR__ . '/includes/db_connect.php';

echo "<h3>Проверка конфигурации</h3>";
echo "<pre>";

// 1. Проверка сессии
echo "Сессия: ";
print_r($_SESSION);
echo "\n";

// 2. Проверка БД
echo "База данных: ";
if ($db_connect->ping()) {
    echo "Подключено успешно\n";

    // Проверка таблицы
    $result = $db_connect->query("SELECT image FROM personal_about LIMIT 1");
    if ($result) {
        echo "Таблица personal_about доступна\n";
    } else {
        echo "Ошибка таблицы: " . $db_connect->error . "\n";
    }
} else {
    echo "Ошибка подключения: " . $db_connect->error . "\n";
}

// 3. Проверка CURL
echo "CURL: ";
if (function_exists('curl_version')) {
    echo "Доступен\n";
} else {
    echo "Не доступен\n";
}

// 4. Проверка fileinfo
echo "Fileinfo: ";
echo function_exists('mime_content_type') ? "Доступен" : "Не доступен";
echo "\n";

echo "</pre>";