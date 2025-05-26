export function initProfileEdit() {
    const editBtn = document.getElementById('edit-btn');
    if (!editBtn) return;

    const formInputs = document.querySelectorAll('input:not([type="hidden"]), textarea');
    const fileInput = document.getElementById('avatar');
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    const hiddenSections = document.querySelectorAll('.hidden');

    let isEditing = false;

    const toggleEditMode = () => {
        isEditing = !isEditing;

        // Включаем/выключаем поля, но не отключаем полностью (чтобы данные отправлялись)
        formInputs.forEach(input => {
            if (isEditing) {
                input.removeAttribute('disabled');
            } else {
                input.setAttribute('disabled', 'disabled');
            }
        });

        hiddenSections.forEach(section => section.classList.toggle('hidden', !isEditing));

        if (isEditing) {
            editBtn.textContent = 'Отменить редактирование';
            editBtn.classList.add('cancel-mode');
            fileInput.focus();
        } else {
            location.reload();
        }
    };

    editBtn.addEventListener('click', toggleEditMode);

    document.addEventListener('keydown', (e) => {
        if (isEditing && e.key === 'Escape') {
            toggleEditMode();
        }
    });

    // Перед отправкой формы временно включаем disabled поля
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', () => {
            formInputs.forEach(input => input.removeAttribute('disabled'));
        });
    });
}