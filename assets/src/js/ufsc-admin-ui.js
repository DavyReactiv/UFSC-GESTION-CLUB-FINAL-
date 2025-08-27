
document.addEventListener('DOMContentLoaded', () => {
  // Add titles for truncated cells
  document.querySelectorAll('.ufsc-table td, .ufsc-table th').forEach(c => {
    if (!c.getAttribute('title')) {
      c.setAttribute('title', c.textContent.trim());
    }
  });

  const tn = document.querySelector('.wrap .tablenav.top');
  if (tn) {
    tn.style.position = 'sticky';
    tn.style.top = '32px';
    tn.style.zIndex = '10';
    tn.style.background = '#fff';
  }
});
