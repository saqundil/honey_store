(() => {
  'use strict';

  const viewport = document.querySelector('#template-preview-viewport');
  const sheet = document.querySelector('#template-preview-sheet');
  if (!viewport || !sheet) return;

  function fitPreview() {
    const scale = Math.min(1, viewport.clientWidth / sheet.offsetWidth);
    sheet.style.setProperty('--preview-scale', String(scale));
    viewport.style.setProperty('--preview-height', `${sheet.offsetHeight * scale}px`);
  }

  if (window.ResizeObserver) new ResizeObserver(fitPreview).observe(viewport);
  else window.addEventListener('resize', fitPreview);
  fitPreview();
})();