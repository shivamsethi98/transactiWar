/**
 * TransactiWar — Client-side enhancements (UX only, not security).
 */

document.addEventListener('DOMContentLoaded', function () {

    // Successful login: brief buffering animation before redirect
    var loginBuffer = document.getElementById('login-buffer');
    if (loginBuffer) {
        var redirectTo = loginBuffer.getAttribute('data-redirect') || '/dashboard';
        var delayMs = parseInt(loginBuffer.getAttribute('data-delay-ms'), 10);

        if (isNaN(delayMs) || delayMs < 0) {
            delayMs = 1000;
        }

        setTimeout(function () {
            window.location.assign(redirectTo);
        }, delayMs);
    }

    // Auto-dismiss flash alerts after 5 seconds
    document.querySelectorAll('.tw-alert').forEach(function (alert) {
        setTimeout(function () {
            if (window.bootstrap && bootstrap.Alert) {
                var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                bsAlert.close();
            } else {
                alert.remove();
            }
        }, 5000);
    });

    // Transfer confirmation dialog
    var transferForm = document.getElementById('transfer-form');
    if (transferForm) {
        transferForm.addEventListener('submit', function (e) {
            var amount = this.querySelector('[name="amount"]').value;
            var receiverId = this.querySelector('[name="receiver_id"]').value;
            if (!confirm('Transfer Rs. ' + amount + ' to User #' + receiverId + '?')) {
                e.preventDefault();
            }
        });
    }

    // Client-side form validation (UX enhancement only)
    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var valid = true;
            this.querySelectorAll('[required]').forEach(function (input) {
                if (!input.value.trim()) {
                    input.classList.add('is-invalid');
                    valid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });
            if (!valid) e.preventDefault();
        });
    });

    // Clear invalid state on input
    document.querySelectorAll('.form-control').forEach(function (input) {
        input.addEventListener('input', function () {
            this.classList.remove('is-invalid');
        });
    });

    // Avatar preview before upload
    var avatarInput = document.getElementById('avatar-input');
    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            var file = this.files[0];
            if (file) {
                // Client-side size check (2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('File too large. Maximum size is 2MB.');
                    this.value = '';
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (e) {
                    var preview = document.getElementById('avatar-preview');
                    if (preview) preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // Password strength indicator
    var passwordInput = document.getElementById('password-input');
    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            var val = this.value;
            var strength = 0;
            if (val.length >= 8) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[a-z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^a-zA-Z0-9]/.test(val)) strength++;

            var bar = document.getElementById('password-strength');
            if (bar) {
                var percent = (strength / 5) * 100;
                bar.style.width = percent + '%';
                bar.className = 'progress-bar';
                if (strength <= 2) bar.classList.add('bg-danger');
                else if (strength <= 3) bar.classList.add('bg-warning');
                else bar.classList.add('bg-success');
            }
        });
    }

    // Amount input: format to 2 decimal places on blur
    var amountInput = document.querySelector('[name="amount"]');
    if (amountInput) {
        amountInput.addEventListener('blur', function () {
            var val = parseFloat(this.value);
            if (!isNaN(val) && val > 0) {
                this.value = val.toFixed(2);
            }
        });
    }

});
