export function initButtons(root = document) {
  const buttons = root.querySelectorAll('.ufsc-btn[data-icon]');
  buttons.forEach(btn => {
    const icon = btn.getAttribute('data-icon');
    if (!icon) return;
    const pos = btn.getAttribute('data-icon-position') || 'start';
    const span = document.createElement('span');
    span.className = 'ufsc-btn__icon' + (pos === 'end' ? ' ufsc-btn__icon--end' : '');
    span.innerHTML = icon;
    if (pos === 'end') {
      btn.appendChild(span);
    } else {
      btn.insertBefore(span, btn.firstChild);
    }
  });
}
