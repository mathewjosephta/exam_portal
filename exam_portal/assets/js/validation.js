document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            let hasEmpty = false;
            form.querySelectorAll('[required]').forEach((field) => {
                if (!field.value || !field.value.toString().trim()) {
                    hasEmpty = true;
                    field.setCustomValidity('This field is required.');
                } else {
                    field.setCustomValidity('');
                }
            });
            const pass = form.querySelector('input[name="password"]');
            const confirm = form.querySelector('input[name="confirm_password"]');
            if (pass && confirm && pass.value && confirm.value && pass.value !== confirm.value) {
                confirm.setCustomValidity('Passwords do not match.');
                hasEmpty = true;
            }
            if (hasEmpty) {
                event.preventDefault();
                form.reportValidity();
            }
        });
    });
});
