import { initButtons } from '../src/components/buttons.js';
import { initModals } from '../src/components/modal.js';
import { showToast } from '../src/components/toast.js';

window.UFSCAdmin = {
  initButtons,
  initModals,
  showToast
};

document.addEventListener('DOMContentLoaded', () => {
  initButtons();
  initModals();
});
