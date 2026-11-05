(function () {
    'use strict';

    const emailInput = document.getElementById('email');
    const pwdInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');

    toggleBtn?.addEventListener('click', () => {
        if (!pwdInput) return;
        const show = pwdInput.type === 'password';
        pwdInput.type = show ? 'text' : 'password';
        toggleBtn.setAttribute('aria-pressed', show ? 'true' : 'false');
        toggleBtn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        toggleBtn.innerHTML = show
            ? '<i class="bi bi-eye-slash" aria-hidden="true"></i>'
            : '<i class="bi bi-eye" aria-hidden="true"></i>';
    });

    document.querySelectorAll('.js-demo-login').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!emailInput || !pwdInput) return;
            emailInput.value = btn.dataset.email || '';
            pwdInput.value = 'password123';
            pwdInput.focus();
        });
    });
})();
