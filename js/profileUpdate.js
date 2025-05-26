/**
 * Обработчик обновления профиля
 * @param {Event} e - Событие формы
 */
async function handleProfileUpdate(e) {
    e.preventDefault();
    const resultDiv = document.getElementById('profile-result');
    resultDiv.textContent = "Сохранение данных...";
    resultDiv.className = "notification";

    try {
        const formData = new FormData(e.target);
        const response = await fetch('../includes/update_profile.php', {
            method: 'POST',
            body: formData,
            headers: { 'Accept': 'application/json' }
        });

        // Обработка ответа
        const text = await response.text();
        let data;

        try {
            data = JSON.parse(text);
        } catch {
            throw new Error("Неверный формат ответа от сервера");
        }

        if (!response.ok) {
            throw new Error(data.error || `Ошибка сервера: ${response.status}`);
        }

        if (data.success) {
            resultDiv.textContent = "Данные успешно сохранены!";
            resultDiv.className = "notification success";
        } else {
            throw new Error(data.error || "Неизвестная ошибка сохранения");
        }
    } catch (error) {
        resultDiv.textContent = "Ошибка: " + error.message;
        resultDiv.className = "notification error";
        console.error("Ошибка сохранения профиля:", error);
    }
}

export { handleProfileUpdate };