/**
 * VulnTrack - Halaman login: toggle password & isi cepat akun demo.
 */
(function () {
    'use strict';

    const pwd = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');

    toggle?.addEventListener('click', () => {
        const show = pwd.type === 'password';
        pwd.type = show ? 'text' : 'password';
        toggle.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });

    document.querySelectorAll('.js-demo-login').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.getElementById('email').value = btn.dataset.email;
            pwd.value = 'password123';
            pwd.focus();
        });
    });
})();

