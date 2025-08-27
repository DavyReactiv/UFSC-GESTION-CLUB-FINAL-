export function setupValidation(form, config) {
    function setupFieldValidation(selector, rule, validMsg, invalidMsg) {
        const fields = form.querySelectorAll(selector);
        fields.forEach(field => {
            const handler = () => {
                const value = field.value.trim();
                if (!value) {
                    clearValidation(field);
                    return;
                }
                const valid = validateField(value, rule);
                showValidation(field, valid, validMsg, invalidMsg);
            };
            field.addEventListener('input', handler);
            field.addEventListener('blur', handler);
        });
    }

    function validateField(value, rule) {
        if (rule === 'phone') {
            const cleanPhone = value.replace(/[\s.-]/g, '');
            return config.validationRules.phone.test(cleanPhone);
        }
        const regex = config.validationRules[rule];
        return regex ? regex.test(value) : false;
    }

    function showValidation(field, isValid, validMsg, invalidMsg) {
        const container = field.closest('.ufsc-form-row > div');
        if (!container) return;

        container.querySelectorAll('.ufsc-validation-icon, .ufsc-validation-message').forEach(el => el.remove());
        field.classList.remove('valid', 'invalid');

        let wrapper = field.parentElement;
        if (!wrapper.classList.contains('ufsc-field-validation')) {
            wrapper = document.createElement('div');
            wrapper.className = 'ufsc-field-validation';
            field.parentNode.insertBefore(wrapper, field);
            wrapper.appendChild(field);
        }
        wrapper.classList.remove('valid', 'invalid');

        const icon = document.createElement('span');
        const message = document.createElement('div');
        message.classList.add('ufsc-validation-message', 'show');

        if (isValid) {
            field.classList.add('valid');
            wrapper.classList.add('valid');
            icon.classList.add('ufsc-validation-icon', 'valid');
            icon.textContent = '\u2713';
            message.classList.add('valid');
            message.textContent = validMsg;
        } else {
            field.classList.add('invalid');
            wrapper.classList.add('invalid');
            icon.classList.add('ufsc-validation-icon', 'invalid');
            icon.textContent = '\u2717';
            message.classList.add('invalid');
            message.textContent = invalidMsg;
        }
        wrapper.appendChild(icon);
        container.appendChild(message);
    }

    function clearValidation(field) {
        const container = field.closest('.ufsc-form-row > div');
        if (!container) return;
        container.querySelectorAll('.ufsc-validation-icon, .ufsc-validation-message').forEach(el => el.remove());
        field.classList.remove('valid', 'invalid');
        const parent = field.parentElement;
        if (parent.classList.contains('ufsc-field-validation')) {
            parent.classList.remove('valid', 'invalid');
        }
    }

    setupFieldValidation('input[type="email"]', 'email', 'Adresse email valide', 'Format d\'email invalide');
    setupFieldValidation('input[name="code_postal"]', 'postal', 'Code postal valide', 'Format: 5 chiffres');
    setupFieldValidation('input[type="tel"], input[name="telephone"]', 'phone', 'Num\u00E9ro valide', 'Format: 01 23 45 67 89');
    setupFieldValidation('input[name="siren"]', 'siren', 'SIREN valide', 'Format: 9 chiffres');
    setupFieldValidation('input[name$="_tel"]', 'phone', 'T\u00E9l\u00E9phone valide', 'Format: 01 23 45 67 89');
    setupFieldValidation('input[name$="_email"]', 'email', 'Email valide', 'Format d\'email invalide');
    setupFieldValidation('input[name$="_nom"]', 'required', 'Nom renseign\u00E9', 'Le nom est obligatoire');
    setupFieldValidation('input[name$="_prenom"]', 'required', 'Pr\u00E9nom renseign\u00E9', 'Le pr\u00E9nom est obligatoire');
}

export function validateForm(form) {
    const requiredFilled = Array.from(form.querySelectorAll('input[required], select[required]'))
        .every(field => field.value.trim() !== '');
    const noInvalid = form.querySelectorAll('.invalid').length === 0;
    return requiredFilled && noInvalid;
}
