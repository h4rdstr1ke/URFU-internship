/**
 * Обработчик загрузки аватара
 * @param {Event} e - Событие формы
 */
async function handleAvatarUpload(e) {
    e.preventDefault();
    const resultDiv = document.getElementById('upload-result');
    resultDiv.textContent = "Загрузка...";
    resultDiv.className = "notification";

    try {
        const formData = new FormData(e.target);

        // Добавляем CSRF-токен, если его нет в форме
        if (!formData.has('csrf_token')) {
            const csrfInput = document.querySelector('input[name="csrf_token"]');
            if (csrfInput) formData.append('csrf_token', csrfInput.value);
        }

        const response = await fetch('../includes/upload.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });

        // Обработка ответа
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || `Ошибка сервера: ${response.status}`);
        }

        if (data.success) {
            resultDiv.textContent = "Аватар успешно обновлён!";
            resultDiv.className = "notification success";

            // Обновляем превью аватара
            const avatarPreview = document.getElementById('avatar-preview');
            if (avatarPreview) {
                avatarPreview.src = data.url;
            } else {
                // Создаем элемент превью, если его не было
                const newPreview = document.createElement('img');
                newPreview.id = 'avatar-preview';
                newPreview.src = data.url;
                newPreview.alt = 'Мой аватар';
                e.target.insertAdjacentElement('afterend', newPreview);
            }
        } else {
            throw new Error(data.error || "Неизвестная ошибка");
        }
    } catch (error) {
        resultDiv.textContent = "Ошибка: " + error.message;
        resultDiv.className = "notification error";
        console.error("Ошибка загрузки аватара:", error);
    }
}

// Экспортируем функцию для использования в других файлах
export { handleAvatarUpload };