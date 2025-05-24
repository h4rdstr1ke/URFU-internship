<?php
require_once __DIR__ . '/../config/session_config.php';
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8" />
  <title>title</title>
</head>

<body>
  <div class="form-container">
    <form id="registerForm" action="../includes/reg_valid.php" method="POST" class="hidden">
      <h2>Регистрация</h2>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
      <input type="text" name="login" placeholder="Логин" required /><br />
      <input type="email" name="email" placeholder="Email" required /><br />
      <input type="password" name="password" placeholder="Придумайте Пароль" required /><br />
      <input type="password" name="repeat_password" placeholder="Повторите Пароль" required /><br />
      <button type="submit">Зарегистрироваться</button>
    </form>

    <form id="loginForm" action="../includes/auth_valid.php" method="POST">
      <h2>Вход</h2>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) ?>">
      <input type="text" name="login" placeholder="Логин" required />
      <input type="password" name="password" placeholder="Пароль" required />
      <button type="submit">Войти</button>
    </form>
  </div>
</body>

</html>