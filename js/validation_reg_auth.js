// Валидация формы регистрации
function validateRegisterForm(form) {
    let isValid = true;
    const login = form.login.value.trim();
    const email = form.email.value.trim();
    const password = form.password.value.trim();
    const repeatPassword = form.repeat_password.value.trim();

    // Очищаем предыдущие ошибки
    clearErrors(form);

    // Валидация логина
    if (!/^[a-zA-Z0-9]{3,50}$/.test(login)) {
        showError(form, 'login', 'Логин должен содержать только латинские буквы и цифры (3-50 символов)');
        isValid = false;
    }

    // Валидация email
    if (!/^\S+@\S+\.\S+$/.test(email)) {
        showError(form, 'email', 'Введите корректный email');
        isValid = false;
    }

    // Валидация пароля
    if (password.length < 6) {
        showError(form, 'password', 'Пароль должен быть не менее 6 символов');
        isValid = false;
    }

    // Проверка совпадения паролей
    if (password !== repeatPassword) {
        showError(form, 'repeat_password', 'Пароли не совпадают');
        isValid = false;
    }

    return isValid;
}

// Валидация формы входа
function validateLoginForm(form) {
    let isValid = true;
    clearErrors(form);

    if (form.login.value.trim() === '') {
        showError(form, 'login', 'Введите логин');
        isValid = false;
    }

    if (form.password.value.trim() === '') {
        showError(form, 'password', 'Введите пароль');
        isValid = false;
    }

    return isValid;
}

// Показать ошибку
function showError(form, fieldName, message) {
    const field = form.elements[fieldName];
    const errorElement = form.querySelector(`[name="${fieldName}"]`).nextElementSibling;

    field.classList.add('input-error');
    errorElement.textContent = message;
    errorElement.style.display = 'block';
}

// Очистить ошибки
function clearErrors(form) {
    form.querySelectorAll('.input-error').forEach(el => {
        el.classList.remove('input-error');
    });

    form.querySelectorAll('.error-message').forEach(el => {
        el.style.display = 'none';
        el.textContent = '';
    });

    const generalError = form.querySelector('.general-error');
    if (generalError) generalError.remove();
}

// Инициализация валидации форм
function initFormValidation() {
    const registerForm = document.getElementById('registerForm');
    const loginForm = document.getElementById('loginForm');

    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            if (!validateRegisterForm(this)) {
                e.preventDefault();
            }
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function (e) {
            if (!validateLoginForm(this)) {
                e.preventDefault();
            }
        });
    }

    // Сброс ошибок при вводе
    document.querySelectorAll('input').forEach(input => {
        input.addEventListener('input', function () {
            this.classList.remove('input-error');
            const errorElement = this.nextElementSibling;
            if (errorElement && errorElement.classList.contains('error-message')) {
                errorElement.style.display = 'none';
            }
        });
    });
}

// Запуск при загрузке страницы
document.addEventListener('DOMContentLoaded', initFormValidation);