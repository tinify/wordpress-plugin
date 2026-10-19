(() => {
  const container = document.querySelector('#tinypng_dashboard_widget .inside');
  if (!container) {
    return;
  }

  // Get more credits is a second view in the same HTML. Nothing is stored, so
  // a reload shows the main view again.
  container.addEventListener('click', (event) => {
    const action = event.target.closest('[data-tiny-action]');
    if (!action) {
      return;
    }
    const showCredits = action.dataset.tinyAction === 'more-credits';
    container.querySelector('.tiny-widget-main').hidden = showCredits;
    container.querySelector('.tiny-widget-credits').hidden = !showCredits;
  });
})();
