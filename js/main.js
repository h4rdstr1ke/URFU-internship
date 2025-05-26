import { handleAvatarUpload } from './avatarUpload.js';
import { handleProfileUpdate } from './profileUpdate.js';
import { initProfileEdit } from './profileEdit.js';

document.addEventListener('DOMContentLoaded', () => {
    // Инициализация управления редактированием
    initProfileEdit();

    // Инициализация обработчиков форм
    const avatarForm = document.getElementById('avatar-form');
    const profileForm = document.getElementById('profile-form');

    if (avatarForm) {
        avatarForm.addEventListener('submit', handleAvatarUpload);
    }

    if (profileForm) {
        profileForm.addEventListener('submit', handleProfileUpdate);
    }
});