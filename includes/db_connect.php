<?php
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "internship";
$port = 3306;

$db_connect = new mysqli($servername, $username, $password, $dbname, $port);


// Проверяем соединение
if ($db_connect->connect_error) {
    die("Connection failed: " . $connect->connect_error);
} else {

}
?>