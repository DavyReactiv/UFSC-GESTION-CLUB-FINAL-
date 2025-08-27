document.addEventListener('DOMContentLoaded', () => {
  // Remove or hide empty action containers
  document.querySelectorAll('[class]').forEach(el => {
    const hasActionsClass = Array.from(el.classList).some(cls => cls === 'actions' || cls.endsWith('-actions'));
    if (hasActionsClass) {
      const hasContent = el.textContent.trim().length > 0;
      const hasElements = el.children.length > 0;
      if (!hasContent && !hasElements) {
        el.remove();
      }
    }
  });

  // Enable sticky table headers if supported
  if (window.CSS && CSS.supports && CSS.supports('position', 'sticky')) {
    document.querySelectorAll('table.ufsc-table thead').forEach(thead => {
      thead.style.position = 'sticky';
      thead.style.top = document.body.classList.contains('admin-bar') ? '32px' : '0';
      thead.style.background = thead.style.background || '#fff';
      thead.style.zIndex = '1';
    });
  }
});
