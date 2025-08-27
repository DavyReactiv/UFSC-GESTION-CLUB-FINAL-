export function showToast(message, { type = 'info', duration = 4000 } = {}) {
  let container = document.getElementById('ufsc-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'ufsc-toast-container';
    container.className = 'ufsc-toast-container';
    container.setAttribute('role', 'region');
    container.setAttribute('aria-live', 'polite');
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `ufsc-toast ufsc-toast--${type}`;
  toast.setAttribute('role', 'status');
  toast.textContent = message;
  container.appendChild(toast);

  setTimeout(() => {
    toast.remove();
    if (!container.children.length) {
      container.remove();
    }
  }, duration);
}
