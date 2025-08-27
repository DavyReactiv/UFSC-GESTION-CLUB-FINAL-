/**
 * Enhanced Club Affiliation Form JavaScript (Vanilla JS version)
 * Features: Progress tracking, real-time validation, tooltips
 */

import { setupValidation, validateForm } from './modules/validation.js';
import { createProgressBar, trackProgress, bindProgressEvents } from './modules/progress.js';

const config = {
    progressSteps: [
        { id: 'general', label: 'Informations générales', section: '.ufsc-form-section:nth-child(1)' },
        { id: 'legal', label: 'Informations légales', section: '.ufsc-form-section:nth-child(2)' },
        { id: 'managers', label: 'Dirigeants', section: '.ufsc-form-section:nth-child(3)' },
        { id: 'documents', label: 'Documents', section: '.ufsc-form-section:nth-child(4)' }
    ],
    validationRules: {
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        postal: /^[0-9]{5}$/,
        phone: /^(?:(?:\+33|0)[1-9](?:[.\-\s]?\d{2}){4})$/,
        siren: /^[0-9]{9}$/,
        required: /^.+$/
    },
    tooltips: {
        'nom': "Nom complet et officiel de votre club tel qu'il apparaît dans vos statuts",
        'email': "Adresse email principale qui sera utilisée pour toutes les communications officielles",
        'telephone': "Numéro de téléphone principal du club (format: 01 23 45 67 89)",
        'code_postal': "Code postal de l'adresse officielle du club (5 chiffres)",
        'siren': "Numéro SIREN de votre association (9 chiffres, disponible sur votre récépissé de déclaration)",
        'num_declaration': "Numéro de déclaration en préfecture (commence généralement par W)",
        'statuts': "Statuts de l'association signés et datés (format PDF recommandé)",
        'recepisse': "Récépissé de déclaration délivré par la préfecture",
        'cer': "Contrat d'engagement républicain signé par le représentant légal"
    }
};

function setupTooltips(tooltips) {
    Object.keys(tooltips).forEach(name => {
        const field = document.querySelector(`input[name="${name}"], select[name="${name}"]`);
        if (!field) return;
        const label = field.closest('.ufsc-form-row')?.querySelector('label');
        if (!label) return;

        const trigger = document.createElement('span');
        trigger.className = 'ufsc-tooltip-trigger';
        trigger.tabIndex = 0;
        trigger.setAttribute('role', 'button');
        trigger.setAttribute('aria-label', 'Aide pour ce champ');
        trigger.innerHTML = `<span class="ufsc-tooltip-icon">?</span><div class="ufsc-tooltip-content" role="tooltip">${tooltips[name]}</div>`;
        label.appendChild(trigger);
    });
}

function init() {
    const form = document.querySelector('.ufsc-form');
    const sections = document.querySelectorAll('.ufsc-form-section');
    if (!form || sections.length < 4) {
        return; // degrade gracefully if form not present
    }

    console.log('🚀 UFSC Form Enhancer: Initializing...');

    createProgressBar(config.progressSteps);
    setupValidation(form, config);
    setupTooltips(config.tooltips);
    trackProgress(config.progressSteps);
    bindProgressEvents(config.progressSteps);

    form.addEventListener('submit', evt => {
        if (!validateForm(form)) {
            evt.preventDefault();
        }
    });

    console.log('✅ UFSC Form Enhancer: All enhancements loaded');
}

document.addEventListener('DOMContentLoaded', init);
document.addEventListener('DOMNodeInserted', e => {
    if (e.target.querySelector && e.target.querySelector('.ufsc-form')) {
        setTimeout(init, 100);
    }
});

// Expose for debugging
export const UFSCFormEnhancer = { init, config };
window.UFSCFormEnhancer = UFSCFormEnhancer;
