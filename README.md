# Название проекта

Урфу internship это сервис
для поиска стажировок и
временной работы в кампусе

## 🚀 Технологии

- Frontend: HTML5, CSS3, JavaScript 
- Backend: PHP (8.3.1)
- Сервер: MAMP (Apache/MySQL/PHP)
- База данных: MySQL (через phpMyAdmin)
- Дополнительно: (укажите библиотеки, плагины и т.д.)

## ⚙️ Установка и настройка

### Требования
- Установленный [MAMP](https://www.mamp.info/) (или MAMP PRO)
- PHP версии 8.3.1
- MySQL через phpMyAdmin

### Установка
1. Клонируйте репозиторий:
   ```bash
   git clone https://github.com/h4rdstr1ke/URFU-internship.git

2. Поместите проект в папку htdocs MAMP:
/Applications/MAMP/htdocs/ваша-папка (для Mac)
или
C:\MAMP\htdocs\ваша-папка (для Windows)

3. Импортируйте базу данных (если есть):
Откройте phpMyAdmin (http://localhost/phpmyadmin)
Создайте новую БД
Импортируйте SQL-файл из папки database/ 

4. Настройте подключение к БД в файле:
config/db_connect.php


🏿 Запуск проекта
Запустите MAMP

Откройте в браузере:

http://localhost:8888/ваша-папка
(Порт может отличаться в вашей конфигурации MAMP)

🌍 Доступ к phpMyAdmin
После запуска MAMP phpMyAdmin доступен по адресу:

http://localhost:8888/phpMyAdmin