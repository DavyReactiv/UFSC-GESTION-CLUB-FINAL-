export function createProgressBar(steps) {
    const form = document.querySelector('.ufsc-form');
    if (!form) return;

    const wrapper = document.createElement('div');
    wrapper.className = 'ufsc-form-progress';
    wrapper.setAttribute('role', 'progressbar');
    wrapper.setAttribute('aria-label', 'Progression du formulaire');

    const header = document.createElement('div');
    header.className = 'ufsc-progress-header';
    header.innerHTML = '<i class="dashicons dashicons-chart-line"></i>Progression du formulaire';
    wrapper.appendChild(header);

    const stepsEl = document.createElement('div');
    stepsEl.className = 'ufsc-progress-steps';
    const line = document.createElement('div');
    line.className = 'ufsc-progress-line';
    const fill = document.createElement('div');
    fill.className = 'ufsc-progress-line-fill';
    line.appendChild(fill);
    stepsEl.appendChild(line);

    steps.forEach((step, index) => {
        const stepEl = document.createElement('div');
        stepEl.className = 'ufsc-progress-step';
        stepEl.dataset.step = step.id;
        stepEl.innerHTML = `<div class="ufsc-progress-step-number">${index + 1}</div><div class="ufsc-progress-step-label">${step.label}</div>`;
        stepsEl.appendChild(stepEl);
    });

    wrapper.appendChild(stepsEl);
    form.parentNode.insertBefore(wrapper, form);
}

export function updateProgress(steps) {
    const progressFill = document.querySelector('.ufsc-progress-line-fill');
    let completed = 0;
    steps.forEach(step => {
        const section = document.querySelector(step.section);
        const stepEl = document.querySelector(`.ufsc-progress-step[data-step="${step.id}"]`);
        if (section && stepEl) {
            const required = section.querySelectorAll('input[required], select[required]').length;
            const filled = Array.from(section.querySelectorAll('input[required], select[required]')).filter(f => f.value.trim() !== '').length;
            if (required > 0 && filled === required) {
                stepEl.classList.add('completed');
                stepEl.classList.remove('active');
                completed++;
            } else {
                stepEl.classList.remove('completed');
            }
        }
    });
    if (progressFill) {
        const percent = (completed / steps.length) * 100;
        progressFill.style.width = percent + '%';
    }
}

export function trackProgress(steps) {
    const form = document.querySelector('.ufsc-form');
    if (!form) return;
    const handler = () => updateProgress(steps);
    window.addEventListener('scroll', handler);
    form.addEventListener('input', handler);
    form.addEventListener('change', handler);
    setTimeout(handler, 500);
}

export function bindProgressEvents(steps) {
    document.querySelectorAll('.ufsc-progress-step').forEach(stepEl => {
        stepEl.addEventListener('click', () => {
            const step = steps.find(s => s.id === stepEl.dataset.step);
            if (step) {
                const section = document.querySelector(step.section);
                if (section) {
                    window.scrollTo({
                        top: section.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
}
