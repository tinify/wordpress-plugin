(function () {
  const container = document.querySelector('#tinypng_dashboard_widget .inside');
  if (!container) {
    return;
  }

  // The statistics scan the whole library, so the server renders the widget
  // after the page has loaded and all state rules stay in PHP.
  async function load() {
    const loading = container.querySelector('.tiny-widget-loading');
    try {
      const response = await fetch(window.ajaxurl, {
        method: 'POST',
        credentials: 'same-origin',
        body: new URLSearchParams({ action: 'tiny_dashboard_widget', _nonce: loading.dataset.nonce }),
      });
      if (!response.ok) {
        throw new Error(response.statusText);
      }
      container.innerHTML = await response.text();
    } catch (error) {
      loading.querySelector('.spinner').remove();
      loading.querySelector('p').hidden = false;
    }
  }

  load();
})();
