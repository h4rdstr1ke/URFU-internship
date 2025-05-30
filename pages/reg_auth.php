<?php
require_once __DIR__ . '/../config/session_config.php';

// Получаем ошибки из сессии
$reg_errors = $_SESSION['reg_errors'] ?? [];
$auth_errors = $_SESSION['auth_errors'] ?? [];
unset($_SESSION['reg_errors'], $_SESSION['auth_errors']);

// Определяем активную форму
$active_form = (!empty($reg_errors) ? 'register' : (!empty($auth_errors) ? 'login' : 'login'));
?>
<!DOCTYPE html>
<html lang="ru">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Вход / Регистрация | URFUintership</title>
  <link rel="stylesheet" href="../assets/css/pages/reg_auth.css">
</head>

<body>
  <div class="form-container">
    <!-- Форма регистрации -->
    <form id="registerForm" action="../includes/reg_valid.php" method="POST"
      class="auth-form <?= $active_form === 'register' ? 'active' : 'hidden' ?>">
      <h2>Регистрация</h2>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <?php if (!empty($reg_errors['general'])): ?>
        <div class="error-message general-error"><?= htmlspecialchars($reg_errors['general']) ?></div>
      <?php endif; ?>

      <div class="input-group">
        <input type="text" name="login" placeholder="Логин" required
          value="<?= htmlspecialchars($reg_errors['fields']['login'] ?? '') ?>">
        <div class="error-message" id="registerLoginError">
          <?= htmlspecialchars($reg_errors['errors']['login'] ?? '') ?>
        </div>
      </div>

      <div class="input-group">
        <input type="email" name="email" placeholder="Email" required
          value="<?= htmlspecialchars($reg_errors['fields']['email'] ?? '') ?>">
        <div class="error-message" id="registerEmailError">
          <?= htmlspecialchars($reg_errors['errors']['email'] ?? '') ?>
        </div>
      </div>

      <div class="input-group">
        <input type="password" name="password" placeholder="Пароль" required>
        <div class="error-message" id="registerPasswordError">
          <?= htmlspecialchars($reg_errors['errors']['password'] ?? '') ?>
        </div>
      </div>

      <div class="input-group">
        <input type="password" name="repeat_password" placeholder="Повторите пароль" required>
        <div class="error-message" id="registerRepeatPasswordError">
          <?= htmlspecialchars($reg_errors['errors']['repeat_password'] ?? '') ?>
        </div>
      </div>

      <button type="submit">Зарегистрироваться</button>
    </form>

    <!-- Форма входа -->
    <form id="loginForm" action="../includes/auth_valid.php" method="POST"
      class="auth-form <?= $active_form === 'login' ? 'active' : 'hidden' ?>">
      <h2>Вход</h2>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

      <?php if (!empty($auth_errors['general'])): ?>
        <div class="error-message general-error"><?= htmlspecialchars($auth_errors['general']) ?></div>
      <?php endif; ?>

      <div class="input-group">
        <input type="text" name="login" placeholder="Логин" required
          value="<?= htmlspecialchars($auth_errors['fields']['login'] ?? '') ?>">
        <div class="error-message" id="loginLoginError">
          <?= htmlspecialchars($auth_errors['errors']['login'] ?? '') ?>
        </div>
      </div>

      <div class="input-group">
        <input type="password" name="password" placeholder="Пароль" required>
        <div class="error-message" id="loginPasswordError">
          <?= htmlspecialchars($auth_errors['errors']['password'] ?? '') ?>
        </div>
      </div>

      <button type="submit">Войти</button>
    </form>

    <!-- Переключатели форм -->
    <div class="form-toggle" id="loginToggle" <?= $active_form === 'register' ? '' : 'style="display:none"' ?>>
      <p>Есть аккаунт? <a href="#" onclick="switchForm('login'); return false;">Войти</a></p>
    </div>

    <div class="form-toggle" id="registerToggle" <?= $active_form === 'login' ? '' : 'style="display:none"' ?>>
      <p>Нет аккаунта? <a href="#" onclick="switchForm('register'); return false;">Зарегистрироваться</a></p>
    </div>
  </div>
  <script src="../js/validation_reg_auth.js"></script>
  <script>
    // Переключение форм
    function switchForm(formType) {
      document.getElementById('loginForm').classList.toggle('active', formType === 'login');
      document.getElementById('loginForm').classList.toggle('hidden', formType !== 'login');

      document.getElementById('registerForm').classList.toggle('active', formType === 'register');
      document.getElementById('registerForm').classList.toggle('hidden', formType !== 'register');

      document.getElementById('loginToggle').style.display = formType === 'register' ? 'block' : 'none';
      document.getElementById('registerToggle').style.display = formType === 'login' ? 'block' : 'none';
    };
  </script>
</body>

</html>