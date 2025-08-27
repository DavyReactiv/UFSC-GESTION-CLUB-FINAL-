export function initModals(root = document) {
  const triggers = root.querySelectorAll('[data-modal-target]');
  const modals = new Map();

  triggers.forEach(trigger => {
    const selector = trigger.getAttribute('data-modal-target');
    const modal = root.querySelector(selector);
    if (!modal) return;
    if (!modals.has(modal)) {
      setupModal(modal);
      modals.set(modal, true);
    }
    trigger.addEventListener('click', e => {
      e.preventDefault();
      openModal(modal, trigger);
    });
  });
}

function setupModal(modal) {
  modal.setAttribute('role', 'dialog');
  modal.setAttribute('aria-modal', 'true');
  modal.setAttribute('aria-hidden', 'true');
  const closeBtn = modal.querySelector('[data-modal-close]');
  if (closeBtn) {
    closeBtn.addEventListener('click', () => closeModal(modal));
  }
  modal.addEventListener('click', e => {
    if (e.target === modal) closeModal(modal);
  });
}

function openModal(modal, trigger) {
  modal._trigger = trigger;
  modal._previousFocus = document.activeElement;
  modal.setAttribute('aria-hidden', 'false');
  trapFocus(modal);
}

function closeModal(modal) {
  modal.setAttribute('aria-hidden', 'true');
  document.removeEventListener('keydown', modal._trapHandler);
  modal._previousFocus && modal._previousFocus.focus();
}

function trapFocus(modal) {
  const focusables = modal.querySelectorAll('a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])');
  if (!focusables.length) return;
  const first = focusables[0];
  const last = focusables[focusables.length - 1];
  const handler = e => {
    if (e.key === 'Tab') {
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    } else if (e.key === 'Escape') {
      closeModal(modal);
    }
  };
  modal._trapHandler = handler;
  document.addEventListener('keydown', handler);
  first.focus();
}
