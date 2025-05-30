-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Хост: localhost:3306
-- Время создания: Май 30 2025 г., 02:16
-- Версия сервера: 5.7.24
-- Версия PHP: 8.3.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `internship`
--
CREATE DATABASE IF NOT EXISTS `internship` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `internship`;

-- --------------------------------------------------------

--
-- Структура таблицы `application`
--

DROP TABLE IF EXISTS `application`;
CREATE TABLE `application` (
  `application_id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `applicationDesc` varchar(255) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `accept` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Триггеры `application`
--
DROP TRIGGER IF EXISTS `after_application_insert`;
DELIMITER $$
CREATE TRIGGER `after_application_insert` AFTER INSERT ON `application` FOR EACH ROW BEGIN
    DECLARE status_value VARCHAR(20);
    
    IF NEW.accept IS NULL THEN
        SET status_value = 'wait';
    ELSEIF NEW.accept = 1 THEN
        SET status_value = 'accepted';
    ELSE
        SET status_value = 'rejected';
    END IF;
    
    INSERT INTO application_status (application_id, user_id, status, date_change, card_id)
    VALUES (NEW.application_id, NEW.user_id, status_value, NOW(), NEW.card_id);
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `after_application_update`;
DELIMITER $$
CREATE TRIGGER `after_application_update` AFTER UPDATE ON `application` FOR EACH ROW BEGIN
    DECLARE status_value VARCHAR(20);
    
    -- Обновляем статус только если изменилось поле accept
    IF NEW.accept != OLD.accept OR (NEW.accept IS NULL AND OLD.accept IS NOT NULL) OR (NEW.accept IS NOT NULL AND OLD.accept IS NULL) THEN
        IF NEW.accept IS NULL THEN
            SET status_value = 'wait';
        ELSEIF NEW.accept = 1 THEN
            SET status_value = 'accepted';
        ELSE
            SET status_value = 'rejected';
        END IF;
        
        -- Обновляем статус И дату изменения
        UPDATE application_status 
        SET 
            status = status_value,
            date_change = NOW()  -- Фиксируем текущую дату и время изменения
        WHERE application_id = NEW.application_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `application_status`
--

DROP TABLE IF EXISTS `application_status`;
CREATE TABLE `application_status` (
  `application_id` int(11) NOT NULL,
  `status` varchar(100) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date_change` datetime DEFAULT NULL,
  `card_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `cataloge`
--

DROP TABLE IF EXISTS `cataloge`;
CREATE TABLE `cataloge` (
  `card_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `smallDesc` varchar(100) NOT NULL,
  `fullDesc` varchar(500) NOT NULL,
  `admin_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `personal_about`
--

DROP TABLE IF EXISTS `personal_about`;
CREATE TABLE `personal_about` (
  `user_id` int(11) NOT NULL,
  `achievementOne` varchar(255) DEFAULT NULL,
  `achievementTwo` varchar(255) DEFAULT NULL,
  `achievementThree` varchar(255) DEFAULT NULL,
  `about` varchar(500) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `personal_account`
--

DROP TABLE IF EXISTS `personal_account`;
CREATE TABLE `personal_account` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `patronymic` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `academic` varchar(50) DEFAULT NULL,
  `telegram` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `register_user`
--

DROP TABLE IF EXISTS `register_user`;
CREATE TABLE `register_user` (
  `user_id` int(11) NOT NULL,
  `login` varchar(50) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `email` varchar(150) NOT NULL,
  `is_admin` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Триггеры `register_user`
--
DROP TRIGGER IF EXISTS `after_user_register`;
DELIMITER $$
CREATE TRIGGER `after_user_register` AFTER INSERT ON `register_user` FOR EACH ROW BEGIN

    INSERT INTO personal_account (user_id, email)
    VALUES (NEW.user_id, NEW.email);
    

    INSERT INTO personal_about (user_id)
    VALUES (NEW.user_id);
END
$$
DELIMITER ;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `application`
--
ALTER TABLE `application`
  ADD UNIQUE KEY `application_id` (`application_id`);

--
-- Индексы таблицы `application_status`
--
ALTER TABLE `application_status`
  ADD PRIMARY KEY (`application_id`);

--
-- Индексы таблицы `cataloge`
--
ALTER TABLE `cataloge`
  ADD UNIQUE KEY `card_id` (`card_id`);

--
-- Индексы таблицы `personal_about`
--
ALTER TABLE `personal_about`
  ADD PRIMARY KEY (`user_id`);

--
-- Индексы таблицы `personal_account`
--
ALTER TABLE `personal_account`
  ADD PRIMARY KEY (`user_id`);

--
-- Индексы таблицы `register_user`
--
ALTER TABLE `register_user`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `application`
--
ALTER TABLE `application`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `cataloge`
--
ALTER TABLE `cataloge`
  MODIFY `card_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `register_user`
--
ALTER TABLE `register_user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `personal_about`
--
ALTER TABLE `personal_about`
  ADD CONSTRAINT `personal_about_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `register_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ограничения внешнего ключа таблицы `personal_account`
--
ALTER TABLE `personal_account`
  ADD CONSTRAINT `personal_account_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `register_user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
